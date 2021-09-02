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

/**
 * This is the model class for table "ophtrconsent_procedure_extra".
 *
 * The followings are the available columns in table 'ophtrconsent_procedure_extra':
 *
 * @property int $id
 * @property string $name
 */
class OphTrConsent_Extra_Procedure extends BaseActiveRecordVersioned
{
    /**
     * Returns the static model of the specified AR class.
     *
     * @return OphTrConsent_Extra_Procedure the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'ophtrconsent_procedure_extra';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('term, short_format, default_duration', 'required'),
            array('default_duration', 'numerical', 'integerOnly' => true, 'max' => 65535),
            array('term, short_format, snomed_term', 'length', 'max' => 255),
            array('operationNotes', 'validateOpNotes'),
            array('id, term, short_format, default_duration, active, unbooked, opcsCodes, benefits, risks, complications, snomed_code, snomed_term, aliases, operationNotes', 'safe'),
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
            //'operations' => array(self::MANY_MANY, 'ElementOperation', 'operation_procedure_assignment(proc_id, operation_id)'),
            'specialties' => array(self::MANY_MANY, 'Subspecialty', 'proc_subspecialty_assignment(proc_id, subspecialty_id)'),
            'subspecialtySubsections' => array(self::MANY_MANY, 'SubspecialtySubsection', 'proc_subspecialty_subsection_assignment(proc_id, subspecialty_subsection_id)'),
            'opcsCodes' => array(self::MANY_MANY, 'OPCSCode', 'proc_opcs_assignment(proc_id, opcs_code_id)'),
            'extra' => array(self::MANY_MANY, 'Procedure', 'ophtrconsent_procedure_extra(proc_id, proc_extra_id)'),
            'benefits' => array(self::MANY_MANY, 'Benefit', 'procedure_benefit(proc_id, benefit_id)'),
            'risks' => array(self::MANY_MANY, '\OEModule\OphCiExamination\models\OphCiExaminationRisk', 'procedure_risk(proc_id, risk_id)'),
            'complications' => array(self::MANY_MANY, 'Complication', 'procedure_complication(proc_id, complication_id)'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'term' => 'Term',
            'short_format' => 'Short Format',
            'default_duration' => 'Default Duration',
            'opcsCodes.name' => 'OPCS Code',
        );
    }


    public function behaviors()
    {
        return array(
            'LookupTable' => 'LookupTable',
        );
    }


    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria();

        $criteria->compare('id', $this->id, true);
        $criteria->compare('term', $this->term, true);
        $criteria->compare('short_format', $this->short_format, true);
        $criteria->compare('default_duration', $this->default_duration);

        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Get a list of procedures.
     *
     * @param string $term     term to search by
     * @param string $restrict Set to 'booked' or 'unbooked' to restrict results to procedures of that type
     *
     * @return array
     */
    public static function getList($term, $restrict = null)
    {
        $search = "%{$term}%";

        $select = 'term, short_format, id, default_duration';

        $where = '(term like :search or short_format like :search or snomed_term like :search or snomed_code = :term or aliases like :search)';

        if ($restrict == 'unbooked') {
            $where .= ' and unbooked = 1';
        } elseif ($restrict == 'booked') {
            $where .= ' and unbooked = 0';
        }

        $where .= ' and ophtrconsent_procedure_extra.active = 1';

        return Yii::app()->db->createCommand()
            ->select('ophtrconsent_procedure_extra.term as label,proc.id')
            ->from('ophtrconsent_procedure_extra')
            ->where($where, array(
                ':term' => $term,
                ':search' => $search,
            ))
            ->order('term')
            ->queryAll();
    }


    /**
     * Add relation to OphTrOperationnote_ProcedureListOperationElement if it exists.
     */
    protected function afterConstruct()
    {
        $this->addOpNoteElementRelation();

        parent::afterConstruct();
    }



    /**
     * Add relation to OphTrOperationnote_ProcedureListOperationElement if it exists.
     */
    protected function afterFind()
    {
        $this->addOpNoteElementRelation();

        parent::afterFind();
    }

