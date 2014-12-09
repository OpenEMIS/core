<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Section'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'add', $selectedYear));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('institution_site_id', array('value' => $institutionSiteId));
echo $this->Form->input('school_year_id', array(
	'options' => $yearOptions, 
	'url' => $this->params['controller'] . '/' . $model . '/add',
	'default' => $selectedYear,
	'onchange' => 'jsForm.change(this)'
));
echo $this->Form->input('name');

?>

<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'index', $selectedYear)));
echo $this->Form->end();

$this->end(); 
?>
