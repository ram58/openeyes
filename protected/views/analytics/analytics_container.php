

<?php $this->renderPartial('//analytics/analytics_header', array());?>

<main class="oe-analytics flex-layout flex-top ">
    <?php $this->renderPartial('//analytics/analytics_sidebar',
        array('specialty'=>$specialty)
    ); ?>
    <div class="analytics-charts">
        <?php if ($specialty === 'Cataract'){ ?>
        <div class="mdl-layout__container" style="width: 60%">
        <?php  $this->renderPartial('//analytics/analytics_cataract',
            array('event_list'=> $patient_list)); ?>
        </div>
        <?php } else {
            $this->renderPartial('//analytics/analytics_service',
                array('service_data'=>$service_data));

            $this->renderPartial('//analytics/analytics_clinical',
                array('clinical_data'=>$clinical_data));

            $this->renderPartial('//analytics/analytics_custom',
                array('custom_data'=> $custom_data));
        }?>
    </div>

    <?php
        if ($specialty !== 'Cataract'){
            $this->renderPartial('//analytics/analytics_drill_down_list', array(
              'patient_list' => $patient_list
            ));
        }
    ?>

</main>

<script >
    $(document).ready(function() {
        OpenEyes.Dash.init('#pcr-risk-grid');
        OpenEyes.Dash.addBespokeReport('/report/ajaxReport?report=PcrRisk&template=analytics', null, 8);
    });

</script>