    protected function addOpNoteElementRelation()
    {
        if (isset(Yii::app()->modules['OphTrOperationnote']) && Yii::app()->db->schema->getTable('ophtroperationnote_procedure_element')) {
            $this->metaData->addRelation(
                'operationNotes',
                array(
                    self::MANY_MANY,
                    'ElementType',
                    'ophtroperationnote_procedure_element(procedure_id, element_type_id)',
                )
            );
        }
    }



    /**
     * @param $subspecialtyId
     * @param bool $restrict
     *
     * @return array
     */
    public function getListBySubspecialty($subspecialtyId, $restrict = false)
    {
        $where = '';
        if ($restrict == 'unbooked') {
            $where = ' and unbooked = 1';
        } elseif ($restrict == 'booked') {
            $where = ' and unbooked = 0';
        }
        $procedures = Yii::app()->db->createCommand()
            ->select('ophtrconsent_procedure_extra.id, ophtrconsent_procedure_extra.term')
            ->from('ophtrconsent_procedure_extra')
            ->join('proc_subspecialty_assignment psa', 'psa.ophtrconsent_procedure_extra_id = ophtrconsent_procedure_extra.id')
            ->where('psa.subspecialty_id = :id and proc.active = 1' . $where, array(':id' => $subspecialtyId))
            ->order('display_order, proc.term ASC')
            ->queryAll();

        $data = array();

        foreach ($procedures as $procedure) {
            $data[$procedure['id']] = $procedure['term'];
        }

        return $data;
    }



    public function validateOpNotes($attribute, $params)
    {
        $is_cataract = false;
        $is_biometry = false;

        $count = count($this->$attribute);
        if ($count > 1) {
            //At this moment, only Cataract and Biometry can be saved together
            foreach ($this->$attribute as $attr) {
                $is_cataract =  $attr->class_name === 'Element_OphTrOperationnote_Cataract' ? true : $is_cataract;
                $is_biometry =  $attr->class_name === 'Element_OphTrOperationnote_Biometry' ? true : $is_biometry;
            }

            if ($count != 2 || !$is_cataract || !$is_biometry) {
                $this->addError($attribute, 'Only one Operation Note element (or Cataract and Biometry) per Procedure');
            }
        }
    }


    /**
     * @param $opNoteElementId
     *
     * @return array
     */
    public function getProceduresByOpNote($opNoteElementId)
    {
        $data = array();
        if (isset($this->operationNotes)) {
            $procedures = Yii::app()->db->createCommand()
                ->select('ophtrconsent_procedure_extra.id, ophtrconsent_procedure_extra.term')
                ->from('ophtrconsent_procedure_extra')
                ->join('ophtroperationnote_procedure_element opnote', 'opnote.procedure_id = ophtrconsent_procedure_extra.id')
                ->where('opnote.element_type_id = :id and proc.active = 1', array(':id' => $opNoteElementId))
                ->order('display_order, ophtrconsent_procedure_extra.term ASC')
                ->queryAll();

            foreach ($procedures as $procedure) {
                $data[$procedure['id']] = $procedure['term'];
            }
        }

        return $data;
    }


    /**
     * @param string $prop
     *
     * @return mixed|null
     */
    public function __get($prop)
    {
        $method = 'get_' . $prop;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return parent::__get($prop);
    }


    /**
     * @param string $prop
     *
     * @return bool
     */
    public function __isset($prop)
    {
        $method = 'get_' . $prop;
        if (method_exists($this, $method)) {
            return true;
        }

        return parent::__isset($prop);
    }



    /**
     * @return bool
     * @codingStandardsIgnoreStart
     */
    protected function get_has_benefits()
    {
        return count($this->benefits) > 0;
    }
    // @codingStandardsIgnoreEnd

    /**
     * @return bool
     * @codingStandardsIgnoreStart
     */
    protected function get_has_complications()
    {
        return count($this->complications) > 0;
    }
    // @codingStandardsIgnoreEnd

    /**
     * @return bool
     * @codingStandardsIgnoreStart
     */
    protected function get_has_risks()
    {
        return count($this->risks) > 0;
    }
    // @codingStandardsIgnoreEnd
}
