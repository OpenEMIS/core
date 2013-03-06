<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Teachers/js/training', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Training'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'training'), array('class' => 'divider')); ?>
	</h1>

		<?php
	    echo $this->Form->create('TeacherTraining', array(
	        'url' => array('controller' => 'Teachers', 'action' => 'trainingEdit'),
	        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	    ));
	    ?>
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_title"><?php echo __('Completed Date'); ?></div>
                <div class="table_cell"><?php echo __('Category'); ?></div>
				<div class="table_cell cell_delete">&nbsp;</div>
			</div>
			
			<div class="table_body">
			<?php
			if(count($data) > 0){
				$ctr = 1;
				// pr($data);
				foreach($data as $arrVal){
				   	// echo '<div class="table_row" data-id="'.$arrVal['id'].'">
				   	 echo '<div class="table_row" id="training_row_'.$arrVal['id'].'" data-id="'.$arrVal['id'].'">
				   			<input type="hidden" value="'.$arrVal['id'].'" name="data[TeacherTraining]['.$ctr.'][id]" />
							<div class="table_cell">'.$this->Utility->getDatePicker($this->Form, $ctr.']['.'completed_date', array('desc' => true,'value' => $arrVal['completed_date'])) .'</div>
							<div class="table_cell"><select value="'.$arrVal['teacher_training_category_id'].'" name="data[TeacherTraining]['.$ctr.'][teacher_training_category_id]" class="training_category">';

					foreach($categories as $category) {
						$selected = ($arrVal['teacher_training_category_id'] === $category['TeacherTrainingCategory']['id']) ? "selected" : "";
						echo '<option value="'.$category['TeacherTrainingCategory']['id'].'"'.$selected.'>'.$category['TeacherTrainingCategory']['name'].'</option>';
					}
					
					echo '</select></div>
							<div class="table_cell"><span class="icon_delete" title="'.__("Delete").'" onClick="Training.confirmDeletedlg('.$arrVal['id'].')"></span></div></div>';
				   $ctr++;
				}
			}
			?>
			</div>
		</div>

		<?php if($_add) { ?>
		<div class="row"><a class="void icon_plus link_add"><?php echo __('Add') .' '. __('Training'); ?></a></div>
		<?php } ?>

		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return Training.validateAdd();" />
			<?php echo $this->Html->link(__('Cancel'), array('action' => 'training'), array('class' => 'btn_cancel btn_left')); ?>
		</div>
		<?php echo $this->Form->end(); ?>
</div>