<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));
echo $this->Html->script('education', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($pageTitle));
$this->start('contentActions');
echo $this->Html->link(__('Structure'), array('action' => 'index'), array('class' => 'divider'));
echo $this->Html->link(__('View'), array('action' => 'setup', $selectedOption), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'education_setup');
$this->assign('contentClass', 'edit setup_level');

$this->start('contentBody');
?>
<?php
echo $this->Form->create('Education', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Education', 'action' => 'setupEdit', $selectedOption)
	)
);
?>

<div id="params" class="none">
	<span name="category"><?php echo $selectedOption; ?></span>
</div>

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

<?php 
$index = 0;
foreach($list as $systemName => $levels) { 
?>
<fieldset class="section_group">
	<legend><?php echo $systemName; ?></legend>
	
	<div class="params none">
		<span name="education_system_id"><?php echo $levels['id']; ?></span>
	</div>
	
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table_view">
		<thead>
			<tr>
				<th class="table_cell cell_visible"><?php echo __('Visible'); ?></th>
				<th class="table_cell"><?php echo __($pageTitle); ?></th>
				<th class="table_cell"><?php echo __('ISCED Level'); ?></th>
				<th class="table_cell cell_order"><?php echo __('Order'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		//echo $this->Utility->getListStart();
		$index = 1;
		foreach($levels as $i => $obj) {
			if($i === 'id') continue;
			$fieldName = sprintf('data[%s][%s][%%s]', $model, $index++);
			/*$isVisible = $obj['visible']==1;
			$fieldName = sprintf('data[%s][%s][%%s]', $model, $index++);
		
			echo $this->Utility->getListRowStart($i, $isVisible);
			echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
			echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
			echo $this->Form->hidden('education_system_id', array(
				'id' => 'education_system_id',
				'name' => sprintf($fieldName, 'education_system_id'),
				'value' => $levels['id']
			));
			echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
			echo $this->Utility->getNameInput($this->Form, $fieldName, $obj['name'], $isNameEditable);
			
			echo '<div class="cell cell_isced">';
			echo $this->Form->select('education_level_isced_id', $isced,
				array(
					'name' => sprintf($fieldName, 'education_level_isced_id'),
					'value' => $obj['education_level_isced_id'],
					'empty' => false
				)
			);
			echo '</div>';
			echo $this->Utility->getOrderControls();
			echo $this->Utility->getListRowEnd();*/ ?>

			<tr row-id="<?php echo $obj['id']; ?>">
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
				<td><?php echo $obj['name']; ?></td>
				<td><?php echo $this->Form->select('education_level_isced_id', $isced,
					array(
						'name' => sprintf($fieldName, 'education_level_isced_id'),
						'value' => $obj['education_level_isced_id'],
						'empty' => false,
						'class' => 'form-control'
					)
				); ?>
				</td>
				<td class="action">
					<?php
					$size = count($obj);
					echo $this->element('layout/reorder', compact('index', 'size'));
					$index++;
					?>
				</td>
			</tr>
		<?php 
		}
		//åecho $this->Utility->getListEnd();
		?>
	</tbody>
</table>
</div>
<?php 
if($_add) { echo $this->Utility->getAddRow($pageTitle); }
?>
</fieldset>
<?php } ?>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'setup', $selectedOption), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
