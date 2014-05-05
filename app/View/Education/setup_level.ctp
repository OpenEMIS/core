<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));

echo $this->Html->script('education', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($pageTitle));
$this->start('contentActions');
echo $this->Html->link(__('Structure'), array('action' => 'index'), array('class' => 'divider'));
	if($_edit) {
		echo $this->Html->link(__('Edit'), array('action' => 'setupEdit', $selectedOption), array('class' => 'divider'));
	}
$this->end();
$this->assign('contentId', 'education_setup');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php
echo $this->Form->create('Education', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Education', 'action' => 'setup')
	)
);
?>

<div class="row category">
	<?php
	echo $this->Form->input('category', array(
		'id' => 'category',
		'options' => $setupOptions,
		'default' => $selectedOption,
		'class' => 'form-control',
		'div' => 'col-md-4',
		'autocomplete' => 'off',
		'onchange' => 'education.navigateTo(this)'
	));
	?>
</div>
<div class="row">
<?php foreach($list as $systemName => $levels) { ?>
<fieldset class="section_group">
	<legend><?php echo $systemName; ?></legend>
	
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<td class="table_cell cell_visible"><?php echo __('Visible'); ?></td>
				<td class="table_cell"><?php echo __($pageTitle); ?></td>
				<td class="table_cell"><?php echo __('ISCED Level'); ?></td>
			</tr>
		</thead>
		
		<tbody class="table_body">
		<?php foreach($levels as $key => $obj) {
				if($key === 'id') continue;
		?>
			<tr class="table_row<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
				<td class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']); ?></td>
				<td class="table_cell"><?php echo $obj['name']; ?></td>
				<td class="table_cell"><?php echo $obj['isced_name']; ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	</div>
</fieldset>
<?php } ?>
</div>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
