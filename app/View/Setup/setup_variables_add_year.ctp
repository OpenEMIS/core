<?php
list($model, $i) = $params;
$fieldName = sprintf('data[%s][%s][%%s]', $model, $i);
?>

<div class="table_row new_row">
	<?php 
	echo $this->Form->hidden('id', array('name' => sprintf($fieldName, 'id'), 'value' => 0));
	echo $this->Form->hidden('available', array('name' => sprintf($fieldName, 'available'), 'value' => 1));
	?>
	<div class="table_cell">
		<div class="input_wrapper combo_box" rel="year_list">
			<?php
			echo $this->Form->input($i . '.name', array(
				'div' => false,
				'label' => false,
				'name' => sprintf($fieldName, 'name')
			));
			?>
		</div>
	</div>
	<div class="table_cell">
		<div class="table_cell_row">
			<div class="label"><?php echo __('From'); ?></div>
			<?php 
			echo $this->Utility->getDatePicker($this->Form, $i . 'start_date', 
				array(
					'name' => sprintf($fieldName, 'start_date'),
					'value' => date('Y-m-d', mktime(0, 0, 0, 1, 1, date('Y'))),
					'endDateValidation' => $i . 'end_date'
				));
			?>
		</div>
		<div class="table_cell_row">
			<div class="label"><?php echo __('To'); ?></div>
			<?php 
			echo $this->Utility->getDatePicker($this->Form, $i . 'end_date', 
				array(
					'name' => sprintf($fieldName, 'end_date'),
					'emptySelect' => true,
					'value' => date('Y-m-d', mktime(0, 0, 0, 1, 1, date('Y')+1)),
					'endDateValidation' => $i . 'end_date',
					'yearAdjust' => 1
				));
			?>
		</div>
	</div>
	<div class="table_cell">
		<div class="input_wrapper">
			<?php
			echo $this->Form->input($i . '.school_days', array(
				'label' => false,
				'div' => false,
				'name' => sprintf($fieldName, 'school_days'),
				'type' => 'text',
				'maxlength' => 5,
				'value' => 0,
				'onkeypress' => 'return utility.integerCheck(event)'
			));
			?>
		</div>
	</div>
	<div class="table_cell">
		<?php
		$attr = array(
			'label' => false, 
			'name' => sprintf($fieldName, 'current'),
			'class' => 'input_radio', 
			'autocomplete' => 'off',
			'onchange' => 'setup.toggleRadio(this)'
		);
		echo $this->Form->radio('current', array('1' => ''), $attr);
		?>
	</div>
	<div class="table_cell"><span class="icon_delete" onclick="jsTable.doRemove(this)"></span></div>
</div>