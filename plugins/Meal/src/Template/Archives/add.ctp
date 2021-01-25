<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);

$this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min.css', ['block' => true]);
$this->Html->css('ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min.js', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/chosen/js/angular-chosen.min', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel'); ?>

<?php $this->start('panelBody'); ?>

<?php if($sizerror){ ?>
    <div class="alert alert-danger">Please make sure there is enough space for backup</div>
<?php }?>

<div class="table-wrapper">
    <div class="table-responsive">
        <?= $this->Form->create($archive) ?>
        
        <?= $this->Form->input('Size', array('class' => 'form-control','type'=>'string', 'value'=>$dbsize, 'style'=> 'width:150px;','readonly')); ?>
        
        <?= $this->Form->input('Available Space', array('class' => 'form-control','type'=>'string', 'value'=> $available_disksize, 'style'=> 'width:150px;','readonly'));?>

        <!-- <?= $this->Form->hidden('name', ['value' => 'Backup 2']); ?>
        <?= $this->Form->hidden('path', ['value' => '/webroot/export/backup']); ?>
        <?= $this->Form->hidden('generated_on', ['value' => date('Y-m-d H:i:s')]); ?>
        <?= $this->Form->hidden('generated_by', ['value' => 'System Administrator']); ?> -->
        
        <?= $this->Form->button('<i class="fa fa-check"></i> '.__('Save'), array('class' => 'btn btn-default btn-save')) ?>
        <?= $this->Form->button('<i class="fa fa-close"></i> '.__('Cancel'), array('class' => 'btn btn-outline btn-cancel')) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
<?php $this->end(); ?>