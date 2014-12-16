<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Student'));

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'add'));
echo $this->Form->create($model, $formOptions);

$labelOptions = $formOptions['inputDefaults']['label'];
$labelOptions['text'] = $this->Label->get('general.search');

$autocompleteArr = array(
	'labelOptions' => $labelOptions, 
	'placeholder' => 'OpenEMIS ID or Name', 
	'url' => $this->params['controller'] . '/' . $model . '/autocomplete',
	'linkWhenNoRecords' => '<span><a href="#" onclick="Autocomplete.submitForm(this);"> ' . __('Create') . ' ' . __('New') . '</a></span>'
);

echo $this->element('edit');
echo $this->element('autocomplete_field', $autocompleteArr);
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model)));
echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
echo $this->Form->end();
$this->end();
?>
