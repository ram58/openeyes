<?php
/**
 * OpenEyes.
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */

/**
 * This is the model class for table "et_ophtroperationnote_anaesthetic".
 *
 * The followings are the available columns in table 'et_ophtroperationnote_anaesthetic':
 *
 * @property int $id
 * @property int $event_id
 * @property int $anaesthetist_id
 * @property string $anaesthetic_comment
 * @property int $display_order
 *
 * The followings are the available model relations:
 * @property Event $event
 * @property EventType $eventType
 * @property ElementType $element_type
 * @property AnaestheticType[] $anaesthetic_type
 * @property Anaesthetist $anaesthetist
 * @property AnaestheticDelivery $anaesthetic_delivery
 * @property OphTrOperationnote_OperationAnaestheticAgent[] $anaesthetic_agent_assignments
 * @property AnaestheticAgent[] $anaesthetic_agents
 * @property OphTrOperationnote_AnaestheticComplication[] $anaesthetic_complications
 * @property User $witness
 */
class Element_OphTrOperationnote_Anaesthetic extends Element_OpNote
{
    public $service;
    public $surgeonlist;
    public $anaesthetic_types = array();

    /**
     * Returns the static model of the specified AR class.
     *
     * @return Element_OphTrOperationnote_Anaesthetic the static model class
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
        return 'et_ophtroperationnote_anaesthetic';
    }

    public function init()
    {
        $this->anaesthetic_types = AnaestheticType::model()->findAll(array('index' => 'code'));
        parent::init(); // TODO: Change the autogenerated stub
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('event_id, anaesthetist_id, anaesthetic_comment', 'safe'),
            // to not to implement the validation logic 2 times the anaesthetist_id is validated in afterValidate method

            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, event_id, anaesthetist_id, anaesthetic_comment', 'safe', 'on' => 'search'),
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
            'element_type' => array(self::HAS_ONE, 'ElementType', 'id', 'on' => "element_type.class_name='".get_class($this)."'"),
            'eventType' => array(self::BELONGS_TO, 'EventType', 'event_type_id'),
            'event' => array(self::BELONGS_TO, 'Event', 'event_id'),
            'user' => array(self::BELONGS_TO, 'User', 'created_user_id'),
            'usermodified' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
            'anaesthetic_type_assignments' => array(self::HAS_MANY, 'OphTrOperationnote_OperationAnaestheticType', 'et_ophtroperationnote_anaesthetic_id'),
            'anaesthetic_type' => array(self::HAS_MANY, 'AnaestheticType', 'anaesthetic_type_id',
                'through' => 'anaesthetic_type_assignments', ),
            'anaesthetic_delivery_assignments' => array(self::HAS_MANY, 'OphTrOperationnote_OperationAnaestheticDelivery', 'et_ophtroperationnote_anaesthetic_id'),
            'anaesthetic_delivery' => array(self::HAS_MANY, 'AnaestheticDelivery', 'anaesthetic_delivery_id',
                'through' => 'anaesthetic_delivery_assignments', ),
            'anaesthetist' => array(self::BELONGS_TO, 'Anaesthetist', 'anaesthetist_id'),
            'anaesthetic_agent_assignments' => array(self::HAS_MANY, 'OphTrOperationnote_OperationAnaestheticAgent', 'et_ophtroperationnote_anaesthetic_id'),
            'anaesthetic_agents' => array(self::HAS_MANY, 'AnaestheticAgent', 'anaesthetic_agent_id',
                'through' => 'anaesthetic_agent_assignments', ),
            'anaesthetic_complication_assignments' => array(self::HAS_MANY, 'OphTrOperationnote_AnaestheticComplication', 'et_ophtroperationnote_anaesthetic_id'),
            'anaesthetic_complications' => array(self::HAS_MANY, 'OphTrOperationnote_AnaestheticComplications', 'anaesthetic_complication_id',
                'through' => 'anaesthetic_complication_assignments', ),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'event_id' => 'Event',
            'agents' => 'Agents',
            'anaesthetist_id' => 'Given by',
            'anaesthetic_comment' => 'Comments',
            'anaesthetic_type_id' => 'Anaesthetic Type',
            'anaesthetic_delivery_id' => 'Anaesthetic Delivery'
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
        $criteria->compare('event_id', $this->event_id, true);
        $criteria->compare('anaesthetist_id', $this->anaesthetist_id);

        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Should not display other anaesthetic details if the anaesthetic type is general.
     *
     * @return bool
     */
    public function getHidden()
    {
        $ga = Yii::app()->db->createCommand()->select('id')->from('anaesthetic_type')->where('code=:code', array(':code' => 'GA'))->queryScalar();
        $no_anaesthetic = Yii::app()->db->createCommand()->select('id')->from('anaesthetic_type')->where('code=:code', array(':code' => 'NoA'))->queryScalar();

        if( count($this->anaesthetic_type) == 1 && ( $this->anaesthetic_type[0]->id == $ga->id || $this->anaesthetic_type[0]->id == $no_anaesthetic->id ) ) {
            return true;
        }

        return false;
    }

