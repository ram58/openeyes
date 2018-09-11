<?php
/**
 * OpenEyes.
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */
?>
<main class="oe-full-main admin-main">
	<h2><?php echo $cbs->id ? 'Edit' : 'Add'?> commissioning body type</h2>
	<?php echo $this->renderPartial('_form_errors', array('errors' => $errors))?>
	<?php
    $form = $this->beginWidget('BaseEventTypeCActiveForm', array(
        'id' => 'adminform',
        'enableAjaxValidation' => false,
        'focus' => '#username',
        'layoutColumns' => array(
            'label' => 2,
            'field' => 5,
        ),
    ))?>

    <div class="cols-5">
        <table class="standard cols-full">
            <colgroup>
                <col class="cols-3">
                <col class="cols-5">
            </colgroup>
            <tbody>
            <tr>
                <td>Name</td>
                <td> <?php echo CHtml::activeTextField($cbs, 'name', ['class' => 'cols-full',
                        'autocomplete'=>Yii::app()->params['html_autocomplete'], 'size' => '50']); ?> </td>
            </tr>
            <tr>
                <td><?php echo$cbs->getAttributeLabel('shortname') ?></td>
                <td> <?php echo CHtml::activeTextField($cbs, 'shortname', ['class' => 'cols-full',
                        'autocomplete'=>Yii::app()->params['html_autocomplete'], 'size' => '10']); ?> </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="5">
                    <?php echo CHtml::button('Save', ['class' => 'button large primary event-action',
                        'name' => 'save', 'type' => 'submit', 'id' => 'et_save']); ?>
                    <?php echo CHtml::button('Cancel', ['class' => 'warning button large primary event-action',
                        'data-uri' => '/admin/commissioning_body_service_types', 'type' => 'submit', 'name' => 'cancel', 'id' => 'et_cancel']); ?>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>

	<?php $this->endWidget()?>
</main>
