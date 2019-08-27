<?php
/**
 * (C) OpenEyes Foundation, 2019
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2019, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */
?>

<?=\CHtml::errorSummary(
    $model,
    null,
    null,
    ["class" => "alert-box alert with-icon"]
); ?>

<div class="cols-5">
    <table class="standard cols-full">
        <colgroup>
            <col class="cols-3">
            <col class="cols-5">
        </colgroup>
        <tbody>
            <tr>
                <td>Name</td>
                <td class="cols-full">
                <?=\CHtml::activeTextField(
                    $model,
                    'name',
                    ['class' => 'cols-full']
                ); ?>
                <?=\CHtml::activeHiddenField(
                    $model,
                    'subspecialty_id',
                    [ 'value' => $s_id ]
                ); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?= \OEHtml::submitButton() ?>
    
    <?php if ($model->id) {
        echo \OEHtml::Button("Delete", [
            'id' => 'ss_delete',
            'data-id' => $model->id,
            'data-s_id' => $s_id
        ]);
    } ?>

    <?= \OEHtml::cancelButton("Cancel", [
        'data-uri' => '/oeadmin/subspecialtySubsections/view?subspecialty_id=' . $s_id,
    ]) ?>
</div>
<script>
    $('#ss_delete').click( event => {
        let alert = new OpenEyes.UI.Dialog.Confirm({
            title: 'Delete Subsection',
            content: 'Are you sure you want to delete this subsection?'
        });
        alert.content.on('click', '.ok', (sub_event, main_event=event) => {
            let params = main_event.target.dataset;
            window.location.href = '/oeadmin/subspecialtySubsections/delete?id=' + params['id'] + '&subspecialty_id=' + params['s_id'];
        });

        event.preventDefault();
        alert.open();
    });
</script>
