<?php //pr($attr['results']);?>
<div class="btn btn-info"><i class="fa fa-check-circle"></i> <?= $uploadedName.$attr['results']['uploadedName']; ?></div>
<div class="clearfix">&nbsp;</div>
<div class="row">
	<div class="col-sm-2"><label><?= $this->Label->get('Import.total_rows'); ?></label>: <span><?= $attr['results']['totalRows']; ?></span></div> 
	<div class="col-sm-2"><label><?= $this->Label->get('Import.rows_imported'); ?></label>: <span><?= $attr['results']['totalImported']; ?></span></div>
	<div class="col-sm-2"><label><?= $this->Label->get('Import.rows_updated'); ?></label>: <span><?= $attr['results']['totalUpdated']; ?></span></div>
</div>
<hr/>
<div class="row text-danger">
	<i class="fa fa-exclamation-circle"></i> <label><?= $this->Label->get('Import.rows_failed'); ?></label>: <span><?= count($attr['results']['dataFailed']); ?></span> 
	<?php 
	if(!empty($attr['results']['dataFailed'])):
	?>
	<span><?= __('(Hover on the icon(s) to view errors.)') ?></span>
	<span><?= $this->Html->link('<i class="fa kd-download"></i> '.$this->Label->get('Import.download_failed_records'), ['action' => 'downloadFailed', $attr['results']['excelFile']], ['class'=>"btn btn-default", 'escape'=>false]); ?></span>
	<?php
	endif;
	?>
</div>
<?php 
if(!empty($attr['results']['dataFailed'])):
?>
<div class="table-responsive import">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th></th>
				<th><?= $this->Label->get('Import.row_number'); ?></th>
				<?php 
				foreach ($attr['results']['header'] as $col):
					echo sprintf('<th>%s</th>', $col);
				endforeach;
				?>
			</tr>
		</thead>
		
		<tbody>
			<?php 
			foreach ($attr['results']['dataFailed'] as $row):
			?>
			<tr>
				<td><i class="fa fa-exclamation-circle red-tooltip red-exclamation" data-toggle="tooltip" title="<?= $row['error']; ?>"></i></td>
				<td>
					<?= $row['row_number']; ?>
				</td>
				<?php 
				foreach ($row['data'] as $col):
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