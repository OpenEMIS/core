<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel'); ?>

<?php $this->start('panelBody'); ?>

<div class="table-wrapper">
    <div class="table-responsive">
        <?= $this->Form->create($archive) ?>
        <?php echo $this->Form->input('Size', array('class' => 'form-control','type'=>'string', 'value'=>'20', disabled));
            echo $this->Form->input('Available Space', array('class' => 'form-control','type'=>'string', 'value'=>'200', disabled));?>
        <?= $this->Form->button(__('Save')) ?>
        <?= $this->Form->button(__('Cancel')) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
<?php $this->end(); ?>