    /**
     * Need to delete associated records.
     *
     * @see CActiveRecord::beforeDelete()
     */
    protected function beforeDelete()
    {
        OphTrOperationnote_OperationAnaestheticAgent::model()->deleteAllByAttributes(array('et_ophtroperationnote_anaesthetic_id' => $this->id));
        OphTrOperationnote_AnaestheticComplication::model()->deleteAllByAttributes(array('et_ophtroperationnote_anaesthetic_id' => $this->id));

        return parent::beforeDelete();
    }

    /**
     * Update the Anaesthetic Agents associated with the element.
     *
     * @param $agent_ids
     *
     * @throws Exception
     */
    public function updateAnaestheticAgents($agent_ids)
    {
        $curr_by_id = array();
        foreach ($this->anaesthetic_agent_assignments as $aa) {
            $curr_by_id[$aa->anaesthetic_agent_id] = $aa;
        }

        if (!empty($agent_ids)) {
            foreach ($agent_ids as $aa_id) {
                if (!isset($curr_by_id[$aa_id])) {
                    $aa = new OphTrOperationnote_OperationAnaestheticAgent();
                    $aa->et_ophtroperationnote_anaesthetic_id = $this->id;
                    $aa->anaesthetic_agent_id = $aa_id;

                    if (!$aa->save()) {
                        throw new Exception('Unable to save anaesthetic agent assignment: '.print_r($aa->getErrors(), true));
                    }
                } else {
                    unset($curr_by_id[$aa_id]);
                }
            }
        }
        foreach ($curr_by_id as $aa) {
            if (!$aa->delete()) {
                throw new Exception('Unable to delete anaesthetic agent assignment: '.print_r($aa->getErrors(), true));
            }
        }
    }

    /**
     * Update the Anaesthetic Type associated with the element.
     *
     * @param $type_ids
     * @throws Exception
     */
    public function updateAnaestheticType($type_ids)
    {
        $curr_by_id = array();
        foreach ($this->anaesthetic_type as $type) {
            $curr_by_id[$type->id] = OphTrOperationnote_OperationAnaestheticType::model()->findByAttributes(array(
                                        'et_ophtroperationnote_anaesthetic_id' => $this->id,
                                        'anaesthetic_type_id' => $type->id
                                    ));
        }

        if (!empty($type_ids)) {
            foreach ($type_ids as $type_id) {
                if (!isset($curr_by_id[$type_id])) {
                    $type = new OphTrOperationnote_OperationAnaestheticType();
                    $type->et_ophtroperationnote_anaesthetic_id = $this->id;
                    $type->anaesthetic_type_id = $type_id;

                    if (!$type->save()) {
                        throw new Exception('Unable to save anaesthetic agent assignment: '.print_r($type->getErrors(), true));
                    }
                } else {
                    unset($curr_by_id[$type_id]);
                }
            }
        }

        foreach ($curr_by_id as $type) {
            if (!$type->delete()) {
                throw new Exception('Unable to delete anaesthetic agent assignment: '.print_r($type->getErrors(), true));
            }
        }
    }

    /**
     * Update the Anaesthetic Delivery associated with the element.
     *
     * @param $delivery_ids
     * @throws Exception
     */
    public function updateAnaestheticDelivery($delivery_ids)
    {

        $curr_by_id = array();
        foreach ($this->anaesthetic_delivery as $delivery) {
            $curr_by_id[$delivery->id] = OphTrOperationnote_OperationAnaestheticDelivery::model()->findByAttributes(array(
                                                'et_ophtroperationnote_anaesthetic_id' => $this->id,
                                                'anaesthetic_delivery_id' => $delivery->id
                                            ));
        }

        if (!empty($delivery_ids)) {
            foreach ($delivery_ids as $delivery_id) {

                if (!isset($curr_by_id[$delivery_id])) {
                    $delivery = new OphTrOperationnote_OperationAnaestheticDelivery();
                    $delivery->et_ophtroperationnote_anaesthetic_id = $this->id;
                    $delivery->anaesthetic_delivery_id = $delivery_id;

                    if (!$delivery->save()) {
                        throw new Exception('Unable to save anaesthetic agent assignment: '.print_r($delivery->getErrors(), true));
                    }
                } else {
                    unset($curr_by_id[$delivery_id]);
                }
            }
        }

        foreach ($curr_by_id as $delivery) {
            if (!$delivery->delete()) {
                throw new Exception('Unable to delete anaesthetic agent assignment: '.print_r($delivery->getErrors(), true));
            }
        }
    }

