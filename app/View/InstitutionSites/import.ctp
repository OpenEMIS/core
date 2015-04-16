<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
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
	<div class="col-md-6">
		<div class="fileupload fileupload-new" data-provides="fileupload">
			<div class="input-group">
				<div class="form-control">
					<i class="fa fa-file fileupload-exists"></i> <span class="fileupload-preview"></span>
				</div>
				<div class="input-group-btn">
					<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload"><?php echo __('Remove'); ?></a>
					<span class="btn btn-default btn-file">
						<span class="fileupload-new"><?php echo __('Select file'); ?></span>
						<span class="fileupload-exists"><?php echo __('Change'); ?></span>
						<?php 
							echo $this->Form->input('excel', array(
								'type' => 'file', 
								'class' => false, 
								'div' => false, 
								'label' => false, 
								'before' => false, 
								'after' => false, 
								'between' => false
							)); 
						?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="form-group">
	<div class="col-md-3"></div>
	<div class="col-md-6">
			<?php echo $this->Label->get('general.format_supported') . ": .xls/.xlsx"; ?><br>
			<?php echo $this->Label->get('Import.recommended_max_rows') . ": 3000"; ?>
	</div>
</div>
<?php 
else:
?>
<div class="importedFile"><i class="fa fa-check-circle blue-check"></i> <?php echo $uploadedName; ?></div>
<div class="overallInfo importInfo">
	<div class="totalRows"><?php echo $this->Label->get('Import.total_rows'); ?>: <span class="content"><?php echo $totalRows; ?></span></div> 
	<div class="imported"><?php echo $this->Label->get('Import.rows_imported'); ?>: <span class="content"><?php echo $totalImported; ?></span></div>
	<div class="updated"><?php echo $this->Label->get('Import.rows_updated'); ?>: <span class="content"><?php echo $totalUpdated; ?></span></div>
</div>
<div class="failedInfo importInfo">
	<i class="fa fa-exclamation-circle red-exclamation"></i> <?php echo $this->Label->get('Import.rows_failed'); ?>:
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
				<td><i class="fa fa-exclamation-circle red-tooltip red-exclamation" data-toggle="tooltip" title="<?php echo $row['error']; ?>"></i></td>
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
	}
	?>
	</div>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>


