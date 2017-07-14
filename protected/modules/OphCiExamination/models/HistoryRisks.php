<?php
/**
 * OpenEyes
 *
 * (C) OpenEyes Foundation, 2017
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2017, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */


namespace OEModule\OphCiExamination\models;

/**
 * Class HistoryRisks
 * @package OEModule\OphCiExamination\models
 */
class HistoryRisks extends \BaseEventTypeElement
{
    protected $auto_update_relations = true;
    protected $auto_validate_relations = true;

    public $widgetClass = 'OEModule\OphCiExamination\widgets\HistoryRisks';
    protected $default_from_previous = true;
    protected $errorExceptions = array(
        'OEModule_OphCiExamination_models_HistoryRisks_no_risks_date' => 'OEModule_OphCiExamination_models_HistoryRisks_no_risks',
        'OEModule_OphCiExamination_models_HistoryRisks_entries' => 'OEModule_OphCiExamination_models_HistoryRisks_entry_table'

    );

    public function tableName()
    {
        return 'et_ophciexamination_history_risks';
    }

    public function behaviors()
    {
        return array(
            'PatientLevelElementBehaviour' => 'PatientLevelElementBehaviour',
        );
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('event_id, entries', 'safe'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'event' => array(self::BELONGS_TO, 'Event', 'event_id'),
            'user' => array(self::BELONGS_TO, 'User', 'created_user_id'),
            'usermodified' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
            'entries' => array(
                self::HAS_MANY,
                'OEModule\OphCiExamination\models\HistoryRisksEntry',
                'element_id',
            ),
            'not_checked' => array(
                self::HAS_MANY,
                'OEModule\OphCiExamination\models\HistoryRisksEntry',
                'element_id',
                'condition' => 'has_risk is null'
            ),
            'present' => array(
                self::HAS_MANY,
                'OEModule\OphCiExamination\models\HistoryRisksEntry',
                'element_id',
                'condition' => 'has_risk = true'
            ),
            'not_present' => array(
                self::HAS_MANY,
                'OEModule\OphCiExamination\models\HistoryRisksEntry',
                'element_id',
                'condition' => 'has_risk = 0'
            )
        );
    }

    /**
     * @param HistoryRisks $element
     */
    public function loadFromExisting($element)
    {
        $entries = array();
        foreach ($element->entries as $entry) {
            $new = new HistoryRisksEntry();
            $new->loadFromExisting($entry);
            $entries[] = $new;
        }
        $this->entries = $entries;
        $this->originalAttributes = $this->getAttributes();
    }

    private $required_risks = null;
    /**
     * @return OphCiExaminationRisk[]
     */
    public function getRequiredRisks()
    {
        if ($this->required_risks === null) {
            $this->required_risks = OphCiExaminationRisk::model()->findAllByAttributes(array('required' => true));
        }
        return $this->required_risks;
    }

    /**
     * @return array
     */
    public function getMissingRequiredRisks()
    {
        $current_ids = array_map(function ($e) { return $e->risk_id; }, $this->entries);
        $missing = array();
        foreach ($this->getRequiredRisks() as $required) {
            if (!in_array($required->id, $current_ids)) {
                $entry = new HistoryRisksEntry();
                $entry->risk_id = $required->id;
                $missing[] = $entry;
            }
        }
        return $missing;
    }

    /**
     * check no risks date or there are entries
     *
     * @inheritdoc
     */
    public function afterValidate()
    {
        if (!$this->no_risks_date && !$this->entries) {
            $this->addError('no_risks_date', 'Please confirm the patient has no risks.');
        }
        $risk_ids = array_map(function($e) { return $e->risk_id; }, $this->entries);
        $missing_required = array();
        foreach ($this->getRequiredRisks() as $required) {
            if (!in_array($required->id, $risk_ids)) {
                $missing_required[] = $required;
            }
        }
        if (count($missing_required)) {
            $this->addError('entries', 'Missing required risks: ' . implode(', ', $missing_required));
        }

        parent::afterValidate();
    }

    /**
     * Get list of available risks for this element (ignoring required risks)
     */
    public function getRiskOptions()
    {
        $force = array();
        foreach ($this->entries as $entry) {
            $force[] = $entry->risk_id;
        }
        return OphCiExaminationRisk::model()->activeOrPk($force)->findAllByAttributes(array('required' => false));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = array();
        foreach (array('present', 'not_checked', 'not_present') as $cat) {
            $result[] = $this->getAttributeLabel($cat) . ': ' . implode(', ', $this->$cat);
        }
        return implode(' // ', $result);
    }

    /**
     * @param string $category
     * @return string
     */
    public function getEntriesDisplay($category = 'entries')
    {
        if (!in_array($category, array('present', 'not_checked', 'not_present'))) {
            $category  = 'entries';
        }
        return implode(', ', array_map(function($e) { return $e->getDisplay(); }, $this->$category));
    }


    /**
     * @param $attribute
     * @inheritdoc
     */
    protected function errorAttributeException($attribute, $message)
    {
        if ($attribute === \CHtml::modelName($this) . '_entries') {
            // TODO: handle highlighting the "other" text field once that validation is in place.
            if (preg_match('/^(\d+)/', $message, $match) === 1) {
                return $attribute .'_' . ($match[1]-1) . '_risk_id';
            }
        }
        return parent::errorAttributeException($attribute, $message);
    }
}