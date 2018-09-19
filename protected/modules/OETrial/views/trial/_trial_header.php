<?php
/**
 * @var Trial $trial
 * @var TrialPermission $permission
 */
?>

<nav class="oe-full-header flex-layout">
  <div class="title wordcaps"><?= $title ?></div>
  <div>
      <?php if (in_array($this->action->id, ['update', 'create'])): ?>
        <button class="button header-tab green" name="save" type="submit" form="trial-form">
            <?= $trial->getIsNewRecord() ? 'Create' : 'Save' ?>
        </button>

          <?= CHtml::link(
              'Cancel',
              $trial->getIsNewRecord() ? $this->createUrl('index') : $this->createUrl('view',
                  array('id' => $trial->id)),
              array('class' => 'button header-tab red')
          ) ?>
      <?php else: ?>
          <?php if (!$trial->getIsNewRecord()): ?>
              <?= CHtml::link('View', $this->createUrl('view', array('id' => $trial->id)),
                  array('class' => 'button header-tab ' . ($this->action->id === 'view' ? 'selected' : ''))) ?>
          <?php endif; ?>
          <?= CHtml::link('Edit', $this->createUrl('update', array('id' => $trial->id)),
              array('class' => 'button header-tab ' . ($this->action->id !== 'view' ? 'selected' : ''))) ?>

          <?php if ($permission->can_manage): ?>
              <?php if ($trial->is_open): ?>
                  <?= CHtml::link(
                      'Close Trial',
                      $trial->getIsNewRecord() ? $this->createUrl('index') : $this->createUrl('close',
                          array('id' => $trial->id)),
                      array('class' => 'button header-tab red')
                  ) ?>
              <?php else: ?>
                  <?= CHtml::link(
                      'Re-open Trial',
                      $trial->getIsNewRecord() ? $this->createUrl('index') : $this->createUrl('reopen',
                          array('id' => $trial->id)),
                      array('class' => 'button header-tab green')
                  ) ?>
              <?php endif; ?>
          <?php endif; ?>
      <?php endif; ?>
  </div>
</nav>