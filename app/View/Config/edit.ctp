<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="config" class="content_wrapper">
	<h1>
		<span><?php echo __('System Configurations'); ?></span>
		<?php echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'index') , array('class' => 'divider link_view')); ?>
		<?php echo $this->Html->link(__('Dashboard Image'), array('controller' => 'Config', 'action' => 'dashboard'), array('class' => 'divider')); ?>
		<!-- <a class="void link-view divider">View</a> -->
	</h1>

	<?php
	echo $this->Form->create('save', array(
		    'inputDefaults' => array(
		        'label' => false,
		        'div' => false
		    ),
			'url' => array(
				'controller' => 'Config',
				'action' => 'save'
			),
			'id' => 'ConfigurationSaveEdit',
			'type' => 'file'
		)
	);
	?>
	<!-- Items -->
		<?php 
		if(isset($items)) {
			foreach($items as $key => $element){ 
				if(isset($element) && sizeof($element) > 0) { 
		?>
	<fieldset class="section_break">
		<legend><?php echo __(ucwords($key)); ?></legend>
		<?php
		if($key == 'custom validation'){
			$str = '';
			foreach($element as $innerKey => $innerElement){ 
				if($innerElement['name'] == 'special_characters'){
					$str = $innerElement['value'];
				}
			}
			echo "<div style='padding:5px 0 0 5px;'><b>N</b>(numbers) | <b>C</b>(Character) | $str (Special Chars)</div>";
		}
		?>
		<div class="table">
			<div class="table_body">
		<?php 
		$arrOptions = array('date_format' => array(
								'Y-m-d' => date('Y-m-d'),
								'd-M-Y' => date('d-M-Y'),
								'd-m-Y' => date('d-m-Y'),
								'd/m/Y' => date('d/m/Y'),
								'm/d/Y' => date('m/d/Y'),
								'd F Y' => date('d F Y'), 
								'F d, Y' => date('F d, Y'), 
								'dS F Y' => date('dS F Y')
							),
							'language' =>array(
								'ara' => 'العربية',
								'chi' => '中文',
								'eng' => 'English',
								'fre' => 'Français',
								'rus' => 'русский',
								'spa' => 'español'
							),
							'yearbook_orientation' => array(
								'0' => 'Portrait',
								'1' => 'Landscape'
							),
							'yearbook_school_year' => $school_years,
							'school_year' => $school_years
							);
		foreach($element as $innerKey => $innerElement){ 
				$item = $innerElement; 
				
				if($item['name'] == 'special_characters') continue;
				$addClass = ($item['type'] == 'custom validation')?'custom_validation':'';
		?>
		
			<div class="table_row <?php echo ($key+1)%2==0? 'even':''; ?>">
			<?php if($item['visible']>0) echo $this->Form->hidden('ConfigItem.'. $key . '.' . $innerKey . '.id', array('value' => $item['id'])); ?>
				<div class="table_cell cell_item_name"><?php echo __($item['label']); ?></div>
				<div class="table_cell cell_item_value">

				<?php 
					if($item['visible']>0){
						$options = array(
							'value' => $item['value'],
							'class' => 'default '.$addClass
						);
							$options['maxlength'] = 300;
						if(stristr($item['name'], 'dashboard_notice')){
							echo $this->Form->textarea('ConfigItem.'. $key . '.' . $innerKey . '.value', $options);
						}elseif (stristr($item['name'], 'publication_date')) {
							echo $this->Utility->getDatePicker($this->Form, 'publication_date', array('name' => 'ConfigItem['.$key.']['.$innerKey.'][value]', 'order' => 'dmy', 'desc' => true, 'value' => (empty($item['value']))?$item['default_value']:$item['value']));
						}elseif (stristr($item['name'], 'yearbook_publication_date')) {

							$publicationDateOptions = $options = array(
								'value' => $item['value'],
								'type' => 'date',
								'dateFormat' => 'DMY'
							);
							echo $this->Form->input('ConfigItem.'. $key . '.' . $innerKey . '.value', $publicationDateOptions);

						}elseif (stristr($item['name'], 'yearbook_logo')) {
							echo $this->Form->input('ConfigItem.'. $key . '.' . $innerKey . '.file_value', array('type' => 'file', 'class' => 'form-error'));
							echo $this->Form->hidden('ConfigItem.'. $key . '.' . $innerKey . '.value', array('value'=> (empty($item['value']))?$item['default_value']:$item['value'] ));
							echo $this->Form->hidden('ConfigItem.'. $key . '.' . $innerKey . '.reset_yearbook_logo', array('value'=>'0'));
							echo "<span id=\"resetDefault\" class=\"icon_delete\"></span>";
					        echo isset($imageUploadError) ? '<div class="error-message">'.$imageUploadError.'</div>' : '';
					        echo "<br/>";
					        echo "<div id=\"image_upload_info\"><em>";
				            echo sprintf(__("Max Resolution: %s pixels"), '400 x 514')."<br/>";
				            echo __("Max File Size:"). ' 200 KB' ."<br/>";
				            echo __("Format Supported:"). " .jpg, .jpeg, .png, .gif".
				            "</em>
				            </div>";
		
						} elseif($item['name'] == 'yearbook_school_year'){
							$options = $arrOptions[$item['name']];
							$arrCond = array('escape' => false, 'empty' => false, 'value' => (empty($item['value']))?$item['default_value']:$item['value']);

							echo $this->Form->hidden('ConfigItem.'. $key . '.' . $innerKey . '.id', array('value' => $item['id']));
							echo $this->Form->select('ConfigItem.'. $key . '.' . $innerKey . '.value', $options, $arrCond);
							
						} elseif($item['name'] == 'student_prefix' || $item['name'] == 'teacher_prefix' || $item['name'] == 'staff_prefix'){
							$itemsVal = explode(",", $item['value']);
							echo $this->Form->input('ConfigItem.'. $key . '.' . $innerKey . '.value.enable',
													array('label'=>'Enabled', 'div' => false, 'type'=>'checkbox', 
													 'style'=>'width: 30px;', 'checked' => $itemsVal[1]));
							echo '&nbsp;&nbsp;';
							echo $this->Form->input('ConfigItem.'. $key . '.' . $innerKey . '.value.prefix', 
													array('default' => $itemsVal[0], 'label'=>false, 'div' => false, 
													'class' => 'default', 'style'=>'width: 100px;'));
						}elseif(array_key_exists($item['name'], $arrOptions)){
							$options = $arrOptions[$item['name']];
							$arrCond = array('escape' => false, 'empty' => false, 'value' => (empty($item['value']))?$item['default_value']:$item['value']);
							/*if($item['name'] == 'language'){
								$arrCond['disabled'] = 'disabled';
							}*/

							echo $this->Form->select('ConfigItem.'. $key . '.' . $innerKey . '.value', $options, $arrCond);
						}elseif(array_key_exists($item['name'], $arrOptions)){
							$options = $arrOptions[$item['name']];
							echo $this->Form->select('ConfigItem.'. $key . '.' . $innerKey . '.value', $options, array('escape' => false, 'empty' => false, 'value' => (empty($item['value']))?$item['default_value']:$item['value']));
						}else{
							
							if(strtolower($item['label']) == 'currency'){
								$options = array_merge ($options,array('maxlength'=>'3'));
								
							}
							
							echo $this->Form->input('ConfigItem.'. $key . '.' . $innerKey . '.value', $options);	

						}

					}else{
						echo $item['value'];
					}
				?>
				</div>
			</div>
			<?php } ?>
		
			</div>
		</div>
	</fieldset>
		<?php 
				}
			}
		} 
		?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>

<script type="text/javascript">
$(document).ready(function() {

    $('#resetDefault').click(function(e){
        e.preventDefault();
        var photoContent = $('input[id^="ConfigItemYearbook"][id$="FileValue"]');
        var resetImage= $('input[id^="ConfigItemYearbook"][id$="ResetYearbookLogo"]');
        

        if (photoContent.attr('disabled')){
            photoContent.removeAttr('disabled');
            resetImage.attr('value', '0');
        }else {
            photoContent.attr('disabled', 'disabled');
            resetImage.attr('value', '1');
        }
    });
});
</script>