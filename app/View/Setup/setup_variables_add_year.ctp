<?php
list($model, $i) = $params;
$fieldName = sprintf('data[%s][%s][%%s]', $model, $i);
?>

<div class="table_row new_row">
	<?php 
	echo $this->Form->hidden('id', array('name' => sprintf($fieldName, 'id'), 'value' => 0));
	echo $this->Form->hidden('available', array('name' => sprintf($fieldName, 'available'), 'value' => 1));
	echo $this->Form->hidden('start_year', array('class' => 'start_year', 'name' => sprintf($fieldName, 'start_year'), 'value' => 0));
	echo $this->Form->hidden('end_year', array('class' => 'end_year', 'name' => sprintf($fieldName, 'end_year'), 'value' => 0));
	?>
	<div class="table_cell">
		<?php 
		echo $this->Utility->getYearList($this->Form,'name',array(
			'name' => sprintf($fieldName, 'name'),
			'class' => 'full_width',
			'desc' => true,
			'label' => false,
			'div' => false
		));
		?>
	</div>
	<div class="table_cell">
		<?php 
		echo $this->Utility->getDatePicker($this->Form, $i.'start_date', 
			array(
				'class' => 'start_date',
				'value' => date('Y-m-d', mktime(0, 0, 0, 1, 1, date('Y'))),
				'name' => sprintf($fieldName, 'start_date'),
				'endDateValidation' => $i.'end_date'
			));
		?>
	</div>
	<div class="table_cell">
		<?php 
		echo $this->Utility->getDatePicker($this->Form, $i.'end_date', 
			array(
				'class' => 'end_date',
				'value' => date('Y-m-d', mktime(0, 0, 0, 1, 1, date('Y')+1)),
				'name' => sprintf($fieldName, 'end_date'),
				'endDateValidation' => $i.'end_date',
				'yearAdjust' => 1
			));
		?>
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