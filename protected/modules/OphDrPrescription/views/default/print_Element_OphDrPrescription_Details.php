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
?>
<?php

$copy = $data['copy'];

$header_text = null;
$footer_text = null;

$allowed_tags = '<b><br><div><em><h1><h2><h3><h4><h5><h6><hr><i><ul><ol><li><p><small><span><strong><sub><sup><u><wbr><table><thead><tbody><tfoot><tr><th><td><colgroup>';

$header_param = Yii::app()->params['prescription_boilerplate_header'];
if ($header_param !== null) {
    $header_text = strip_tags($header_param, $allowed_tags);
}

$footer_param = Yii::app()->params['prescription_boilerplate_footer'];
if ($footer_param !== null) {
    $footer_text = strip_tags($footer_param, $allowed_tags);
}

?>

<?php
$firm = $element->event->episode->firm;
$cost_code = $firm->cost_code ? " ($firm->cost_code)" : '';
$consultantName = $firm->consultant ? ($firm->consultant->getFullName() . $cost_code) : 'None';
$subspecialty = $firm->serviceSubspecialtyAssignment->subspecialty;
?>

<?php if (!$data['fpten']): ?>
  <?php if ($header_text !== null): ?>
      <div class="clearfix"><?= $header_text ?></div>
  <?php endif; ?>

    <table class="borders prescription_header">
        <tr>
            <th>Patient Name</th>
            <td><?=$this->patient->fullname ?> (<?=$this->patient->gender ?>)</td>
            <th>Hospital Number</th>
            <td><?=$this->patient->hos_num ?></td>
        </tr>
        <tr>
            <th>Date of Birth</th>
            <td><?=$this->patient->NHSDate('dob') ?> (<?=$this->patient->age ?>)</td>
            <th><?=Yii::app()->params['nhs_num_label'] ?> Number</th>
            <td><?=$this->patient->getNhsnum() ?></td>
        </tr>
        <tr>
            <th>Consultant</th>
            <td><?=$consultantName ?></td>
            <th>Service</th>
            <td><?=$subspecialty->name ?></td>
        </tr>
        <tr>
            <th>Patient's address</th>
            <td colspan="3"><?=$this->patient->getSummaryAddress(', ') ?></td>
        </tr>
    </table>

    <div class="spacer"></div>

    <h2>Allergies</h2>
    <table class="borders">
        <tr>
            <td><?=$this->patient->getAllergiesString(); ?></td>
        </tr>
    </table>

    <div class="spacer"></div>

