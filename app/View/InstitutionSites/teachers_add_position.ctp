<?php 
$model = 'InstitutionSiteTeacher.' . $index; 
$fieldName = 'data[InstitutionSiteTeacher][' . $index . '][%s]';
?>

<?php if(!empty($categoryOptions)) { ?>

<div class="table_row" row-id="<?php echo $index; ?>">
	<div class="table_cell">
		<div class="table_cell_row">
		<?php
		echo $this->Form->input($model . '.teacher_category_id', array(
			'label' => false,
			'div' => false,
			'class' => 'full_width',
			'options' => $categoryOptions
		));
		?>
		</div>
		<div class="table_cell_row input_wrapper">
		<?php
		echo $this->Form->input($model . '.position_no', array(
			'type' => 'text',
			'label' => false,
			'div' => false,
			'class' => 'grey',
			'name' => sprintf($fieldName, 'position_no'),
			'value' => __('Position No'),
			'empty' => __('Position No'),
			'onfocus' => 'objInstitutionSite.positionFocusEvent(this)',
			'onblur' => 'objInstitutionSite.positionBlurEvent(this)',
			'maxlength' => 15
		));
		?>
		</div>
	</div>
	<div class="table_cell">
		<div class="table_cell_row">
			<div class="label"><?php echo __('From'); ?></div>
			<?php 
			echo $this->Utility->getDatePicker($this->Form, $index . 'start_date', 
				array(
					'name' => sprintf($fieldName, 'start_date'),
					'endDateValidation' => $index . 'end_date'
				));
			?>
		</div>
		<div class="table_cell_row">
			<div class="label"><?php echo __('To'); ?></div>
			<?php 
			echo $this->Utility->getDatePicker($this->Form, $index . 'end_date', 
				array(
					'name' => sprintf($fieldName, 'end_date'),
					'emptySelect' => true,
					'endDateValidation' => $index . 'end_date',
					'yearAdjust' => 1
				));
			?>
		</div>
	</div>
	<div class="table_cell">
		<?php
		echo $this->Form->input($model . '.FTE', array(
				'type' => 'text',
				'label' => false,
				'div' => false,
				'class' => 'default',
				'style' => 'width: 90%;',
				'maxlength' => 4,
				'name' => sprintf($fieldName, 'FTE'),
				'onkeypress' => 'return utility.FTECheck(event)'
			));
		?>
	</div>
	<div class="table_cell">
		<?php
		echo $this->Form->input($model . '.teacher_status_id', array(
			'label' => false,
			'div' => false,
			'style' => 'width: 100%',
			'options' => $statusOptions
		));
		?>
	</div>
	<div class="table_cell">
		<div class="table_cell_row"><span class="icon_delete" onclick="jsTable.doRemove(this)"></span></div>
	</div>
</div>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No position available.'); ?></span>

<?php } ?>