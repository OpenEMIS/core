<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="institutions" class="content_wrapper">
	<h1>
		<span><?php echo __('Institutions'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'institutions'), array('class' => 'divider'));
		?>
	</h1>
	
	<?php
	echo $this->Form->create('InstitutionSiteStaff', array(
		'url' => array('plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'institutionsEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Institution'); ?></div>
			<div class="table_cell cell_year_month"><?php echo __('Start Date'); ?></div>
			<div class="table_cell cell_year_month"><?php echo __('End Date'); ?></div>
			<div class="table_cell cell_delete">&nbsp;</div>
		</div>
		
		<div class="table_body">
			<?php 
			if(count($records) > 0){
			$ctr = 1;
			foreach ($records as $record){
				echo '<div class="table_row" id="institution_row_'.$record['InstitutionSiteStaff']['id'].'">';
				echo '<div class="table_cell">
						<input type="hidden" value="'.$record['InstitutionSiteStaff']['id'].'" name="data[InstitutionSiteStaff]['.$ctr.'][id]" />
						<select class="full_width" name="data[InstitutionSiteStaff]['.$ctr.'][institution_site_id]">';

						foreach ($institutions as $arrInstitutionValue){
                        	$selected = ($record['InstitutionSiteStaff']['institution_site_id'] == $arrInstitutionValue['InstitutionSite']['id']) ? "selected=selected" : null;
                        	echo "<option value=".$arrInstitutionValue['InstitutionSite']['id']." ".$selected.">".$arrInstitutionValue['Institution']['name']." - ".$arrInstitutionValue['InstitutionSite']['name']."</option>";
                        }

				echo 	'</select>
					 </div>
					 <div class="table_cell cell_start_date">'.
						$this->Utility->getDatePicker($this->Form, 'start_date', array('order' => 'my', 'name' => "data[InstitutionSiteStaff][{$ctr}][start_date]", 'value' => $record['InstitutionSiteStaff']['start_date']));
				echo '</div>
					<div class="table_cell cell_end_date">'.
						$this->Utility->getDatePicker($this->Form, 'end_date', array('order' => 'my', 'name' => "data[InstitutionSiteStaff][{$ctr}][end_date]", 'value' => $record['InstitutionSiteStaff']['end_date']));
				echo '</div>
					<div class="table_cell"><span class="icon_delete" title="'.__("Delete").'" onClick="objStaff.confirmDeletedlg('.$record['InstitutionSiteStaff']['id'].')"></span></div>
				</div>';
				$ctr++;
				}
			}
			?>
		</div>
	</div>
	
	<?php if($_add) { ?>
		<!-- <div class="row"><a class="void icon_plus link_add">Add Training</a></div> -->
		<div class="row"><a id="institutions" class="void icon_plus"><?php echo __('Add') .' '. __('Institution'); ?></a></div>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return objStaff.validateAdd();" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'institutions'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
