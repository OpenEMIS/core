<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('shift', false);
echo $this->Html->script('app.date', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Shift'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'shifts'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<div id="shifts" class="content_wrapper edit add">
	<?php
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => 'shiftsAdd'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteShift', $formOptions);

//    echo $this->Form->create('InstitutionSiteShift', array(
//        'url' => array('controller' => 'InstitutionSites', 'action' => 'shiftsAdd'),
//        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
//    ));

	$labelOptions['text'] = $this->Label->get('Shift.name');
	echo $this->Form->input('name', array('label' => $labelOptions));

	$labelOptions['text'] = $this->Label->get('general.school_year');
	echo $this->Form->input('school_year_id', array('options' => $yearOptions, 'label' => $labelOptions));

	echo $this->Form->input('start_time');

	echo $this->Form->input('end_time');

	$labelOptions['text'] = $this->Label->get('general.location');
	echo $this->Form->input('location_institution_site_name', array('value' => $institutionSiteName, 'id' => 'locationName', 'label' => $labelOptions));
	echo $this->Form->input('location_institution_site_id', array('value' => $institutionSiteId, 'type' => 'hidden', 'id' => 'locationInstitutionSiteId'));
	?>
    <div class="controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'shifts'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>