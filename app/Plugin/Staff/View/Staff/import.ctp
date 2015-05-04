<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Import Staff'));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.download_template'), array('action' => 'importTemplate'), array('class' => 'divider'));
	if(empty($uploadedName)){
		echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index'), array('class' => 'divider'));
	}else{
		echo $this->Html->link($this->Label->get('general.back'), array('action' => 'import'), array('class' => 'divider'));
	}
	
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'import'));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['id'] = $model;
$formOptions['type'] = 'file';
echo $this->Form->create($model, $formOptions);

echo $this->element('excel_import/import');

echo $this->Form->end();
$this->end(); 
?>


