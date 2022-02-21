<?php
/**
 * OpenEyes.
 *
 * (C) OpenEyes Foundation, 2019
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2019, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */
require_once './vendor/setasign/fpdi/src/PdfParser/PdfParser.php';

use Xthiago\PDFVersionConverter\Guesser\RegexGuesser;

class DefaultController extends BaseEventTypeController
{
    protected $max_document_size = 10485760;
    protected $max_document_name_length = 255;
    protected $max_content_length = 8388608;
    protected $allowed_file_types = array();
    protected bool $is_updating = false;

    /**
     * @var OphCoDocument_Sub_Types
     */
    protected $sub_type;

    protected static $action_types = array(
        'fileUpload' => self::ACTION_TYPE_FORM,
        'fileRemove' => self::ACTION_TYPE_FORM,
        'removeDocuments' => self::ACTION_TYPE_FORM,
        'getImage' => self::ACTION_TYPE_FORM,
    );

    protected $pdf_output;

    public function init()
    {
        $this->allowed_file_types = Yii::app()->params['OphCoDocument']['allowed_file_types'];
        $this->max_document_size = Helper::return_bytes(ini_get('upload_max_filesize'));
        $this->jsVars['max_document_name_length'] = $this->max_document_name_length;
        $this->max_content_length = Helper::return_bytes(ini_get('upload_max_filesize'));

        $this->jsVars['max_document_size'] = $this->max_document_size;
        $this->jsVars['max_content_length'] = $this->max_content_length;
        $this->jsVars['allowed_file_types'] = array_values($this->allowed_file_types);

        return parent::init(); // TODO: Change the autogenerated stub
    }

    /**
     * Returns the allowed file size in MB or bytes
     * @param bool $to_mb
     * @return int
     */
    public function getMaxDocumentSize($to_mb = true)
    {
        $size = $to_mb ? (number_format($this->max_document_size / 1048576, 0)) : $this->max_document_size;
        return $size;
    }

    /**
     * Returns the allowed file types (extensions)
     * @return array
     */
    public function getAllowedFileTypes()
    {
        return array_keys($this->allowed_file_types);
    }

    /**
     *
     */
    protected function initActionView()
    {
        parent::initActionView();
        $el = Element_OphCoDocument_Document::model()->findByAttributes(array('event_id' => $this->event->id));
        $this->sub_type = $el->sub_type;
        $this->title = $el->sub_type->name;
    }

    public function initActionCreate()
    {
        parent::initActionCreate();
        $this->initEdit();
    }

    protected function initEdit()
    {
        $assetManager = \Yii::app()->getAssetManager();
        $baseAssetsPath = \Yii::getPathOfAlias('application.assets.js');
        $assetManager->publish($baseAssetsPath, true);

        Yii::app()->clientScript->registerScriptFile($assetManager->getPublishedUrl($baseAssetsPath, true) . '/OpenEyes.UI.ImageAnnotator.js', \CClientScript::POS_END);
    }

    public function initActionUpdate()
    {
        $this->is_updating = true;
        parent::initActionUpdate();
        $this->initEdit();
    }

    /**
     * @param $files
     * @param $index
     * @return null|string
     */
    private function documentErrorHandler($files, $index)
    {
        $message = null;

        switch ($files['Document']['error'][$index]) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded!';
                return $message;
                break;
            case UPLOAD_ERR_INI_SIZE:
                $message = "The file you tried to upload exceeds the maximum allowed file size, which is " . $this->getMaxDocumentSize() . " MB ";
                return $message;
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The document\'s size is too large!';
                return $message;
                break;
            default:
                $message = 'Unknown error! Please try again!';
                return $message;
        }

        $file_contents = file_get_contents($files['Document']['tmp_name'][$index]);

        if (strtolower(SettingMetadata::model()->getSetting('enable_virus_scanning')) === 'on') {
            try {
                if (!VirusScanController::stringIsClean($file_contents)) {
                    $message = 'File contains potentially malicious data and cannot be saved.';
                    return $message;
                }
            } catch (\Throwable $e) {
                $message = 'Cannot connect to virus scanner. Please contact a system administrator.';
                return $message;
            }
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $file_mime = strtolower($finfo->file($files['Document']['tmp_name'][$index]));
        $extension = pathinfo($files['Document']['name'][$index], PATHINFO_EXTENSION);

        if (false === array_search($file_mime, $this->allowed_file_types, true)) {
            $message = 'Only the following file types can be uploaded: ' . (implode(', ', $this->getAllowedFileTypes())) . '.';
            $message .= "\n\nFor reference, the type of the file you tried to upload is: <i>$extension</i>, which is mime type: <i>$file_mime</i>";
        }

        return $message;
    }