    /**
     * Update the complications assigned to this element.
     *
     * @param int[] $complication_ids
     *
     * @throws Exception
     */
    public function updateComplications($complication_ids)
    {
        $curr_by_id = array();

        foreach ($this->anaesthetic_complication_assignments as $ca) {
            $curr_by_id[$ca->anaesthetic_complication_id] = $ca;
        }

        if (!empty($complication_ids)) {
            foreach ($complication_ids as $c_id) {
                if (!isset($curr_by_id[$c_id])) {
                    $ca = new OphTrOperationnote_AnaestheticComplication();
                    $ca->et_ophtroperationnote_anaesthetic_id = $this->id;
                    $ca->anaesthetic_complication_id = $c_id;

                    if (!$ca->save()) {
                        throw new Exception('Unable to save complication assignment: '.print_r($ca->getErrors(), true));
                    }
                } else {
                    unset($curr_by_id[$c_id]);
                }
            }
        }

        foreach ($curr_by_id as $ca) {
            if (!$ca->delete()) {
                throw new Exception('Unable to delete complication assignment: '.print_r($ca->getErrors(), true));
            }
        }
    }

    // TODO: This should use the standard surgeons method
    public function getSurgeons()
    {
        if (!$this->surgeonlist) {
            $criteria = new CDbCriteria();
            $criteria->compare('active', true);
            $criteria->compare('is_surgeon', 1);
            $criteria->order = 'first_name,last_name asc';

            $this->surgeonlist = User::model()->findAll($criteria);
        }

        return $this->surgeonlist;
    }

    /**
     * Get ids of anaesthetic complications in use by the element.
     */
    public function getAnaestheticComplicationValues()
    {
        $complication_values = array();

        foreach ($this->anaesthetic_complication_assignments as $complication_assignment) {
            $complication_values[] = $complication_assignment->anaesthetic_complication_id;
        }

        return $complication_values = array();
    }

    /**
     * @param $type_name
     * @return bool|null
     */
    public function hasAnaestheticType($type_name)
    {
        $type = AnaestheticType::model()->findByAttributes(array('name' => $type_name));

        if(!$type){
            return null;
        }

        foreach($this->anaesthetic_type as $anaesthetic_type){
            if($anaesthetic_type->id == $type->id){
                return true;
            }
        }

        return false;
    }

    /**
         * @return string
         */
    public function getAnaestheticTypeDisplay()
        {
            return implode(', ', $this->anaesthetic_type);
        }

    public function afterValidate()
    {
        if( !count($this->anaesthetic_type_assignments)){
            $this->addError('anaesthetic_type', 'Type cannot be empty.');
        }

        $type_ga =  AnaestheticType::model()->findByAttributes(array('code' => 'GA'));
        $type_noA =  AnaestheticType::model()->findByAttributes(array('code' => 'NoA'));
        $type_la =  AnaestheticType::model()->findByAttributes(array('code' => 'LA'));

        $assignments_count = count($this->anaesthetic_type_assignments);
        $delivery_method_count = count($this->anaesthetic_delivery_assignments);

        // GA is selected,
        // delivery method should be other (all other delivery options un-checked)
        // given by should be Anaesthetist
        if( ($assignments_count == 1) && ($this->anaesthetic_type_assignments[0]->anaesthetic_type_id == $type_ga->id) ){

            $anaesthetist_delivery_other = AnaestheticDelivery::model()->findByAttributes(array('name' => 'Other'));

            if($delivery_method_count != 1 || $this->anaesthetic_delivery_assignments[0]->anaesthetic_delivery_id != $anaesthetist_delivery_other->id){
                $this->addError('anaesthetic_delivery', 'If anaesthetic Type is "GA" than LA Delivery Methods must only be "Other"');
            }

            $anaesthetist_type_anaesthetist = Anaesthetist::model()->findByAttributes(array('name' => 'Anaesthetist'));

            if($this->anaesthetist_id != $anaesthetist_type_anaesthetist->id){
                $this->addError('Anaesthetist', 'If anaesthetic Type is "GA" than Given by must be "Anaesthetist"');
            }
        }

        //No Anaesthetic selected
        //delivery option should be empty
        //anaesthetist_id should be null
        if( ($assignments_count == 1) && ($this->anaesthetic_type_assignments[0]->anaesthetic_type_id == $type_noA->id) ){
            if($delivery_method_count != 0){
                $this->addError('anaesthetic_delivery', 'If anaesthetic Type is "No Anaesthetic" than no LA Delivery Methods should be selected');
            }

            if($this->anaesthetist_id != null){
                $this->addError('Anaesthetist', 'If anaesthetic Type is "No Anaesthetic" than no "Given by" should be selected.');
            }
        }

        //Anything else seleted than GA(alone) or No Anaesthetic
        if( $assignments_count > 1 || ( $assignments_count == 1 && !in_array($this->anaesthetic_type_assignments[0]->anaesthetic_type_id, array($type_ga->id, $type_noA->id))) ){
            if($this->anaesthetic_type_assignments[0]->anaesthetic_type_id === $type_la->id && !count($this->anaesthetic_delivery_assignments)){
                $this->addError('anaesthetic_delivery', 'LA Delivery Methods cannot be empty.');
            }

            if($this->anaesthetist_id == null){
                $this->addError('Anaesthetist', 'Given by cannot be empty.');
            }
        }

        parent::afterValidate(); // TODO: Change the autogenerated stub
    }

    public function behaviors()
    {
        return array(
            'OeAnaestheticFormat' => array(
                'class' => 'application.behaviors.OeAnaestheticFormat'
            ),
        );
    }
}
