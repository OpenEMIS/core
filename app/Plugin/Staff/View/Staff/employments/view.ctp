<?php /* 
<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="employmentView" class="content_wrapper">
    <h1>
        <span><?php echo __('Employment'); ?></span>
        <?php
        $data = $employmentObj[0]['StaffEmployment'];
        echo $this->Html->link(__('List'), array('action' => 'employments', $data['staff_id']), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'employmentsEdit', $data['id']), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'employmentsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value"><?php echo $employmentObj[0]['EmploymentType']['name']; ?></div>
    </div>

    
    <div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['employment_date']); ?></div>
    </div>
    
      <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $data['comment']; ?></div>
    </div>

   <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($employmentObj[0]['ModifiedUser']['first_name'] . ' ' . $employmentObj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($employmentObj[0]['CreatedUser']['first_name'] . ' ' . $employmentObj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>
    
</div>
 * 
 */?>
<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'employments'), array('class' => 'divider'));
if($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'employmentsEdit', $id), array('class' => 'divider'));
}
if($_delete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'employmentsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
