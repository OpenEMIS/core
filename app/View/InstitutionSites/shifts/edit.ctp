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

$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => 'shiftsEdit'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('InstitutionSiteShift', $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('name');
echo $this->Form->input('academic_period_id', array('options' => $academicPeriodOptions));

echo $this->FormUtility->timepicker('start_time', array('id' => 'startTime'));
echo $this->FormUtility->timepicker('end_time', array('id' => 'endTime'));

$labelOptions['text'] = $this->Label->get('general.location');
echo $this->Form->input('location_institution_site_name', array('value' => $locationSiteName, 'id' => 'locationName', 'label' => $labelOptions));
echo $this->Form->hidden('location_institution_site_id', array('value' => $locationSiteId, 'id' => 'locationInstitutionSiteId'));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'shiftsView', $shiftId)));
echo $this->Form->end();

$this->end();
?>
