<?php
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('custom_field', false);
echo $this->Html->script('setup_variables', false);

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="custom" class="content_wrapper">
	<h1>
		<span><?php echo __($header); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'fieldOptionIndexEdit', $defaultModel, $sitetype), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	<?php
	echo $this->Form->create('CustomFields', array(
			'id' => 'submitForm',
			'inputDefaults' => array('label' => false, 'div' => false),	
			'url' => array('controller' => 'Setup', 'action' => 'customFields', '', $defaultModel, $sitetype)
		)
	); 
	?>
	<div class="row category">
		<?php
		echo $this->Form->input('options', array(
			'class' => 'default',
			'options' => $options,
			'label' => false,
			'default' => $selectedOption,
			'url' => $this->params['controller'] . '/fieldOption',
			'onchange' => 'jsForm.change(this)',
			'autocomplete' => 'off'
		));
		?>
	</div>
	<?php if(isset($subOptions)) : ?>
	<div class="row category">
		<?php
		echo $this->Form->input('suboptions', array(
			'class' => 'default',
			'options' => $subOptions,
			'label' => false,
			'default' => $selectedSubOption,
			'url' => $this->params['controller'] . '/fieldOption/' . $selectedOption,
			'onchange' => 'jsForm.change(this)',
			'autocomplete' => 'off'
		));
		?>
	</div>
	<?php endif; ?>
	
	<!-- if institution site or census -->
	<?php
	if(count($siteTypes)>0) {
		echo $this->Form->input('institution_site_type_id',	array(
			'id' => 'siteTypeId',
			'class' => 'default',
			'options' => array_merge(array('0'=>'All'),$siteTypes),
			'default' => $sitetype,
			'before' => '<div class="row">',
			'after' => '</div>',
			'url' => sprintf('Setup/setupVariables/%s/%s/', $selectedCategory, $defaultModel),
			'onchange' => 'custom.changeSiteType(this)'
		));
	}
	?>
	<!-- end if -->
	
	<?php 
		$indexField = 0;
        $optIndex = 0;
        $model = $defaultModel;
        $fieldName = sprintf('data[%s][%%s][%%s]', $model);
        $fieldOption = sprintf('data[%s][%%s][%%s]', $model.'Option');
	/*$model = 'InstitutionCustomField'; // change accordingly
	$optionModel = 'InstitutionCustomFieldOption'; // change accordingly
	$fieldName = sprintf('data[%s][%%s][%%s]', $model);
	$fieldOption = sprintf('data[%s][%%s][%%s]', $optionModel);*/
	?>
	<div class="quicksand field_list">
		<?php 
			foreach($data as $index => $arrVal){ ?>
				<div data-id="<?php echo $index; ?>">
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
						'name' => sprintf($fieldName, $index, 'visible'),
						'type' => 'hidden',
						'value' => $arrVal[$model]['visible']
					));
					//if(!in_array($defaultModel,$noSiteTypeCustFields)){
					if($CustomFieldModelLists[$defaultModel]['hasSiteType'])	{
					echo $this->Form->input('institution_site_type_id', array(
						'name' => sprintf($fieldName, $index, 'institution_site_type_id'),
						'type' => 'hidden',
						'value' => $arrVal[$model]['institution_site_type_id']));    
					}
					if($arrVal[$model]['type'] == 1){
					?>
					<fieldset class="custom_section_break">
						<legend>
							<?php
							echo $this->Form->input('name', array(
								'name' => sprintf($fieldName, $index, 'name'),
								'type' => 'text',
								'class' => 'default',
								'disabled' =>'disabled',
								'placeholder' => __('Field Label'),
								'value' => $arrVal[$model]['name'],
								'autocomplete' => 'off'
							));
							?>
						</legend>
					</fieldset>
					<?php } else { 
						
					?>
					
					<div class="custom_field">
							<div class="field_label">
								<?php
								echo $this->Form->input('name', array(
									'name' => sprintf($fieldName, $index, 'name'),
									'type' => 'text',
									'class' => 'default',
									'disabled' =>'disabled',
									'placeholder' => __('Field Label'),
									'value' => $arrVal[$model]['name'],
									'autocomplete' => 'off'
								));
								?>
									
							</div>
							<?php if($arrVal[$model]['type'] == 2 ){ ?>
							<div class="field_value"><input type="text" class="default" disabled="disabled" /></div>
							<?php 
							
							}elseif($arrVal[$model]['type'] == 3 || $arrVal[$model]['type'] == 4){ 
								if(count($arrVal[$model.'Option']) > 0){
									
							?>
							<div class="field_value">
								<!-- <select disabled="disabled"><option>-- Select --</option></select> -->
								<ul class="quicksand options">
									<?php //echo $fieldOption;pr($arrVal[$model.'Option']); ?>
									<?php foreach ($arrVal[$model.'Option'] as $optK => $optVal) {?>
									<li data-id="<?php echo $optIndex;?>">
											
									<input type="text" class="default" disabled="disabled" name="<?php echo sprintf($fieldOption, $optIndex, 'value'); ?>" value="<?php echo $optVal['value']; ?>" />
									<?php if($optVal['visible'] == 1) { ?>
										<span class="green">&#10003;</span>
									<?php } else { ?>
										<span class="red">&#10005;</span>
									<?php } ?>
									</li>
									<?php $optIndex++; } ?>
								</ul>
							</div>
							<?php 
								}
							}elseif($arrVal[$model]['type'] == 5 ){ 
							?>
							<div class="field_value"><textarea disabled="disabled"></textarea></div>
							<?php } ?>
					</div>
					<?php } ?>
				</div>
		<?php } ?>
	</div> <!-- End field-list -->
        
	<?php echo $this->Form->end(); ?>
</div>
