<?php
list($model, $i) = $params;
$fieldName = sprintf('data[%s][%s][%%s]', $model, $i);
?>

<div class="table_row new_row">
	<?php echo $this->Form->hidden('id', array('name' => sprintf($fieldName, 'id'), 'value' => 0)); ?>
	<?php echo $this->Form->hidden('available', array('name' => sprintf($fieldName, 'available'), 'value' => 1)); ?>
	<div class="table_cell">
		<!--div class="input_wrapper">
			<?php /*echo $this->Form->input('name', array(
				'name' => sprintf($fieldName, 'name'),
				'maxlength' => 30,
				'label' => false,
				'div' => false
			));*/
			?>
		</div-->
		<?php 
			echo $this->Utility->getYearList($this->Form,'name',array(
				'name' => sprintf($fieldName, 'name'),
				'desc' => true,
				'maxlength' => 30,
				'label' => false,
				'div' => false));
		?>
	</div>
	<div class="table_cell">
		<?php echo $this->Utility->getDatePicker($this->Form, 'start_date', array('desc' => true,'name' => sprintf($fieldName, 'start_date'))); ?>
	</div>
	<div class="table_cell">
		<?php echo $this->Utility->getDatePicker($this->Form, 'end_date', array('desc' => true,'name' => sprintf($fieldName, 'end_date'))); ?>
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
	<div class="table_cell"><span class="icon_cross"></span></div>
</div>