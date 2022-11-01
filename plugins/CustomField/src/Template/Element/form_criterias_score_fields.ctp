<?php
	$alias = $ControllerAction['table']->alias();
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	$reorder = isset($attr['reorder']) ? $attr['reorder'] : [];
	$labels = isset($attr['labels']) ? $attr['labels'] : [];
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= __($attr['label'])?></h3>
	<div class="row">
		<div class="col-xs-6 col-md-3 form-label"><?= $attr2['add_steps_field'] ?></div>
		<div class="form-input"><?php echo $attr2['attr']['value']; ?></div>
	</div>
	<div class="row">
		<div class="col-xs-6 col-md-3 form-label"><?= $attr['add_field'] ?></div>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
					<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
				</table>
			</div>
		</div>
	</div>

<?php elseif ($ControllerAction['action'] == 'edit') : ?>
	<?= $this->Html->script('CustomField.custom.form', ['block' => true]); ?>
	<div class="clearfix"></div>
		<br/>
		<hr>
		<h3><?= __($attr['label'])?></h3>
		<div class="clearfix">
			<?php
				$attr['model'] = $alias;
				echo $this->HtmlField->chosenSelectInput($attr, [
					'label' => $attr['add_field'], 
					'multiple' => false, 
					'onchange' => "$('#reload').val('addField').click();"
				]);

				if(array_key_exists('add_steps_field', $attr2)) {
					echo $this->HtmlField->chosenSelectInput($attr2, [
						'label' => $attr2['add_steps_field'], 
						'options' => $attr2['options'],
						'multiple' => false, 
						'value' => $attr2['attr']['value']
					]);
				}
			?>
			<br/>
		</div>
	<div class="table-wrapper">
		<div class="table-responsive">
			<table class="table table-curved table-input">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php endif ?>