<?php
$items_data = $this->groupItems($element->items);
foreach ($items_data as $group => $items) { ?>
    <b>
        <?php
        $group_name = OphDrPrescription_DispenseCondition::model()->findByPk($group)->name;
        echo $group_name; ?>
    </b>
    <table class="borders prescription_items">
        <thead>
        <tr>
            <th class="prescriptionLabel">Prescription details</th>
            <th>Dose</th>
            <th>Route</th>
            <th>Freq.</th>
            <th>Duration</th>
            <?php if (strpos($group_name, 'Hospital') !== false) { ?>
                <th>Dispense Location</th>
                <th>Dispensed</th>
                <th>Checked Status</th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($items as $item) {
            ?>
            <tr class="prescriptionItem<?=$this->patient->hasDrugAllergy($item->drug_id) ? ' allergyWarning' : '';?> ">
                <td class="prescriptionLabel"><?=$item->drug->label; ?></td>
                <td><?=is_numeric($item->dose) ? ($item->dose . " " . $item->drug->dose_unit) : $item->dose ?></td>
                <td><?=$item->route->name ?><?php if ($item->route_option) {
                        echo ' (' . $item->route_option->name . ')';
                    } ?></td>
                <td><?=$item->frequency->long_name; ?></td>
                <td><?=$item->duration->name ?></td>
                <?php if (strpos($group_name, 'Hospital') !== false) { ?>
                    <td><?=$item->dispense_location->name ?></td>
                    <td></td>
                    <td></td>
                <?php } ?>
            </tr>
            <?php foreach ($item->tapers as $taper) { ?>
                <tr class="prescriptionTaper">
                    <td class="prescriptionLabel">then</td>
                    <td><?=is_numeric($taper->dose) ? ($taper->dose . " " . $item->drug->dose_unit) : $taper->dose ?></td>
                    <td>-</td>
                    <td><?= $taper->frequency->long_name ?></td>
                    <td><?=$taper->duration->name ?></td>
                    <?php if (strpos($group_name, "Hospital") !== false) { ?>
                        <td></td>
                        <td>-</td>
                        <td>-</td>
                    <?php } ?>
                </tr>
                <?php
            }

            if (strlen($item->comments) > 0) { ?>
                <tr class="prescriptionComments">
                    <td class="prescriptionLabel">Comments:</td>
                    <td colspan="<?=strpos($group_name, "Hospital") !== false ? 7 : 4 ?>">
                        <i><?= \CHtml::encode($item->comments); ?></i></td>
                </tr>
            <?php }
        } ?>
        </tbody>
    </table>
<?php } ?>
    <div class="spacer"></div>

    <h2>Comments</h2>
    <table class="borders">
        <tr>
            <td><?=$element->comments ? $element->textWithLineBreaks('comments') : '&nbsp;' ?></td>
        </tr>
    </table>

    <div class="spacer"></div>
<?php if (!$data['copy'] && $site_theatre = $this->getSiteAndTheatreForLatestEvent()) { ?>
    <table class="borders done_bys">
        <tr>
            <th>Site</th>
            <td><?=$site_theatre->site->name ?></td>
            <?php if ($site_theatre->theatre) { ?>
                <th>Theatre</th>
                <td><?=$site_theatre->theatre->name ?></td>
            <?php } ?>
        </tr>
    </table>
    <div class="spacer"></div>
<?php } ?>
    <table class="borders done_bys">
        <tr>
            <th>Prescribed by</th>
            <td><?=$element->usermodified->fullname ?><?php if ($element->usermodified->registration_code) echo ' (' . $element->usermodified->registration_code . ')' ?>
            </td>
            <th>Date</th>
            <td><?=$element->NHSDate('last_modified_date') ?>
            </td>
        </tr>
        <tr class="handWritten">
            <th>Clinical Checked by</th>
            <td>&nbsp;</td>
            <th>Date</th>
            <td>&nbsp;</td>
        </tr>
    </table>

  <?php if ($footer_text !== null): ?>
      <div><?= $footer_text ?></div>
  <?php endif; ?>
<?php else: ?>
<div class="fpten-age">
    <?= $this->patient->getAge() . 'y' ?>
</div>
<div class="fpten-dob">
    <?= Helper::convertDate2Short($this->patient->dob) ?>
</div>
<div class="fpten-patient-details">
    <?=$this->patient->fullname ?>
  <br/><br/>
    <?= $this->patient->contact->address->address1 ?>
    <?= $this->patient->contact->address->address2 ? '<br/>' : null ?>
    <?= $this->patient->contact->address->address2 ?><br/>
    <?= $this->patient->contact->address->city ?><br/>
    <?= $this->patient->contact->address->county ?>
</div>
<div class="fpten-postcode-nhs">
  <br/><br/><br/>
    <?= $this->patient->contact->address->address2 ? '<br/>' : null ?>
  <br/>
  <br/>
    <?= $this->patient->contact->address->postcode ?>
  <br/>
  <br/>
    <?= $this->patient->nhs_num ?>
</div>
<br/>
<br/>
<div class="fpten-prescriber">
  HOSPITAL PRESCRIBER
</div>
<br/>
<div class="fpten-prescription-list">
    <?php
    foreach ($this->groupItems($element->items) as $group => $items):
        $group_name = OphDrPrescription_DispenseCondition::model()->findByPk($group)->name;
        if ($group_name === 'Print to FP10'):
            foreach ($items as $item):
                ?>
                <?= $item->drug->label ?>
              <br/>
                <?= is_numeric($item->dose) ? ($item->dose . ' ' . $item->drug->dose_unit . ' ' . $item->frequency->long_name) : $item->dose . ' ' . $item->frequency->long_name ?>
              <br/><br/>
              ----------------------------------------------------
            <?php endforeach;
        endif;
    endforeach;?>
</div>
<span class="fpten-prescriber-code">HP</span>
<div class="fpten-date">
    <?= date('d/m/y') ?>
</div>
<div class="fpten-site">
    <?= $this->firm->name ?>
  <br/><br/>
    <?= $this->site->name ?>
  <br/>
    <?= $this->site->contact->address->address1 ?>
    <?= $this->site->contact->address->address2 ? '<br/>' : null ?>
    <?= $this->site->contact->address->address2 ?>
  <br/>
    <?= $this->site->contact->address->city ?>
    <?= $this->site->contact->address->county ? '<br/>' : null ?>
    <?= $this->site->contact->address->county ?>
  <br/>
    <?= $this->site->contact->primary_phone ?>
</div>
<div class="fpten-site-code">
  <span class="fpten-trust-code"><?= $data['user']->registration_code ?></span>
  <br/>
  <br/>
  <br/>
    <?= $this->site->contact->address->postcode ?>
  <br/>
  <br/>
    <?= $this->site->institution->remote_id ?>
</div>
<span class="fpten-site-prescriber-code">HP</span>
<?php endif; ?>
