<?php
	$this->RubricsView->fieldSetupOptions = array('label' => false, 'div'=> array('class'=>'input_wrapper'), 'autocomplete' => 'off');
	$this->RubricsView->defaultNoOfColumns = $totalColumns;
	
	if($type == 'header'){
		echo $this->RubricsView->insertRubricHeader($processItem, $lastId);
	}
	else{
		//$item['columnHeader'] = $columnHeaderData;
		echo $this->RubricsView->insertRubricQuestionRow($processItem , $lastId);
	}
?>