    /**
     * @param $tmp_name
     * @param $original_name
     * @return int|boolean
     */
    private function uploadFile($tmp_name, $original_name)
    {
        $image_size = getimagesize($tmp_name);
        $mime = isset($image_size['mime']) ? $image_size['mime'] : null;
        if ($mime && $mime == 'image/jpeg') {
            $tmp_name = Element_OphCoDocument_Document::model()->rotate($tmp_name);
        }

        $p_file = ProtectedFile::createFromFile($tmp_name);
        $p_file->name = $original_name;
        $p_file->title = $original_name;

        if ($p_file->save()) {
            unlink($tmp_name);
            return $p_file->id;
        } else {
            $errors = $p_file->getErrors();
        }
    }

    public function actionRemoveDocuments()
    {
        $doc_ids = \Yii::app()->request->getPost('doc_ids', []);
        foreach ($doc_ids as $doc_id) {
            try {
                $doc = ProtectedFile::model()->findByPk($doc_id);
                if ($doc && file_exists($doc->getFilePath() . '/' . $doc->uid)) {
                    $doc->delete();
                } else {
                    OELog::log(($doc ? "Failed to delete the document from " . $doc->getFilePath() : "Failed to find document"));
                }
            } catch (Exception $e) {
                OELog::log("Failed to delete the ProtectedFile with id = " . $doc_id);
            }
        }
    }

    /**
     *
     */
    public function actionGetImage()
    {
        $return_data = null;
        if (isset($_POST['subTypeId'], $_POST['uploadMode'])) {
            $subTypeId = $_POST['subTypeId'];
            $documentId = OphCoDocument_Sub_Types::model()->findByPk($subTypeId)->document_id;
            if ($documentId) {
                if (!$file = ProtectedFile::model()->findByPk($documentId)) {
                    throw new CHttpException(404, 'File not found');
                }

                if (!file_exists($file->getPath())) {
                    throw new CException('File not found on filesystem: '.$file->getPath());
                }
                if ($_POST['uploadMode'] === 'single') {
                    $return_data['document'] = ['single'];
                } else {
                    $return_data['document'] = ['right', 'left'];
                }

                $return_data['extension'] = strtolower(explode('.', $file->name)[1]);
                $return_data['mime_type'] = $file->mimetype;
                $return_data['file_id'] = $file->id;
                $return_data['file_name'] = $file->name;
                $return_data['file_size'] = number_format($file->size / 1048576, 2) . 'Mb';
            }
        }
        $this->renderJSON($return_data);
    }

    /**
     * @return string
     */
    public function getHeaderBackgroundImage()
    {
        if ($this->sub_type) {
            if (in_array($this->sub_type->name, array('OCT', 'Photograph'))) {
                $asset_path = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.modules.' . $this->event->eventType->class_name . '.assets'), true) . '/';
                return $asset_path . 'img/medium' . $this->sub_type->name . '.png';
            }
        }
    }

    /**
     *
     */
    public function actionFileUpload()
    {
        foreach ($_FILES as $file) {
            $return_data = array();
            foreach (array('single_document_id', 'left_document_id', 'right_document_id') as $file_key) {
                if (isset($file["name"][$file_key]) && strlen($file["name"][$file_key]) > 0) {
                    $handler = $this->documentErrorHandler($_FILES, $file_key);
                    if ($handler == null) {
                        $return_data[$file_key] = $this->uploadFile($file["tmp_name"][$file_key], $file["name"][$file_key]);
                    } else {
                        $return_data = array(
                            's' => 0,
                            'msg' => $handler,
                            'index' => $file_key
                        );
                    }
                }
            }

            $this->renderJSON($return_data);
        }
    }

    /**
     * @param $mimetype
     * @return string
     */
    public function getTemplateForMimeType($mimetype)
    {
        if (strpos($mimetype, "image/") !== false) {
            return 'image';
        } elseif (strpos($mimetype, "video/") !== false) {
            return 'video';
        } else {
            return 'object';
        }
    }

