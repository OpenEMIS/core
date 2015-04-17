<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/quality.rubric', false);
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'student');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'qualityRubricHeader', $selectedQualityRubricId, $rubricTemplateId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->Form->create($modelName, array(
	//'url' => array('controller' => 'Quality','plugin'=>'Quality'),
	//'type' => 'file',
	'novalidate' => true,
	'inputDefaults' => array('label' => false, 'div' => array('class' => 'input_wrapper'), 'autocomplete' => 'off')
));
?>
<table class='rubric-table'>
	<?php
	$this->RubricsView->defaultNoOfColumns = $totalColumns;
	//$this->RubricsView->defaultNoOfRows = $totalRows;
	//$options = array('columnHeader'=> $columnHeaderData);
	//pr($options);
	foreach ($this->data['RubricsTemplateDetail'] as $key => $item) {
		$item['editable'] = $editable;

		if (array_key_exists('RubricsTemplateSubheader', $item)) {
			echo $this->RubricsView->insertRubricTableHeader($item, $key, NULL);
		} else {
			$item['columnHeader'] = $columnHeaderData;
			echo $this->RubricsView->insertQualityRubricTableQuestionRow($item, $key, NULL);
		}
	}
	?>
</table><br/>
<div class="form-group ">
	<div class=" center">
		<?php
		if ($editable === 'true') {
			echo $this->Form->submit($this->Label->get('general.save'), array('class' => 'btn_save btn_right', 'div' => false));
		}
		echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'qualityRubricHeader', $selectedQualityRubricId, $rubricTemplateId), array('class' => 'btn_cancel btn_left'));
		?>
	</div>
</div>
<?php
echo $this->Form->end();
$this->end();
?>  
