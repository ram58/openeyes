<?php
/**
 * OpenEyes
 *
 * (C) OpenEyes Foundation, 2021
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2021, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */

use Zend\Http\Client;
/**
 * Class CitoIntegration
 *
 * Integration component for Cito - provides link to CITO based on module
 * configuration.
 */

class CitoIntegration extends \CApplicationComponent
{
    /**
     * @var string
     */
    public $cito_sign_url;
    /**
     * @var string
     */
    public $cito_otp_url;
    /**
     * @var string
     */
    public $cito_access_token_url;
    /**
     * @var string
     */
    public $cito_application_id;
    /**
     * @var string
     */
    public $cito_grant_type;
    /**
     * @var string
     */
    public $cito_client_id;
    /**
     * @var string
     */
    public $cito_client_secret;

    /**
     * @inheritDoc
     */
    public function init()
    {
        foreach (['cito_otp_url', 'cito_sign_url', 'cito_access_token_url', 'cito_application_id', 'cito_grant_type', 'cito_client_id', 'cito_client_secret'] as $key => $value) {
            if ($this->getSetting($value)) {
                $this->{$value} = $this->getSetting($value);
            } else {
                if (strlen(\SettingMetadata::model()->getSetting($value)) === 0) {
                    throw new Exception($value . ' is not set.');
                }
                $this->{$value} = \SettingMetadata::model()->getSetting($value);
            }
        }
    }

    protected function getSetting($value) {
        $app = Yii::app();
        return $app->params[$value];
    }

    /**
     * Set the cURL that will do the api calls
     * @param string $url
     * @param array $params
     * @param array $headers
     */
    private function setCurl($url, $params = NULL, $headers = NULL)
    {
        $ch = curl_init($url);

        if(!empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if(!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);

        return $ch;
    }

    /**
     * Get access token to CITO api url
     * @return string
     * @throws Exception
     */
    private function getCitoAccessToken()
    {
        $params = [
            'grant_type' => $this->cito_grant_type,
            'client_id' => $this->cito_client_id,
            'client_secret' => $this->cito_client_secret
        ];
        $curl = $this->setCurl($this->cito_access_token_url, $params);
        $curl_result = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpcode > 299) {
            throw new \Exception('Unable to login, user credentials in config incorrect');
        }

        $jsonResponse = json_decode($curl_result, true);
        if(!is_array($jsonResponse) || !array_key_exists('access_token', $jsonResponse)) {
            throw new \Exception('The server did not send a valid JSON reply.');
        }
        return $jsonResponse['access_token'];
    }

    /**
     * Get OTP to CITO api url
     *
     * @param string $userName      username
     * @param string $accessToken   token previously generated by getCitoAccessToken
     *
     * @return string   The one time password
     * @throws Exception
     */


    private function getCitoOneTimePassCode(string $userName, string $accessToken)
    {
        $params = [
            'domain' => $this->cito_application_id,
            'userName' => $userName,
        ];
        $headers = [
            'accept: application/json',
            'Content-Type: application/json-patch+json',
            'Authorization: Bearer ' . $accessToken,
        ];
        $curl = $this->setCurl($this->cito_otp_url, json_encode($params), $headers);
        $curl_result = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpcode > 299) {
            throw new \Exception('Unable to oneTimePassCode');
        }
        $jsonResponse = json_decode($curl_result, true);
        if(!is_array($jsonResponse) || !array_key_exists('oneTimePassCode', $jsonResponse)) {
            throw new \Exception('The server did not send a valid JSON reply.');
        }
        return $jsonResponse['oneTimePassCode'];
    }

    /**
     * Generate CITO url
     *
     * @param string $hos_num   Hospital number
     * @param string $username  Username
     * @param string $otp       One-time password received from API
     *
     * @return string
     */
    private function getUrl(string $hosNum, string $username, string $otp) : string
    {
        if(strlen($hosNum) === 0) {
            throw new \Exception("Hospital number is empty");
        }
        if(strlen($username) === 0) {
            throw new \Exception("Username is empty");
        }
        if(strlen($otp) === 0) {
            throw new \Exception("OTP is empty");
        }

        return $this->cito_sign_url . "?identifier=" . $hosNum . "&display=cito-icm-record&otp=" . urlencode($otp) . "&user=" . $username . "&domain=" .$this->cito_application_id . "";
    }

    /**
     * @param string $hosNum    Patient hospital number
     * @param string $userName  Username
     *
     * @return string   CITO url
     */
    public function generateCitoUrl(string $hosNum, string $userName) : string
    {
        $token = $this->getCitoAccessToken();
        $otp = $this->getCitoOneTimePassCode($userName, $token);
        return $this->getUrl($hosNum, $userName, $otp);
    }
}