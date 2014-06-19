<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('custom_field', false);

$actionLabel = Array(
    '1' => 'Section Break',
    '2' => 'Single Line Text',
    '3' => 'DropDown List',
    '4' => 'Checkboxes',
    '5' => 'Multi Line Text',
);
?>
<?php echo $this->element('breadcrumb'); ?>

<div id="custom" class="content_wrapper">
	<?php
	echo $this->Form->create('CustomFields', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Setup', 'action' => 'customFieldsEdit', $selectedCategory, $defaultModel, $sitetype)
	)); 
	?>
	<h1>
		<span><?php echo __($header); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'setupVariables', $selectedCategory, $defaultModel, $sitetype), array('class' => 'divider')); ?>
	</h1>
	
	<div class="row category">
		<?php
		echo $this->Form->input('category', array(
			'id' => 'category',
			'options' => $categoryList,
			'default' => $selectedCategory,
			'url' => 'Setup/setupVariablesEdit/',
			'onchange' => 'setup.changeCategory()'
		));
		?>
	</div>
	
	<!-- if institution site or census -->
	<?php
	if(count($siteTypes)>0) {
		echo $this->Form->input('institution_site_type_id',	array(
			'id' => 'siteTypeId',
			'options' => array_merge(array('0'=>'All'),$siteTypes),
			'default' => $sitetype,
			'before' => '<div class="row">',
			'after' => '</div>',
			'url' => sprintf('Setup/setupVariablesEdit/%s/%s/', $selectedCategory, $defaultModel),
			'onchange' => 'custom.changeSiteType(this)'
		));
	}
	?>
	<!-- end if -->
	
	<div class="row">
		<span id="model" class="none"><?php echo $defaultModel; ?></span>
		<span id="refField" class="none"><?php echo $referenceId; ?></span>
	</div>
	
	<?php if($_add) { ?>
	<div class="row add">
		<?php foreach($actionLabel as $id => $name) { ?>
		<input type="button" value="<?php echo __('Add') . ' ' . __($name); ?>" class="btn_left btn_field" onclick="custom.addField(<?php echo $id; ?>)" />
		<?php } ?>
	</div>
	<?php } ?>
	
	<?php 
	$indexField = 1;
	$optIndex = 1;
	$model = $defaultModel;
	$fieldName = sprintf('data[%s][%%s][%%s]', $model);
	$fieldOption = sprintf('data[%s][%%s][%%s]', $model.'Option');
	?>
	<div class="quicksand field_list">
	<?php foreach($data as $index => $arrVal){ $isVisible = $arrVal[$model]['visible']==1; ?>
		<div data-id="<?php echo $indexField; ?>" class="<?php echo !$isVisible ? 'inactive' : ''; ?>">
		<?php
		echo $this->Form->input('order', array(
				'id' => 'order',
				'name' => sprintf($fieldName, $index, 'order'),
				'type' => 'hidden',
				'value' => $indexField++
		));
		echo $this->Form->input('id', array(
				'id' => 'id',
				'name' => sprintf($fieldName, $index, 'id'),
				'type' => 'hidden',
				'value' => $arrVal[$model]['id']
		));
		echo $this->Form->input('type', array(
				'name' => sprintf($fieldName, $index, 'type'),
				'type' => 'hidden',
				'value' => $arrVal[$model]['type'] // type = Text
		));
		echo $this->Form->input('visible', array(
				'id' => 'visible',
				'name' => sprintf($fieldName, $index, 'visible'),
				'type' => 'hidden',
				'value' => $arrVal[$model]['visible']
		));
		if($CustomFieldModelLists[$defaultModel]['hasSiteType'])	{
			echo $this->Form->input('institution_site_type_id', array(
				'name' => sprintf($fieldName, $index, 'institution_site_type_id'),
				'type' => 'hidden',
				'value' => $arrVal[$model]['institution_site_type_id']));    
		}
		
		if($arrVal[$model]['type'] == 1){ // type = label
		?>
		<fieldset class="custom_section_break">
			<legend>
			<?php
			echo $this->Form->input('name', array(
					'name' => sprintf($fieldName, $index, 'name'),
					'type' => 'text',
					'class' => 'default',
					'placeholder' => __('Field Label'),
					'value' => $arrVal[$model]['name'],
					'autocomplete' => 'off'
			));
			?>
			</legend>
			<div class="action">
				<div class="tag"><?php echo __('Section Break'); ?></div>
				<span class="icon_visible"></span>
				<span class="icon_up"></span>
				<span class="icon_down"></span>
			</div>
		</fieldset>
		
		<?php } else { ?>
					
		<div class="custom_field">
			<div class="field_label">
				<?php
				echo $this->Form->input('name', array(
						'name' => sprintf($fieldName, $index, 'name'),
						'type' => 'text',
						'class' => 'default',
						'placeholder' => __('Field Label'),
						'value' => $arrVal[$model]['name'],
						'autocomplete' => 'off'
				));
				?>
				<div class="action">
					<div class="tag"><?php echo __($actionLabel[$arrVal[$model]['type']]);?></div>
					<span class="icon_visible"></span>
					<span class="icon_up"></span>
					<span class="icon_down"></span>
				</div>
			</div>
			
			<?php if($arrVal[$model]['type'] == 2 ){ ?>
			
			<div class="field_value"><input type="text" class="default" disabled="disabled" /></div>
			
			<?php } elseif($arrVal[$model]['type'] == 3 || $arrVal[$model]['type'] == 4){ ?>
			
			<div class="field_value">
				<ul class="quicksand options">
				<?php $optIndexCtr = 1; foreach ($arrVal[$model.'Option'] as $optK => $optVal) { ?>
				<?php 	$isOptionVisible = $optVal['visible']==1; ?>
					<li data-id="<?php echo $optIndexCtr;?>" class="<?php echo !$isOptionVisible ? 'inactive' : ''; ?>">
						<input type="hidden" id="order" name="<?php echo sprintf($fieldOption, $optIndex, 'order'); ?>" value="<?php echo $optIndexCtr; ?>" />
						<input type="hidden" id="visible" name="<?php echo sprintf($fieldOption, $optIndex, 'visible'); ?>" value="<?php echo $optVal['visible'] ?>" />
						<input type="hidden" id="id" name="<?php echo sprintf($fieldOption, $optIndex, 'id'); ?>" value="<?php echo $optVal['id']; ?>" />
						<input type="hidden" id="ref_id" name="<?php echo sprintf($fieldOption, $optIndex, $referenceId); ?>" value="<?php echo $arrVal[$model]['id']; ?>" />
						<input type="text" class="default" name="<?php echo sprintf($fieldOption, $optIndex, 'value'); ?>" value="<?php echo $optVal['value']; ?>" />
						<span class="icon_visible"></span>
						<span class="icon_up"></span>
						<span class="icon_down"></span>
					</li>
				<?php $optIndex++;$optIndexCtr++; } // end foreach ?>
				</ul>
				<div class="row add_option">
					<span id="refValue" class="none"><?php echo $arrVal[$model]['id']; ?></span>
					<a class="void icon_plus"><?php echo __('Add') . ' ' . __('an option'); ?></a>
				</div>
			</div>
			
			<?php } elseif($arrVal[$model]['type'] == 5 ){ ?>
			<div class="field_value"><textarea disabled="disabled"></textarea></div>
			<?php } // end if field type label ?>
			</div>
		<?php } // end if field type ?>
		</div>	
	<?php } // end foreach ?>
		
	</div> <!-- End field-list -->
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setupVariables', $selectedCategory, $defaultModel, $sitetype), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
