<?php //pr($attr['results']);?>
<div class="overview-box alert">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<i class="kd-rows icon"></i>
		<div class="data-field">
			<h4>Total Data:</h4>
			<h1 class="data-header"><?= $attr['results']['totalRows']; ?></h1>
		</div>
	</div>

	<div class="data-section">
		<div class="data-field">
			<h4>Datas Imported:</h4>	
			<h1 class="data-header"><?= $attr['results']['totalImported']; ?></h1>
		</div>
	</div>

	<div class="data-section">
		<div class="data-field">	
			<h4>Data Updated:</h4>
			<h1 class="data-header"><?= $attr['results']['totalUpdated']; ?></h1>
		</div>		
	</div>

	<div class="data-section">
		<div class="data-field">	
			<h4>Data Failed:</h4>
			<h1 class="data-header"><?= count($attr['results']['dataFailed']); ?></h1>
		</div>		
	</div>
</div>

<?php 
if(!empty($attr['results']['passedExcelFile'])) {
	$passedRecordsLink = $this->Html->link('<i class="fa kd-download-success"></i> '.$this->Label->get('Import.download_passed_records'), $attr['results']['passedExcelFile'], ['class'=>"btn btn-green", 'escape'=>false]);
} else {
	$passedRecordsLink = '';
}

if(!empty($attr['results']['failedExcelFile'])):
?>

<div class="table-wrapper">
	<div class="table-responsive table-scroll-y">
		<table class="table">
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
					<td class="tooltip-red">
						<?php
							$buffer = explode(';', $row['error']);
							$error = '<ul><li>'. implode('</li><li>', $buffer) . '</li></ul>';
						?>
						<i class="fa fa-exclamation-circle fa-lg table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="<?= $error ?>"></i>
					</td>
					<td><?= $row['row_number']; ?></td>
					<?php 
					foreach ($row['data'] as $key=>$col):
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
</div>

<div class="form-buttons">
	<?= $this->Html->link('<i class="fa kd-download-fail"></i> '.$this->Label->get('Import.download_failed_records'), $attr['results']['failedExcelFile'], ['class'=>"btn btn-red", 'escape'=>false]); ?>
	<?= $passedRecordsLink; ?>
</div>

<?php
else:
?>

<div class="form-buttons">
	<?= $passedRecordsLink; ?>
</div>

<?php
endif;
?>

