<?php echo $this->element('breadcrumb'); ?>
<?php 
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('shift', false);
echo $this->Html->script('app.date', false);
?>
<div id="shifts" class="content_wrapper edit add">
    <h1>
        <span><?php echo __('Shifts'); ?></span>
        <?php
        echo $this->Html->link(__('Back'), array('action' => 'shifts'), array('class' => 'divider'));
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    echo $this->Form->create('InstitutionSiteShift', array(
        'url' => array('controller' => 'InstitutionSites', 'action' => 'shiftsEdit'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php echo $this->Form->hidden('id', array('value' => $shiftId)); ?>
    <?php //$objRequestData = @$this->request->data; ?>
    <div class="row">
        <div class="label"><?php echo __('Shift Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('name'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('School Year'); ?></div>
        <div class="value"><?php echo $this->Form->input('school_year_id', array('empty' => __('--Select--'), 'options' => $yearOptions)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Start Time'); ?></div>
        <div class="value"><?php echo $this->Form->input('start_time'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('End Time'); ?></div>
        <div class="value"><?php echo $this->Form->input('end_time'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Location'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('location_institution_site_name', array('id' => 'locationName', 'value' => $locationSiteName)); ?>
            <?php echo $this->Form->hidden('location_institution_site_id', array('id' => 'locationInstitutionSiteId', 'value' => $locationSiteId)); ?>
        </div>
    </div>
    <div class="controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'shiftsView', $shiftId), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>