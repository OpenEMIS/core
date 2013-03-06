<?php
$actionLabel = Array(
    '1' => 'Section Break',
    '2' => 'Single Line Text',
    '3' => 'DropDown List',
    '4' => 'Checkboxes',
    '5' => 'Multi Line Text',
);
list($fieldType, $model, $order, $refField, $siteType,$customfieldid) = $params; 
$fieldName = sprintf('data[%s][%s][%%s]', $model, $order);
$fieldOption = sprintf('data[%s][0][%%s]', $model.'Option');
$inputNameOption = array(
	'label' => false,
	'div' => false,
	'name' => sprintf($fieldName, 'name'),
	'type' => 'text',
	'class' => 'default',
	'placeholder' => __('Field Label'),
	'autocomplete' => 'off');
?>

<div>
	<?php
	echo $this->Form->input('order', array(
			'id' => 'order',
			'name' => sprintf($fieldName, 'order'),
			'type' => 'hidden',
			'value' => '1'
	));
	echo $this->Form->input('id', array(
			'id' => 'id',
			'name' => sprintf($fieldName, 'id'),
			'type' => 'hidden',
			'value' => $customfieldid
	));
	echo $this->Form->input('type', array(
			'name' => sprintf($fieldName, 'type'),
			'type' => 'hidden',
			'value' => $fieldType
	));
	echo $this->Form->input('visible', array(
			'id' => 'visible',
			'name' => sprintf($fieldName, 'visible'),
			'type' => 'hidden',
			'value' => '1'
	));
	if($model != 'InstitutionCustomField'){
		echo $this->Form->input('institution_site_type_id', array(
			'name' => sprintf($fieldName, 'institution_site_type_id'),
			'type' => 'hidden',
			'value' => $siteType));
	}
	
	if($fieldType == 1){ // type = label
	?>
	<fieldset class="custom_section_break">
		<legend><?php echo $this->Form->input('name', $inputNameOption); ?></legend>
		<div class="action">
			<div class="tag">Section Break</div>
			<span class="icon_visible"></span>
			<span class="icon_up"></span>
			<span class="icon_down"></span>
		</div>
	</fieldset>
	
	<?php } else { ?>
				
	<div class="custom_field">
		<div class="field_label">
			<?php echo $this->Form->input('name', $inputNameOption); ?>
			<div class="action">
				<div class="tag"><?php echo $actionLabel[$fieldType]; ?></div>
				<span class="icon_visible"></span>
				<span class="icon_up"></span>
				<span class="icon_down"></span>
			</div>
		</div>
		
		<?php if($fieldType == 2 ){ ?>
		
		<div class="field_value"><input type="text" class="default" disabled="disabled" /></div>
		
		<?php } elseif($fieldType == 3 || $fieldType == 4){ ?>
		
		<div class="field_value">
			<ul class="quicksand options">
				<li data-id="1">
					<input type="hidden" id="order" name="<?php echo sprintf($fieldOption, 'order'); ?>" value="1" />
					<input type="hidden" id="visible" name="<?php echo sprintf($fieldOption, 'visible'); ?>" value="1" />
					<input type="hidden" id="id" name="<?php echo sprintf($fieldOption, 'id'); ?>" value="0" />
					<input type="hidden" id="ref_id" name="<?php echo sprintf($fieldOption, $refField); ?>" value="<?php echo $customfieldid;?>" />
					<input type="text" class="default" name="<?php echo sprintf($fieldOption, 'value'); ?>" value="" />
					<span class="icon_visible"></span>
					<span class="icon_up"></span>
					<span class="icon_down"></span>
				</li>
			</ul>
			<div class="row add_option">
				<span id="refValue" class="none"><?php echo $customfieldid;?></span>
				<a href="javascript: void(0)" class="icon_plus"><?php echo __('Add') . ' ' . __('an option'); ?></a>
			</div>
		</div>
		
		<?php } elseif($fieldType == 5 ){ ?>
		<div class="field_value"><textarea disabled="disabled"></textarea></div>
		<?php } // end if field type label ?>
	</div>
<?php } // end if field type ?>
</div>
	