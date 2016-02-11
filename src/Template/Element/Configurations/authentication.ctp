<?php if ($ControllerAction['action'] == 'index') : ?>
	
<?php elseif ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<div class="clearfix"></div>
		<hr>
		<h3><?= __('Authentication Configurations')?></h3>
		<div class="clearfix">
			<?php 
				foreach ($attr as $key => $value) {
				echo 
					$this->Form->input('AuthenticationTypeAttributes'.'.'.$key.'.value', $value);

				echo 
					$this->Form->input('AuthenticationTypeAttributes'.'.'.$key.'.name', [
						'type' => 'hidden',
						'value' => $value['label']
					]);
				}
			?>
			<br/>
		</div>
<?php endif ?>
