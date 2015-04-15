<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Import Institutions'));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.download_template'), array('action' => 'importTemplate'), array('class' => 'divider'));
	if(empty($uploadedName)){
		echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index'), array('class' => 'divider'));
	}else{
		echo $this->Html->link($this->Label->get('general.back'), array('action' => 'import'), array('class' => 'divider'));
	}
	
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'import'));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['id'] = $model;
$formOptions['type'] = 'file';
echo $this->Form->create($model, $formOptions);

if(empty($uploadedName)):
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
			<?php echo __("Format Supported:") . " .xlsx"; ?>
	</div>
</div>
<?php 
else:
?>
<div class="importedFile"><?php echo $uploadedName; ?></div>
<div class="overallInfo importInfo">
	<div class="totalRows">Total Rows: <span class="content"><?php echo $totalRows; ?></span></div> 
	<div class="imported">Total Rows Imported: <span class="content"><?php echo $totalSuccess; ?></span></div>
</div>
<div class="failedInfo importInfo">
	<i class="fa fa-exclamation-circle red-tooltip"></i> Total Validation Failed:
	<span class="content"><?php echo count($dataFailed); ?></span>
</div>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th></th>
				<th><?php echo __('Row Number'); ?></th>
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
				<td><i class="fa fa-exclamation-circle red-tooltip" data-toggle="tooltip" title="<?php echo $row['error']; ?>"></i></td>
				<td>
					<?php echo $row['row_number']; ?>
				</td>
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
	if(empty($uploadedName)){
		echo $this->Form->submit($this->Label->get('general.import'), array('name' => 'submit', 'class' => 'btn_save btn_right', 'div' => false));
		echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left'));
	}else{
		//echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'import'), array('class' => 'btn_cancel btn_left'));
	}
	?>
	</div>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>


