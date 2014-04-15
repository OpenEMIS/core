<?php /*

<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="identityView" class="content_wrapper">
    <h1>
        <span><?php echo __('Identities'); ?></span>
        <?php
 
        $data = $identityObj['StudentIdentity'];
        echo $this->Html->link(__('List'), array('action' => 'identities', $data['student_id']), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'identitiesEdit', $data['id']), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'identitiesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value"><?php echo $identityObj['IdentityType']['name']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Number'); ?></div>
        <div class="value"><?php echo $data['number']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Issue Date'); ?></div>
        <div class="value"><?php echo $data['issue_date']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Expiry Date'); ?></div>
        <div class="value"><?php echo $data['expiry_date']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Issue Location'); ?></div>
        <div class="value"><?php echo $data['issue_location']; ?></div>
    </div>

      <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $data['comments']; ?></div>
    </div>

    
   <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($identityObj['ModifiedUser']['first_name'] . ' ' . $identityObj['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($identityObj['CreatedUser']['first_name'] . ' ' . $identityObj['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>
    
</div>
*/ ?>

<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'identities'), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'identitiesEdit', $id), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'identitiesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