    /**
     * @param $element
     * @param $index
     * @param $side
     */
    public function generateFileField($element, $index, $side)
    {
        if ($element->{$index."_id"} > 0) {
            $this->renderPartial('form_'.$this->getTemplateForMimeType($element->{$index}->mimetype), array('element'=>$element, 'index'=>$index, 'side'=>$side));
        }
    }

    /**
     * @inheritdoc
     */

    public function actionSavePDFprint($id)
    {
        $this->initWithEventId($id);

        $imgdir = $this->event->getImageDirectory();
        if (!file_exists($imgdir)) {
            mkdir($imgdir, 0775, true);
        }

        if ($this->eventContainsImagesOnly()) {
            return parent::actionSavePDFprint($id);
        }

        $auto_print = Yii::app()->request->getParam('auto_print', true);
        $inject_autoprint_js = $auto_print == "0" ? false : $auto_print;

        $pdfpath = $this->actionPDFPrint($id, true, $inject_autoprint_js);
        $pf = ProtectedFile::createFromFile($pdfpath);
        if ($pf->save()) {
            $result = array(
                'success' => 1,
                'file_id' => $pf->id,
            );

            if (!isset($_GET['ajax'])) {
                $result['name'] = $pf->name;
                $result['mime'] = $pf->mimetype;
                $result['path'] = $pf->getPath();

                return $result;
            }
        } else {
            $result = array(
                'success' => 0,
                'message' => "couldn't save file object" . print_r($pf->getErrors(), true)
            );
        }

        return $this->renderJSON($result);
    }

    /**
     * @return bool
     *
     * Returns whether only images are uploaded to this event
     */

    private function eventContainsImagesOnly()
    {
        $document_types = $this->getDocumentTypes();
        return array_values($document_types) === array('image');
    }

    /**
     * @return array
     *
     * Returns an array of all different document types uploaded to this event
     */

    private function getDocumentTypes()
    {
        $document_types = array();

        foreach ($this->event->getElements() as $element) {
            foreach (array("single_document", "left_document", "right_document") as $property) {
                if (isset($element->$property)) {
                    $mimetype = $element->$property->mimetype;
                    if (strpos($mimetype, "image/") === 0) {
                        $document_types[] = 'image';
                    } elseif ($mimetype = 'application/pdf') {
                        $document_types[] = 'pdf';
                    } else {
                        $document_types[] = 'other';
                    }
                }
            }
        }

        return array_unique($document_types);
    }

    /**
     * @inheritdoc
     */

    public function actionPDFPrint($id, $return_pdf_path = false, $inject_autoprint_js = true)
    {
        $this->initWithEventId($id);

        if (in_array('other', $this->getDocumentTypes())) {
            // Other documents cannot be printed
            throw new Exception("Only images or PDF documents can be printed");
        }

        // Image(s) only
        if ($this->eventContainsImagesOnly()) {
            return parent::actionPDFPrint($id);
        } // Pdf(s) only - or - pdf(s) and image(s) mixed
        else {
            $this->pdf_output = new PDF_JavaScript();
            foreach ($this->event->getElements() as $element) {
                foreach (array("single_document", "left_document", "right_document") as $property) {
                    if (isset($element->$property)) {
                        if (strpos($element->$property->mimetype, "image/") === 0) {
                            $auto_print = Yii::app()->request->getParam('auto_print', true);
                            $inject_autoprint_js = $auto_print == "0" ? false : $auto_print;

                            $pdf_route = $this->setPDFprintData($id, $inject_autoprint_js);

                            $pdf = $this->event->getPDF($pdf_route);
                            $this->addPDFToOutput($pdf);
                        } else {
                            $this->addPDFToOutput($element->$property->getPath());
                        }
                    }
                }
            }

            if ($inject_autoprint_js) {
                $script = 'print(true);';
                $this->pdf_output->IncludeJS($script);
            }

            $imgdir = $this->event->imageDirectory;
            $pdf_path = $imgdir . '/event_print.pdf';
            if (!file_exists($imgdir)) {
                mkdir($imgdir, 0775, true);
            }
            $this->pdf_output->Output("F", $pdf_path);

            if ($return_pdf_path) {
                return $pdf_path;
            }

            header('Content-Type: application/pdf');
            header('Content-Length: ' . filesize($pdf_path));

            readfile($pdf_path);
            return Yii::app()->end();
        }
    }

    /**
     * Merges a PDF file to the end of the output
     *
     * @param $pdf_path
     */

