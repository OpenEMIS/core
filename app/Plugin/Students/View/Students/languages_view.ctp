<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="languageView" class="content_wrapper">
    <h1>
        <span><?php echo __('Languages'); ?></span>
        <?php
        $data = $languageObj[0]['StudentLanguage'];
        echo $this->Html->link(__('List'), array('action' => 'languages', $data['student_id']), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'languagesEdit', $data['id']), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'languagesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
        <div class="label"><?php echo __('Language'); ?></div>
        <div class="value"><?php echo $languageObj[0]['Language']['name']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Listening'); ?></div>
        <div class="value"><?php echo $data['listening']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Speaking'); ?></div>
        <div class="value"><?php echo $data['speaking']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Reading'); ?></div>
        <div class="value"><?php echo $data['reading']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Writing'); ?></div>
        <div class="value"><?php echo $data['writing']; ?></div>
    </div>
   <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($languageObj[0]['ModifiedUser']['first_name'] . ' ' . $languageObj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($languageObj[0]['CreatedUser']['first_name'] . ' ' . $languageObj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>
    
</div>
