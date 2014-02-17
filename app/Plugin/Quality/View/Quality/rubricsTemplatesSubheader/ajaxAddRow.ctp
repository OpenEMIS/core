<?php
	$this->RubricsView->fieldSetupOptions = array('label' => false, 'div'=> array('class'=>'input_wrapper'), 'autocomplete' => 'off');
	$this->RubricsView->defaultNoOfColumns = $totalColumns;
	
        
        $displayEvenClass = (($lastId %2) == 0)? 'li_even': '';
        echo sprintf('<li data-id="%s" class="%s">', $lastId, $displayEvenClass);
	if($type == 'header'){
		echo $this->RubricsView->insertRubricHeader($processItem, $lastId);
	}
	else{
		//$item['columnHeader'] = $columnHeaderData;
		echo $this->RubricsView->insertRubricQuestionRow($processItem , $lastId);
	}
        echo $this->Utility->getListRowEnd();

?>