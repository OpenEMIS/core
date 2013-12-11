<?php
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('/Staff/js/staff', false);

$data = $data[0];
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="extracurricular" class="content_wrapper edit add">
	
    <h1>
        <span><?php echo __('Extracurricular'); ?></span>
        <?php 
        echo $this->Html->link(__('List'), array('action' => 'extracurricular'), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'extracurricularEdit', $data['StaffExtracurricular']['id']), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'extracurricularDelete', $data['StaffExtracurricular']['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
	
   
    <?php echo $this->element('alert'); ?>
    
    
    <div class="row">
        <div class="label"><?php echo __('School Year'); ?></div>
        <div class="value"><?php echo $data['SchoolYears']['name']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value"><?php echo $data['ExtracurricularType']['name']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Title'); ?></div>
        <div class="value"><?php echo $data['StaffExtracurricular']['name']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Start Date'); ?></div>
       <div class="value"><?php echo $this->Utility->formatDate($data['StaffExtracurricular']['start_date']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('End Date'); ?></div>
       <div class="value"><?php echo $this->Utility->formatDate($data['StaffExtracurricular']['end_date']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Hours'); ?></div>
        <div class="value"><?php echo $data['StaffExtracurricular']['hours']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Points'); ?></div>
        <div class="value"><?php echo $data['StaffExtracurricular']['points']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Location'); ?></div>
        <div class="value"><?php echo $data['StaffExtracurricular']['location']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $data['StaffExtracurricular']['comment']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['StaffExtracurricular']['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['StaffExtracurricular']['created']; ?></div>
    </div>
   
 
</div>