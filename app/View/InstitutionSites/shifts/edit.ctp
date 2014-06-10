<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('shift', false);
echo $this->Html->script('app.date', false);

echo $this->Html->css('../js/plugins/timepicker/bootstrap-timepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/timepicker/bootstrap-timepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Edit Shift'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'shifts'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<div id="shifts" class="content_wrapper edit add">
	<?php
//    echo $this->Form->create('InstitutionSiteShift', array(
//        'url' => array('controller' => 'InstitutionSites', 'action' => 'shiftsEdit'),
//        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
//    ));

	$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => 'shiftsEdit'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteShift', $formOptions);
	?>
	<?php
	echo $this->Form->hidden('id', array('value' => $shiftId));

	$labelOptions['text'] = $this->Label->get('Shift.name');
	echo $this->Form->input('name', array('label' => $labelOptions));

	$labelOptions['text'] = $this->Label->get('general.school_year');
	echo $this->Form->input('school_year_id', array('options' => $yearOptions, 'label' => $labelOptions));

	//echo $this->Form->input('start_time');
	echo $this->FormUtility->timepicker('start_time', array('id' => 'startTime'));

	//echo $this->Form->input('end_time');
	echo $this->FormUtility->timepicker('end_time', array('id' => 'endTime'));

	$labelOptions['text'] = $this->Label->get('general.location');
	echo $this->Form->input('location_institution_site_name', array('value' => $locationSiteName, 'id' => 'locationName', 'label' => $labelOptions));
	echo $this->Form->hidden('location_institution_site_id', array('value' => $locationSiteId, 'id' => 'locationInstitutionSiteId'));
	?>
	<?php //$objRequestData = @$this->request->data; ?>
    <div class="controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'shiftsView', $shiftId), array('class' => 'btn_cancel btn_left')); ?>
    </div>
	<?php echo $this->Form->end(); ?>
</div>

<?php $this->end(); ?>