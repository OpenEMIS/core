<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Schedule.angular/timetable.svc', ['block' => true]); ?>
<?= $this->Html->script('Schedule.angular/timetable.ctrl', ['block' => true]); ?>

<?php
$this->start('toolbar');
?>
	<a href="<?=$excelUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Export') ?>" >
            <i class="fa kd-export" ></i>
        </button>
    </a>
<?php
$this->end();
?>

<?php
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>

<?= $this->element('OpenEmis.alert') ?>

<div>This is the test </div>

<?php
$this->end();
?>
