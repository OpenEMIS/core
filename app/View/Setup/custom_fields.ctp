<?php
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('custom_field', false);

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
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
			'url' => array('controller' => 'Setup', 'action' => 'customFields',$defaultModel,$sitetype)
		)
	); 
	?>
	<h1>
		<span><?php echo __('Custom Fields'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'customFieldsEdit'), array('id' => 'edit-link', 'class' => 'divider'));
		}
		?>
	</h1>
	<div class="row">
		<select id="customfieldchoices">
		<?php foreach($CustomFieldModelLists as $k=> $arrModelval){
			echo '<option value="'.$k.'"'.(($defaultModel == $k || $defaultModel == '')?'selected ="selected"':'').'>'.__($arrModelval['label']).'</option>';
		} ?>
		</select>
		<!--
		<select id="customfieldchoices">
			<option value="InstitutionCustomField" <?php echo ($defaultModel == 'InstitutionCustomField' || $defaultModel == '')?'selected ="selected"':''; ?>>Institution Custom Fields</option>
			<option value="InstitutionSiteCustomField" <?php echo ($defaultModel == 'InstitutionSiteCustomField')?'selected ="selected"':''; ?>>Institution Site Custom Fields</option>
			<option value="CensusCustomField" <?php echo ($defaultModel == 'CensusCustomField')?'selected ="selected"':''; ?>>Census Custom Fields</option>
			<option value="StudentCustomField" <?php echo ($defaultModel == 'StudentCustomField')?'selected ="selected"':''; ?>>Student Custom Fields</option>
			<option value="CensusGrid" <?php echo ($defaultModel == 'CensusCustomGrid')?'selected ="selected"':''; ?>>Census Custom Table</option>
		</select>
		-->
	</div>
	
	<!-- if institution site or census -->
	<div class="row">
		<?php
		
			if(count($siteTypes)>0) 
			echo $this->Form->input('institution_site_type_id',
					array('id'=>'siteTypeid',
						  'options'=>$siteTypes,
						  'default'=>$sitetype
						 )
				 ); 
		?>
	</div>
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