    private function addPDFToOutput($pdf_path)
    {
        if ((float)$this->getPDFVersion($pdf_path) > 1.4) {
            $pdf_path = $this->convertPDF($pdf_path);
        }

        $pagecount = $this->pdf_output->setSourceFile($pdf_path);
        for ($i = 1; $i <= $pagecount; $i++) {
            $this->pdf_output->AddPage('P');
            $tplidx = $this->pdf_output->ImportPage($i);
            $this->pdf_output->useTemplate($tplidx);
        }
    }

    /**
     * @param string $pdf_path
     * @return string
     */

    private function getPDFVersion($pdf_path)
    {
        $guesser = new RegexGuesser();
        return $guesser->guess($pdf_path);
    }

    /**
     * @param $pdf_path
     * @param string $version
     *
     * @return string Filepath of the converted document
     */

    private function convertPDF($pdf_path, $version = '1.4')
    {
        $tmpfname = tempnam("/tmp", "OE");
        exec('gs -sDEVICE=pdfwrite -dCompatibilityLevel=' . $version . ' -dNOPAUSE -dBATCH -sOutputFile=' . $tmpfname . ' ' . $pdf_path);
        return $tmpfname;
    }


    /**
     * Creates a preview event image for the event with the given ID
     *
     * @param int $id The ID of the vent to genreate a preview image for
     * @throws Exception
     */
    public function actionCreateImage($id)
    {
        try {
            $this->initActionView();
            $this->removeEventImages();

            /* @var Element_OphCoDocument_Document $element */
            $element = Element_OphCoDocument_Document::model()->findByAttributes(array('event_id' => $this->event->id));
            /* @var ProtectedFile $document */
            foreach (
                [
                    Eye::LEFT => $element->left_document,
                    Eye::RIGHT => $element->right_document,
                    null => $element->single_document,
                ] as $eye => $document
            ) {
                if (!$document) {
                    continue;
                }

                // Always write the file contents to the file, even if it already exists. This will ensure the contents are always up-to-date.
                file_put_contents($document->getPath(), file_get_contents($document->getPath()));

                switch ($document->mimetype) {
                    case 'application/pdf':
                        $this->createPdfPreviewImages($document->getPath(), $eye);
                        break;
                    case 'image/jpeg':
                    case 'image/png':
                        $imagick = new Imagick();
                        $imagick->readImage($document->getPath());
                        $this->createPdfPreviewImages($document->getPath(), $eye);
                        break;
                    case 'image/gif':
                        $imagick = new Imagick();
                        $imagick->readImage($document->getPath());
                        $format = end(explode('/', $document->mimetype));
                        $output_path = $this->getPreviewImagePath(['eye' => $eye], '.' . $format);
                        $imagick->writeImage($output_path);
                        $this->saveEventImage('CREATED', array('image_path' => $output_path, 'eye_id' => $eye));
                        break;
                    case 'video/mp4':
                    case 'video/ogg':
                    case 'video/quicktime':
                        $output_path = $this->getPreviewImagePath(['eye' => $eye], '.jpg');

                        // Use ffmpeg to generate a thumbnail of the video
                        $command = 'ffmpeg -i ' . $document->getPath() . ' -vf "thumbnail" -frames:v 1 ' . $output_path . ' -y 2>&1';
                        Yii::log('Executing command: ' . $command);
                        $result = shell_exec($command);
                        Yii::log('Result: ' . $result);

                        // Resize the thumbnail
                        $imagick = new Imagick();
                        $imagick->readImage($output_path);
                        $this->scaleImageForThumbnail($imagick);

                        // Add a white triangle to the in the center of the preview
                        $draw = new \ImagickDraw();

                        $centreX = $imagick->getImageWidth() / 2;
                        $centreY = $imagick->getImageHeight() / 2;
                        $triangleSideLength = $imagick->getImageHeight() / 4;
                        $triangleWidth = sqrt(3) / 2 * $triangleSideLength;

                        $draw->setFillColor(new ImagickPixel('#4E4E4E'));
                        $draw->circle($centreX, $centreY, $centreX + $triangleWidth, $centreY);

                        $draw->setFillColor(new \ImagickPixel('white'));

                        $draw->polygon([
                            ['x' => $centreX - $triangleWidth / 3, 'y' => $centreY - $triangleSideLength / 2],
                            ['x' => $centreX + $triangleWidth * 2 / 3, 'y' => $centreY],
                            ['x' => $centreX - $triangleWidth / 3, 'y' => $centreY + $triangleSideLength / 2]
                        ]);

                        $imagick->drawImage($draw);

                        if (!$imagick->writeImage($output_path)) {
                            throw new Exception('An error occurred when resizing the video thumbnail');
                        }

                        $this->saveEventImage('CREATED', array('image_path' => $output_path, 'eye_id' => $eye));
                        break;
                    default:
                        // If the mime type isn't recognised, then use a preview of the entire event
                        parent::actionCreateImage($id);
                }
            }
        } catch (Exception $ex) {
            $this->saveEventImage('FAILED', ['message' => (string)$ex]);
            throw $ex;
        }
    }


