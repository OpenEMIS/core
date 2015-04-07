<?php

echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Import Institutions'));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');


$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'import'));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['id'] = $model;
$formOptions['type'] = 'file';
echo $this->Form->create($model, $formOptions);

?>
<div class="form-group fileupload fileupload-new" data-provides="fileupload">
	<label class="col-md-3 control-label"><?php echo __('Import'); ?></label>
	<div class="col-md-4">
		<div>
			<span class="btn btn-default btn-file"><span class="fileupload-new"><?php echo __('Select Excel File') ?></span>
				<?php 
				echo $this->Form->input('excel', array(
					'type' => 'file', 
					'class' => false, 
					'div' => false, 
					'label' => false, 
					'before' => false, 
					'after' => false, 
					'between' => false,
					'error' => false,
					'value' => 'abc'
				)); 
				?>
			</span>
		</div>
	</div>
	<?php echo $this->Form->error($model.'.excel', null, array('class' => 'error-message')); ?>
</div>
<div class="form-group">
	<div class="col-md-3"></div>
	<div class="col-md-6">
			<?php echo __("Format Supported:") . " .xsl"; ?>
	</div>
</div>
<?php 
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
echo $this->Form->end();
$this->end(); 
?>
