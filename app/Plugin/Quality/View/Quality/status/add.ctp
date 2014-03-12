<?php
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php $action = ($displayType == 'add')?'statusAdd':'statusEdit'; ?>
    
    <?php
    echo $this->Form->create($modelName, array(
        'url' => array('controller' => 'Quality', 'action' => $action, 'plugin' => 'Quality'),
        'type' => 'file',
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
    <?php //echo $this->Form->input('institution_id', array('type'=> 'hidden'));  ?>
    
    <?php 
        if($displayType == 'add'){
            $nameField = $this->Form->input('rubric_template_id', array('options' => $rubricOptions));
            $yearField = $this->Form->input('year', array('options' => $yearOptions));
           /* $yearField  = $this->Utility->getYearList($this->Form, 'data[year]', array(
                        'name' => "data[".$modelName."][year]",
                        'id' => "year_id",
                        'maxlength' => 30,
                        'desc' => true,
                        'label' => false,
                        'default' => $selectedYear,
                        'div' => false), true);*/
        }
        else{
            $nameField = $rubricOptions[$this->data['QualityStatus']['rubric_template_id']];
            $yearField = $this->data['QualityStatus']['year'];
        }
    ?>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $nameField;//$this->Form->input('rubric_template_id', array('options' => $rubricOptions)); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Year'); ?></div>
        <div class="value"><?php echo $yearField; ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Status'); ?></div>
        <div class="value"><?php echo $this->Form->input('status', array('options' => $statusOptions)); ?> </div>
    </div>
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'status'), array('class' => 'btn_cancel btn_left')); ?>
    </div>

    <?php echo $this->Form->end(); ?>
</div>