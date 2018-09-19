<?php
/**
 * @var TrialController $this
 *
 * @var Trial $trial
 * @var TrialPermission $permission
 * @var CActiveDataProvider $dataProvider
 * @var bool $renderTreatmentType
 * @var string $title
 * @var int $sort_by
 * @var int $sort_dir
 */
?>
<div class="report-summary row divider">
    <?php
    $dataProvided = $dataProvider->getData();
    $items_per_page = $dataProvider->getPagination()->getPageSize();
    $page_num = $dataProvider->getPagination()->getCurrentPage();
    $from = ($page_num * $items_per_page) + 1;
    $to = min(($page_num + 1) * $items_per_page, $dataProvider->totalItemCount);
    ?>
  <h2>
      <?php echo $title; ?>: viewing <?php echo $from; ?> - <?php echo $to; ?>
    of <?php echo $dataProvider->totalItemCount ?>
  </h2>

  <table id="patient-grid" class="standard">
    <thead>
    <tr>
        <?php
        $columns = array(
            '',
            'Name',
            'Gender',
            'Age',
            'Ethnicity',
            'External Reference',
        );


        $sortableColumns = array('Name', 'Gender', 'Age', 'Ethnicity', 'External Reference');

        if ($trial->trialType->code === TrialType::INTERVENTION_CODE && !$trial->is_open && $renderTreatmentType) {
            $columns[] = 'Treatment Type';
            $sortableColumns[] = 'Treatment Type';
        }

        $columns[] = '';

        foreach ($columns as $i => $field): ?>
          <th id="patient-grid_c<?php echo $i; ?>">
              <?php
              if (in_array($field, $sortableColumns, true)) {
                  $new_sort_dir = ($i === $sort_by) ? 1 - $sort_dir : 0;
                  $sort_symbol = '';
                  if ($i === $sort_by) {
                      $sort_symbol = $sort_dir === 1 ? '&#x25BC;' /* down arrow */ : '&#x25B2;'; /* up arrow */
                  }

                  echo CHtml::link(
                      $field . $sort_symbol,
                      $this->createUrl('view',
                          array(
                              'id' => $trial->id,
                              'sort_by' => $i,
                              'sort_dir' => $new_sort_dir,
                              'page_num' => $page_num,
                          ))
                  );
              } else {
                  echo $field;
              }
              ?>
          </th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>

    <?php /* @var Trial $trial */
    foreach ($dataProvided as $i => $trialPatient) {
        $this->renderPartial('/trialPatient/_view', array(
            'data' => $trialPatient,
            'renderTreatmentType' => $renderTreatmentType,
            'permission' => $permission,
        ));
    }

    ?>
    </tbody>
    <tfoot class="pagination-container">
    <tr>
      <td colspan="9">
        <div class="pagination">
            <?php
            $this->widget('LinkPager', array(
                'pages' => $dataProvider->getPagination(),
                'maxButtonCount' => 15,
                'cssFile' => false,
                'nextPageCssClass' => 'oe-i arrow-right-bold medium pad',
                'previousPageCssClass' => 'oe-i arrow-left-bold medium pad',
                'htmlOptions' => array(
                    'class' => 'pagination',
                ),
            ));
            ?>
        </div>
      </td>
    </tr>
    </tfoot>
  </table>
</div>
