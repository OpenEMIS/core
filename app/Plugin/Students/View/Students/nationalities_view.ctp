<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="nationalityView" class="content_wrapper">
    <h1>
        <span><?php echo __('Nationalities'); ?></span>
        <?php
        $data = $nationalityObj[0]['StudentNationality'];
        echo $this->Html->link(__('List'), array('action' => 'nationalities', $data['student_id']), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'nationalitiesEdit', $data['id']), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'nationalitiesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
        <div class="label"><?php echo __('Country'); ?></div>
        <div class="value"><?php echo $nationalityObj[0]['Country']['name']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $data['comments']; ?></div>
    </div>

    
   <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($nationalityObj[0]['ModifiedUser']['first_name'] . ' ' . $nationalityObj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($nationalityObj[0]['CreatedUser']['first_name'] . ' ' . $nationalityObj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>
    
</div>
