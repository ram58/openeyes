<?php
/**
 * (C) OpenEyes Foundation, 2014
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2014, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */
use OEModule\OphCiExamination\models as ExamModels;
use OEModule\OphCiPhasing\models as PhasingModels;

class OphCiExamination_Episode_IOPHistory extends \EpisodeSummaryWidget
{
    public $collapsible = true;
    public $openOnPageLoad = true;

    public function run()
    {
        $chart = $this->createWidget('FlotChart', array('chart_id' => 'iop-history-chart'))
            ->configureChart(array(
                'colors' => array('#4daf4a', '#984ea3', '#4daf4a', '#984ea3'),
            ))
            ->configureXAxis(array('mode' => 'time'))
            ->configureYAxis('mmHg', array(
                'min' => 0,
                'max' => 80,
            ))
            ->configureSeries('RE', array(
                'points' => array('show' => true),
                'lines' => array(
                    'show' => true,
                ),
            ))
            ->configureSeries('LE', array(
                'points' => array('show' => true),
                'lines' => array('show' => true),
            ))
            ->configureSeries('Target RE', array(
                'colors' => array('#fff', '#fff', '#fff'),
                'points' => array('show' => true),
                'dashes' => array(
                    'show' => true,
                    'style' => array(6),
                ),
            ))
            ->configureSeries('Target LE', array(
                'points' => array('show' => true),
                'dashes' => array(
                    'show' => true,
                    'style' => array(6),
                ),
            ));

        $events = $this->event_type->api->getEvents($this->episode->patient, false);

        foreach ($events as $event) {
            if (($iop = $event->getElementByClass('OEModule\OphCiExamination\models\Element_OphCiExamination_IntraocularPressure'))) {
                $timestamp = Helper::mysqlDate2JsTimestamp($event->event_date);
                $this->addIop($chart, $iop, $timestamp, 'right');
                $this->addIop($chart, $iop, $timestamp, 'left');
            }
        }
        
        $plan = $this->event_type->api->getLatestElement(
            'OEModule\OphCiExamination\models\Element_OphCiExamination_OverallManagementPlan',
            $this->episode->patient,
            false
        );
       
        if ($plan) {
            $this->addTargetIop($chart, $plan, 'right');
            $this->addTargetIop($chart, $plan, 'left');
        }

        $this->render('OphCiExamination_Episode_IOPHistory', array('chart' => $chart));
    }

    public function run_oescape($widgets_no = 1){
        $this->render('OphCiExamination_OEscape_IOPHistory');
    }

    protected function addIop(\FlotChart $chart, ExamModels\Element_OphCiExamination_IntraocularPressure $iop, $timestamp, $side)
    {
        if (($reading = $iop->getReading($side))) {
            $seriesName = strtoupper($side[0]).'E';
            $chart->addPoint($seriesName, $timestamp, $reading, "{$reading} mmHg");
        }
    }

    protected function addTargetIop(\FlotChart $chart, ExamModels\Element_OphCiExamination_OverallManagementPlan $plan, $side)
    {
        if (($target = $plan->{"{$side}_target_iop"})) {
            $seriesName = 'Target '.strtoupper($side[0]).'E';
            $chart->addPoint($seriesName, $chart->getXMin(), $target->name, "{$target->name} mmHg");
            $chart->addPoint($seriesName, $chart->getXMax(), $target->name, "{$target->name} mmHg");
        }
    }

    //Currently only gets value for examination data.
    public function getIOPData(){
        $iop_data_list = array('right'=>array(), 'left'=>array());
        $events = $this->event_type->api->getEvents($this->patient, false);
        foreach ($events as $event) {
            if (($iop = $event->getElementByClass('OEModule\OphCiExamination\models\Element_OphCiExamination_IntraocularPressure'))) {
                $timestamp = Helper::mysqlDate2JsTimestamp($event->event_date);
                foreach (['left', 'right'] as $side) {
                    if ($reading = $iop->getReading($side)){
                        array_push($iop_data_list[$side], array('x'=>$timestamp, 'y'=>(float)$reading));
                    }
                }
            }
        }
        foreach (['left', 'right'] as $side){
            usort($iop_data_list[$side], function($item1, $item2){
                if ($item1['x'] == $item2['x']) return 0;
                return $item1['x'] < $item2['x'] ? -1 : 1;
            });
        }
        return $iop_data_list;
    }

    //Gets values for examination and phasing data
    public function getPlotlyIOPData(){

			$iop_data_list = array(
				'examination'=>array(
					'right'=>array(
						'event_id'=>'',
						'x'=>'',
						'y'=>''),
					'left'=>array(
						'event_id'=>'',
						'x'=>'',
						'y'=>'')),
				'phasing'=>array(
					'right'=>array(
						'event_id'=>'',
						'x'=>'',
						'y'=>''),
					'left'=>array(
						'event_id'=>'',
						'x'=>'',
						'y'=>'')));

			$exam_events = Event::model()->getEventsOfTypeForPatient($this->event_type, $this->patient);
			$phasing_events = Event::model()->getEventsOfTypeForPatient(EventType::model()->find('name=:name', array(':name'=>"Phasing")), $this->patient);

			foreach ($phasing_events as $phasing_event) {
				$iop = $phasing_event->getElementByClass('Element_OphCiPhasing_IntraocularPressure');

				if ($iop) {
					$timestamp = Helper::mysqlDate2JsTimestamp($phasing_event->event_date);

					foreach (['left', 'right'] as $side) {
						$phasing_readings = $iop->getReadings($side);

						foreach ($phasing_readings as $phasing_reading) {

							if ($phasing_reading) {
								array_push(
									$iop_data_list['phasing'][$side],
									array(
										'event_id' => $phasing_event->id,
										'x' => $timestamp,
										'y' => $phasing_reading));
							}
						}
					}
				}
			}

			foreach ($exam_events as $exam_event) {
				$iop = $exam_event->getElementByClass('OEModule\OphCiExamination\models\Element_OphCiExamination_IntraocularPressure');

				if ($iop) {
					$timestamp = Helper::mysqlDate2JsTimestamp($exam_event->event_date);

					foreach (['left', 'right'] as $side) {
						$exam_readings = $iop->getReadings($side);

						if(count($exam_readings) > 0) {
							foreach ($exam_readings as $exam_reading) {
								if ($exam_reading) {
									array_push(
										$iop_data_list['examination'][$side],
										array(
											'event_id' => $exam_event->id,
											'x' => $timestamp,
											'y' => $exam_reading));
								}
							}
						}
					}
				}
			}

/*      foreach (['left', 'right'] as $side){
        usort($iop_data_list[$side], function($item1, $item2){
          if ($item1['x'] == $item2['x']) return 0;
          return $item1['x'] < $item2['x'] ? -1 : 1;
        });
        foreach ($iop_data_list[$side] as $item){
          $iop_plotly_list[$side]['x'][] = $item['x'];
          $iop_plotly_list[$side]['y'][] = $item['y'];
        }
      }*/
      //return $iop_plotly_list;

			return $iop_data_list;
    }

    public function getTargetIOP(){
      //set the default value of iop target
        $iop_target = array('right'=>0, 'left'=>0);
        $plan = $this->event_type->api->getLatestElement(
            'OEModule\OphCiExamination\models\Element_OphCiExamination_OverallManagementPlan',
            $this->patient,
            false
        );
        if ($plan) {
            foreach (['left', 'right'] as $side) {
                if (($target = $plan->{$side."_target_iop"})) {
                    $iop_target[$side] = (float)$target->name;
                }
            }
        }
        return $iop_target;
    }

}
