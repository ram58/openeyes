<?php $cs = Yii::app()->clientScript; ?>
<?php $cs->registerCoreScript('jquery')?>
<?php $cs->registerCoreScript('jquery.ui')?>
<?php $cs->registerCSSFile($cs->getCoreScriptUrl().'/jui/css/base/jquery-ui.css', 'screen')?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/jquery.watermark.min.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/mustache.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/libs/uri-1.10.2.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/waypoints.min.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/waypoints-sticky.min.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/libs/modernizr-2.0.6.min.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/jquery.printElement.min.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/jquery.hoverIntent.min.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('/js/jquery.autosize.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/print.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/buttons.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/util.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/dialogs.js'))?>
<?php $cs->registerScriptFile(Yii::app()->createUrl('js/script_new.js'))?>