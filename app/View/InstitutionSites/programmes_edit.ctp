<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('programmes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="programmes" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Programmes'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'programmes'), array('class' => 'divider')); ?>
	</h1>
	<?php
	echo $this->Form->create('InstitutionSiteProgramme', array(
		'id' => 'InstitutionSiteProgramme',
		'url' => array('controller' => 'InstitutionSites', 'action' => 'programmesEdit'),
		'model' => 'InstitutionSiteProgramme'
	));
	//pr($data);
	$ctr = 1;
	foreach($data as $System => $arrEduc){
		echo '<fieldset class="section_group">
			  <legend>'.__($System).'</legend>';
		foreach($arrEduc as $level => $arrProgrammes){
			echo '<fieldset class="section_break">
		<legend>
			<span>'.$level.'</span>
		</legend>
				<div class="table">
					<div class="table_head">
							<div class="table_cell cell_prog">'.__('Programme').'</div>
							<div class="table_cell">'.__('Cycle').'</div>
							<div class="table_cell">'.__('Field of Study').'</div>
							<div class="table_cell cell_status">'.__('Status').'</div>
					</div>
					<div class="table_body">';
				
				foreach($arrProgrammes as $arrProgrammesDetails){
					
					//pr($arrProgrammesDetails);die;
					echo '<div class="table_row">
								<div class="table_cell">'.$arrProgrammesDetails['EducationProgramme']['name'].'</div>
								<div class="table_cell">'.$arrProgrammesDetails['EducationCycle']['name'].'</div>
								<div class="table_cell">'.$arrProgrammesDetails['EducationFieldOfStudy']['name'].'</div>
								<div class="table_cell">
									<input type="hidden" name="data[InstitutionSiteProgramme]['.$ctr.'][id]" value="'.$arrProgrammesDetails['InstitutionSiteProgramme']['id'].'">
									<select name="data[InstitutionSiteProgramme]['.$ctr.'][status]">
										<option value="0" '.($arrProgrammesDetails['InstitutionSiteProgramme']['status'] == 0?'Selected="Selected"':'').'>'.__('Close').'</option>
										<option value="1" '.($arrProgrammesDetails['InstitutionSiteProgramme']['status'] == 0?'':'Selected="Selected"').'>'.__('Open').'</option>
									</select>
								
								</div>
						</div>';
				$ctr++;
				}
			echo '  </div>
				</div>
				
				</fieldset>';
		}
				
		echo '</fieldset>';
	}
	
		if(count($data) > 0){
			echo '<div class="controls">
					<input type="submit" value="'.__('Save').'" class="btn_save btn_right" onClick="return Programmes.validateEdit();" />
					<input type="button" value="'.__('Cancel').'" class="btn_cancel btn_left" onClick="Programmes.hide(\'ProgrammesAdd\')" />
				</div>';
		}else{
			
			echo '<div class="controls" style="border:0">'.__('No Available Programmes').'</div>';
		}
	
	echo $this->Form->end(); ?>
</div>