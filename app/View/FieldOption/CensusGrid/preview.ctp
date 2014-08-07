<?php
if ($this->action == 'view') {
	$obj = isset($data[$model]) ? $data[$model] : array();
	$x = isset($data['CensusGridXCategory']) ? $data['CensusGridXCategory'] : array();
	$y = isset($data['CensusGridYCategory']) ? $data['CensusGridYCategory'] : array();
} else {
	$obj = isset($this->data[$model]) ? $this->data[$model] : array();
	$x = isset($this->data['CensusGridXCategory']) ? $this->data['CensusGridXCategory'] : array();
	$y = isset($this->data['CensusGridYCategory']) ? $this->data['CensusGridYCategory'] : array();
}
$xCount = count($x);
?>

<div class="custom_field">
	<div class="field_label">Preview</div>
	<div class="field_value">
	
		<?php if (!empty($obj['name'])) : ?>
		<fieldset class="custom_section_break">
			<legend><?php echo $obj['name'] ?></legend>
		</fieldset>
		<?php endif ?>
		
		<?php if (!empty($obj['description'])) : ?>
		<div class="">
			<?php echo $obj['description'] ?>
		</div>
		<?php endif ?>
		
		<?php if (!empty($obj['x_title'])) : ?>
		<div class="" style="margin-top: 20px; font-weight: bold; text-align: center">
			<?php echo $obj['x_title'] ?>
		</div>
		<?php endif ?>
		
		<?php if (!empty($x) || !empty($y)) : ?>
		<div class="grid-table" style="margin-top: 20px">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<?php
						foreach ($x as $col) {
							echo '<th>' . $col['name'] . '</th>';
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach ($y as $row) {
						echo '<tr>';
						echo '<td>' . $row['name'] . '</td>';
						for ($i=0; $i<$xCount; $i++) {
							echo '<th></th>';
						}
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
		</div>
		<?php endif ?>
	</div>
	<?php if ($this->action != 'view') : ?>
	<hr />
	<div class="center" style="margin-bottom: 20px;">
	<?php echo $this->Form->submit($this->Label->get("$model.update_preview"), array('name' => 'submit', 'value' => 'update', 'class' => 'btn_save', 'div' => false)); ?>
	</div>
	<?php endif ?>
</div>
<hr />
