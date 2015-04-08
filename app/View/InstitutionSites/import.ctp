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

if(empty($data)):
?>
<div class="form-group fileupload fileupload-new" data-provides="fileupload">
	<label class="col-md-3 control-label"><?php echo __('Select Excel'); ?></label>
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
			<?php echo __("Format Supported:") . " .xls"; ?>
	</div>
</div>
<?php 
else:
?>
<p>Total Rows: <?php echo $totalRows; ?></p>
<p>Total Validation Failed: <?php echo count($dataFailed); ?></p>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
<!--				<th><?php echo __('Error'); ?></th>-->
				<?php 
				foreach($header as $col):
					echo sprintf('<th>%s</th>', $col);
				endforeach;
				?>
			</tr>
		</thead>
		
		<tbody>
			<?php 
			foreach($dataFailed as $row):
			?>
			<tr>
<!--				<td><?php echo __('Error'); ?></td>-->
				<?php 
				foreach($row['data'] as $col):
					echo sprintf('<td>%s</td>', $col);
				endforeach;
				?>
			</tr>
			<?php 
			endforeach;
			?>
		</tbody>
	</table>
</div>
<?php
endif;
?>

<div class="form-group">
	<div class="col-md-offset-4">
	<?php 
	if(empty($data)){
		echo $this->Form->submit($this->Label->get('general.import'), array('name' => 'submit', 'class' => 'btn_save btn_right', 'div' => false));
		echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left'));
	}else{
		echo $this->Form->submit($this->Label->get('general.proceed'), array('name' => 'submit', 'class' => 'btn_save btn_right', 'div' => false));
		echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'import'), array('class' => 'btn_cancel btn_left'));
	}
	?>
	</div>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>
