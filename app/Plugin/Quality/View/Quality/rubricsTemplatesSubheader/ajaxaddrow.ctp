<?php
	$this->RubricsView->fieldSetupOptions = array('label' => false, 'div'=> array('class'=>'input_wrapper'), 'autocomplete' => 'off');
	$this->RubricsView->defaultNoOfColumns = $totalColumns;
	
        
        $displayEvenClass = (($lastId %2) == 1)? 'li_even': '';
        $html = sprintf('<li data-id="%s" class="%s">', $lastId, $displayEvenClass);
	if($type == 'header'){
		$html .= $this->RubricsView->insertRubricHeader($processItem, $lastId);
	}
	else{
		//$item['columnHeader'] = $columnHeaderData;
		$html .= $this->RubricsView->insertRubricQuestionRow($processItem , $lastId);
	}
        $html .= '</li>';//$this->Utility->getListRowEnd();
        
        echo json_encode(array('html'=> $html, 'message' => $message));

?>