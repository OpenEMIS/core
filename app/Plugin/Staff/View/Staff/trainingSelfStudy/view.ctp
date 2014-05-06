<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>
<?php 
$obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="training_self_study" class="content_wrapper">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
			echo $this->Html->link(__('List'), array('action' => 'trainingSelfStudy' ), array('class' => 'divider'));
			if($_edit) {
				if($obj['training_status_id'] == 1){
					echo $this->Html->link(__('Edit'), array('action' => 'trainingSelfStudyEdit',$obj['id'] ), array('class' => 'divider'));
				}
			}
			if($_delete) {
				if($obj['training_status_id'] == 1){
					echo $this->Html->link(__('Delete'), array('action' => 'trainingSelfStudyDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
				}
			}
			if($_execute) {
				if($obj['training_status_id']==3){
					echo $this->Html->link(__('Inactivate'), array('action' => 'trainingSelfStudyInactivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmInactivate(this)'));
				}
			}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Course Title'); ?></div>
			<div class="value"><?php echo $obj['title'];?></div>
		</div>
	  	<div class="row">
			<div class="label"><?php echo __('Start Date'); ?></div>
			<div class="value"><?php echo $obj['start_date']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('End Date'); ?></div>
			<div class="value"><?php echo $obj['end_date']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Course Description'); ?></div>
			<div class="value"><?php echo $obj['description']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Course Goal / Objectives'); ?></div>
			<div class="value"><?php echo $obj['objective']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Location'); ?></div>
			<div class="value"><?php echo $obj['location']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Provider'); ?></div>
			<div class="value"><?php echo $data['TrainingProvider']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Hours'); ?></div>
			<div class="value"><?php echo $obj['hours']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Credits'); ?></div>
			<div class="value"><?php echo $obj['credit_hours']; ?></div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Result'); ?></div>
			<div class="value">
				<?php echo $obj['result']; ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Completed'); ?></div>
			<div class="value">
				<?php if(!isset($obj['pass'])){
                		echo '-';
                 }else if($obj['pass'] == 1){
                 		echo __('Passed');
                 }else{
                 		echo __('Failed');
                 }
                 ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($modelName,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
		</div>

		<div class="row">
	        <div class="label"><?php echo __('Attachments'); ?></div>
	        <div class="value">
			<?php if(!empty($attachments)){?>
	        <?php foreach($attachments as $key=>$value){ 
		        $obj = $value[$_model];
				$link = $this->Html->link($obj['name'], array('action' => 'attachmentsTrainingSelfStudyDownload', $obj['id']));
		        echo $link . '<br />'; 
	        } ?>
    		<?php }?>
	    	</div>
	    </div>

        <div class="row">
            <div class="label"><?php echo __('Modified by'); ?></div>
            <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Modified on'); ?></div>
            <div class="value"><?php echo $obj['modified']; ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created by'); ?></div>
            <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created on'); ?></div>
            <div class="value"><?php echo $obj['created']; ?></div>
        </div>
        <?php echo $this->element('Training.workflow');?>
</div>
 * 
 */?>




<?php
echo $this->Html->script('search', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$obj = $data[$model]; 
$this->start('contentActions');


echo $this->Html->link($this->Label->get('general.list'), array('action' => 'trainingSelfStudy' ), array('class' => 'divider'));
if($_edit) {
	if($obj['training_status_id'] == 1){
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'trainingSelfStudyEdit',$obj['id'] ), array('class' => 'divider'));
	}
}
if($_delete) {
	if($obj['training_status_id'] == 1){
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'trainingSelfStudyDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
}
if($_execute) {
	if($obj['training_status_id']==3){
		echo $this->Html->link($this->Label->get('StaffTraining.inactivate'), array('action' => 'trainingSelfStudyInactivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmInactivate(this)'));
	}
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($modelName,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
</div>

<?php 
echo $this->element('layout/view', array('fields' => $fields2, 'data' => $data));
echo $this->element('Training.workflow');
$this->end();
?>
