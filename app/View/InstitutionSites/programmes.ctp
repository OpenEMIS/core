<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('programmes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="programmes" class="content_wrapper">
	<h1>
		<span><?php echo __('Programmes'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array(), array('class' => 'divider void', 'onclick' => "Programmes.show('ProgrammesAdd')"));
		}
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'programmesEdit'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php //pr($data);
		
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
                            echo '<div class="table_row">
                                        <div class="table_cell">'.$arrProgrammesDetails['EducationProgramme']['name'].'</div>
                                        <div class="table_cell">'.$arrProgrammesDetails['EducationCycle']['name'].'</div>
                                        <div class="table_cell">'.$arrProgrammesDetails['EducationFieldOfStudy']['name'].'</div>
                                        <div class="table_cell cell_status">'.
                                        ($arrProgrammesDetails['InstitutionSiteProgramme']['status'] == 0?'<span style="color: red">&#10005;</span> Close':'<span style="color: green">&#10003;</span> Open')
                                        .'
                                        </div>
                                </div>';
                        }
                echo '      </div>
                        </div>
                        </fieldset>';
            }
            echo '</fieldset>';
        }
    ?>
	
	
	<?php 
	if($_add) {
		echo $this->Form->create('InstitutionSiteProgramme', array(
			'id' => 'InstitutionSiteProgramme',
			'url' => array('controller' => 'InstitutionSites', 'action' => 'programmes')
		));
	?>
	<fieldset class="section_group" id="ProgrammesAdd"  <?php echo (count($data) > 0?'style="visibility:hidden"':""); ?>>
		<legend>
			<select onchange="Programmes.getAvailableProgrammeList(this)" autocomplete="off">
				<option value="0"><?php echo __('--Select--'); ?></option>
				<?php 
					foreach ($arrEducationSystems as $id => $val){
						echo '<option value="'.$id.'">'.$val.'</option>';
					}
				?>
			</select>
		</legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_checkbox">&nbsp;</div>
				<div class="table_cell cell_prog"><?php echo __('Programme'); ?></div>
				<div class="table_cell"><?php echo __('Level'); ?></div>
				<div class="table_cell"><?php echo __('Cycle'); ?></div>
				<div class="table_cell"><?php echo __('Field of Study'); ?></div>

			</div>

			<div id="records" class="table_body">
			</div>
		</div>
		<div id="no-programme" class="row" <?php echo (count($data) > 0?'style="display: none;"':""); ?>>
			<div style="width:100%;text-align:center;"><?php echo __('No Available Programmes'); ?></div>    
		</div>
		<?php if(sizeof($data)==0) { ?>
	
	<?php } ?>
		<div class="controls" style="<?php echo (sizeof($data)==0)?'display:none':'';?>">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return Programmes.validateAdd();" />
			<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" onClick="Programmes.hide('ProgrammesAdd')" />
		</div>
	</fieldset>
	<?php 
		echo $this->Form->end();
	} // end if $_add
	?>
</div>
