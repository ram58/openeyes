<?php
/**
 * OpenEyes.
 *
 * (C) OpenEyes Foundation, 2014
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2014, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */
$layoutColumns = $form->layoutColumns;
$form->layoutColumns = array('label' => 3, 'field' => 9);
?>
  <div class="element-fields full-width">
    <div class="eyedraw-row trabectome cols-11 flex-layout col-gap">
      <div class="cols-6">
            <?php $this->renderPartial($element->form_view . '_OEEyeDraw', array(
              'element' => $element,
              'form' => $form,
          )); ?>
      </div>
      <div class="cols-6">
            <?php $this->renderPartial($element->form_view . '_OEEyeDraw_fields', array(
              'form' => $form,
              'element' => $element,
          )); ?>
      </div>
    </div>
  </div>
<?php $form->layoutColumns = $layoutColumns; ?>