    /**
     * @param $fileContent
     * @param $name
     * @return int | string
     * @throws Exception
     */
    private function getProtectedFileId($fileContent, $name): int
    {
        $return = explode(';', $fileContent);
        if (sizeof($return) > 1) {
            $type = $return[0];

            list(, $fileContent) = explode(',', $fileContent);
            $fileContent = base64_decode($fileContent);

            $fileExtension = explode("/", $type);
            $tmp_name = '/tmp/' . $name . '.' . $fileExtension[1];
            file_put_contents($tmp_name, $fileContent);

            $p_file = ProtectedFile::createFromFile($tmp_name);
            $p_file->name = $name;
            $p_file->title = $name;

            if ($p_file->save()) {
                unlink($tmp_name);
                return $p_file->id;
            } else {
                throw new Exception('Unable to save the document: ' . print_r($p_file->getErrors(), true));
            }
        } else {
            return $fileContent;
        }
    }

    /**
     * @param $element
     * @param $data
     * @param $index
     * @throws Exception
     */
    protected function setComplexAttributes_Element_OphCoDocument_Document($element, $data, $index)
    {
        $model_name = \CHtml::modelName($element);
        if ($data['upload_mode'] === 'single') {
            if (!empty($data['ProtectedFile']['single_file_content']) && $data['single_file_canvas_modified'] === '1') {
                $name = ProtectedFile::model()->findByPk($element->single_document_id)->name;
                $element->single_document_id = $this->getProtectedFileId($data['ProtectedFile']['single_file_content'], $name);
                // before deleting check if it is template or not.
                $count = OphCoDocument_Sub_Types::model()->count('document_id = :id', array(':id' => $data['Element_OphCoDocument_Document']['single_document_id']));
                if ($count === '0') {
                    ProtectedFile::model()->deleteAll('id = :id', array(':id' => $data['Element_OphCoDocument_Document']['single_document_id']));
                }
            }
        } else {
            if (!empty($data['ProtectedFile']['right_file_content']) && $data['right_file_canvas_modified'] === '1') {
                $rightDocumentName = ProtectedFile::model()->findByPk($element->right_document_id)->name;
                $element->right_document_id = $this->getProtectedFileId($data['ProtectedFile']['right_file_content'], $rightDocumentName);

                $count = OphCoDocument_Sub_Types::model()->count('document_id = :id', array(':id' => $data['Element_OphCoDocument_Document']['right_document_id']));
                if ($count === '0') {
                    ProtectedFile::model()->deleteAll('id = :id', array(':id' => $data['Element_OphCoDocument_Document']['right_document_id']));
                }
            }

            if (!empty($data['ProtectedFile']['left_file_content']) && $data['left_file_canvas_modified'] === '1') {
                $leftDocumentName = ProtectedFile::model()->findByPk($element->left_document_id)->name;
                $element->left_document_id = $this->getProtectedFileId($data['ProtectedFile']['left_file_content'], $leftDocumentName);

                $count = OphCoDocument_Sub_Types::model()->count('document_id = :id', array(':id' => $data['Element_OphCoDocument_Document']['left_document_id']));
                if ($count === '0') {
                    ProtectedFile::model()->deleteAll('id = :id', array(':id' => $data['Element_OphCoDocument_Document']['left_document_id']));
                }
            }
        }
    }

    protected function saveComplexAttributes_Element_OphCoDocument_Document($element, $data, $index)
    {
        foreach (['single_document', 'left_document', 'right_document'] as $document) {
            $document_id = $document.'_id';
            $rotate = $document.'_rotate';
            $protected = ProtectedFile::model()->findByPk($element->{$document_id});
            $rotate_value = \Yii::app()->request->getParam($rotate);
            if ($protected && !is_null($rotate_value)) {
                $protected->rotate = $rotate_value;
                $protected->save();
            }
        }
    }
}
