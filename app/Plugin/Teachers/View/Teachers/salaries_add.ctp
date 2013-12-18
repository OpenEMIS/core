<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->css('table', 'stylesheet', array('inline' => false)); ?>
<?php echo $this->Html->script('app.date', false); ?>
<?php echo $this->Html->script('/Teachers/js/salary'); ?>
<div id="salary" class="content_wrapper edit add">
   <h1>
        <span><?php echo __('Salaries'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'salaries'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php

    echo $this->Form->create('TeacherSalary', array(
        'url' => array('controller' => 'Teachers', 'action' => 'salariesAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
       <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'issue_date',array('desc' => true)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Gross Salary'); ?></div>
        <div class="value"><?php echo $this->Form->input('gross_salary'); ?></div>
    </div>
        
    <fieldset class="section_group">
    <legend><?php echo __('Additions');?></legend>
     <div class="row">
        <div class="label"><?php echo __('Total Additions'); ?></div>
        <div class="value"><?php echo $this->Form->input('additions'); ?></div>
    </div>
    <div class="table full_width">
        <div class="delete deleteAddition" name="data[DeleteAddition][{index}][id]"></div>
        <div class="table_head">
            <div class="table_cell cell_title"><?php echo __('Type'); ?></div>
            <div class="table_cell"><?php echo __('Amount'); ?></div>
            <div class="table_cell cell_delete">&nbsp;</div>
        </div>
            
        <div class="table_body additions">
            
        </div>
        <br />
        <div class="row">
        <a class="void icon_plus link_add" onclick="Salary.addAddition(this)"><?php echo __('Add') .' '. __('Addition'); ?></a>
        </div>
    </div>
    </fieldset>

    <fieldset class="section_group">
    <legend><?php echo __('Deductions');?></legend>
    <div class="row">
        <div class="label"><?php echo __('Total Deductions'); ?></div>
        <div class="value"><?php echo $this->Form->input('deductions'); ?></div>
    </div>
    <div class="table full_width">
        <div class="delete deleteDeduction" name="data[DeleteDeduction][{index}][id]"></div>
        <div class="table_head">
            <div class="table_cell cell_title"><?php echo __('Type'); ?></div>
            <div class="table_cell"><?php echo __('Amount'); ?></div>
            <div class="table_cell cell_delete">&nbsp;</div>
        </div>
            
        <div class="table_body deductions">
            
        </div>
        <br />
        <div class="row">
        <a class="void icon_plus link_add" onclick="Salary.addAddition(this)"><?php echo __('Add') .' '. __('Deductions'); ?></a>
        </div>
    </div>

    
    </fieldset>


    <div class="row">
        <div class="label"><?php echo __('Net Salary'); ?></div>
        <div class="value"><?php echo $this->Form->input('net_salary'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('comment', array('type'=>'textarea')); ?>
        </div>
    </div>
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'salaries'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>