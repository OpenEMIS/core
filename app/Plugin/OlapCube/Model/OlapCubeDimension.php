<?php

class OlapCubeDimension extends OlapCubeAppModel {
	//public $useTable = 'student_health_histories';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		'OlapCube',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	public $headerDefault = 'OLAP';

	public function olapReport($controller, $params){
		$olapCube = ClassRegistry::init('OlapCube');

		$cubeOptions = $olapCube->find('list', array('fields'=>array('id','cube'),'conditions'=>array('visible'=>1), 'order'=>array('order')));
		$controller->set('cubeOptions', $cubeOptions);

        $cubeOptionsId = isset($params['pass'][0]) ? $params['pass'][0] : key($cubeOptions);
        $cubeCriteriaId = isset($params['pass'][1]) ? $params['pass'][1] : '';

        $dimensionOptions = $this->find('list', array('fields'=>array('id','dimension'), 'conditions' => array('olap_cube_id' => $cubeOptionsId, 'visible' => 1), 'recursive' => -1, 'order'=>array('order')));
        $criteriaOptions = $this->find('list', array('fields'=>array('table_name','dimension'), 'conditions' => array('olap_cube_id' => $cubeOptionsId, 'visible' => 1), 'recursive' => -1, 'order'=>array('order')));

        if($cubeCriteriaId){
			$olapCriteria = ClassRegistry::init($cubeCriteriaId);

    	  	$controller->set('fields', $olapCriteria->schema());
        }

        $controller->set('dimensionOptions', $dimensionOptions);
      	$controller->set('criteriaOptions', $criteriaOptions);
        $controller->set('selectedCubeOptions', $cubeOptionsId);
 		$controller->set('selectedCubeCriterias', $cubeCriteriaId);

       	if(!$controller->request->is('get')){
       		ini_set('memory_limit', '256M');
			set_time_limit(0);
       		$data = $controller->request->data;

       		$cubeId = $data['OlapCubeDimension']['cube_id'];
       		$rowId = $data['OlapCubeDimension']['row_id'];
   			$columnId = $data['OlapCubeDimension']['column_id'];

   			$rowDimensions = $this->find('first',
				array(
					'recursive'=>-1,
					'conditions'=>array('OlapCubeDimension.id' => $rowId)
				)
			);
			$columnDimensions = $this->find('first',
				array(
					'recursive'=>-1,
					'conditions'=>array('OlapCubeDimension.id' => $columnId)
				)
			);

			$str = $rowDimensions['OlapCubeDimension']['table_join'];

			if(!empty($str)){
				$str .= ',';
			}
			$str .= $columnDimensions['OlapCubeDimension']['table_join'];

			eval("\$options = array($str);");

			$joins = array();
			$i=0;
			foreach($options as $value){
				if(in_array($value['table'],$joins)){
					unset($options[$i]);
				}else{
					array_push($joins, $value['table']);
				}
				$i++;
			}
			$options = array_values($options);
			
			$modelTable = ClassRegistry::init($rowDimensions['OlapCubeDimension']['table_parent']);

			$group[] = $rowDimensions['OlapCubeDimension']['table_group'];
			$group[] = $columnDimensions['OlapCubeDimension']['table_group'];

 			$modelData = $modelTable->find('all',
				array(
					'recursive'=>-1,
					'fields'=>array("{$rowDimensions['OlapCubeDimension']['table_field']} as CubeRow", "{$columnDimensions['OlapCubeDimension']['table_field']} as CubeColumn", 'Count(*) as Number'),
					'joins'=> $options,
					'group' => $group,
					'order' => $group
				)
			);


			$layout = array();
			$rowName = array();
			$columnName = array();
			$layout[0][0] = 'Name';
			$i = 1;
			$rowAffected = 0;
			$colAffected = 0;
			$cubeRowTable = $rowDimensions['OlapCubeDimension']['table_name'];
			$cubeColumnTable = $columnDimensions['OlapCubeDimension']['table_name'];

			//pr($modelData);
			foreach($modelData as $result){
				$sameRow = false;
				if(!in_array($result[$cubeColumnTable]['CubeColumn'], $columnName)){
					$value = $result[$cubeColumnTable]['CubeColumn'];
					if(!isset($value)){
						$value = '';
					}
					$layout[0][count($columnName)+1] = $value;
					array_push($columnName, $result[$cubeColumnTable]['CubeColumn']);
					$colAffected = $i;
				}
				if(!in_array($result[$cubeRowTable]['CubeRow'], $rowName)){
					$sameRow = false;
					$value = $result[$cubeRowTable]['CubeRow'];
					if(!isset($value)){
						$value = '';
					}
					$layout[$i][0] = $value;
					array_push($rowName, $result[$cubeRowTable]['CubeRow']);
					$rowAffected = $i;
				}else{
					$sameRow = true;
				}
				

				if(!$sameRow){
					$j = 1;
					foreach($columnName as $col){
						if($col==$result[$cubeColumnTable]['CubeColumn']){
							$layout[$i][$j] = $result[0]['Number'];
						}else{
							$layout[$i][$j] = '';
						}
						$j++;
					}
				}else{
					$j = 1;
					foreach($columnName as $col){
						if($col==$result[$cubeColumnTable]['CubeColumn']){
							$layout[$rowAffected][$j] = $result[0]['Number'];
						}
						$j++;
					}
				}
				$i++;
			}

			$layout = array_values($layout);
			for($c=0;$c<=$colAffected;$c++){
				$r = 1;
				if(isset($layout[$c][0])){
					foreach($columnName as $col){
						if(!isset($layout[$c][$r])){
							$layout[$c][$r] = '';
						}
						$r++;
					}
				}
			}

			$controller->Session->write('olap_cube', $cubeId);
			$controller->Session->write('olap_row', $rowId);
			$controller->Session->write('olap_column', $columnId);
			$controller->Session->write('olap_report', $layout);

			$controller->redirect(array('action'=>'olapReportDisplay'));
       	}

       	$controller->set('modelName', $this->name);
		$controller->set('subheader', $this->headerDefault);

 	}



   	public function olapReportExport($controller, $params) { //$this->genReport('Site Details','CSV');
        $controller->autoRender = false;
        if($controller->Session->check('olap_report')){
       		$this->genXLSX($controller, $controller->Session->read('olap_report'));
       	}else{
       		$controller->redirect(array('action'=>'olapReport'));
       	}	
    }


    public function genXLSX($controller, $data){
        $webroot = WWW_ROOT;
        $view = new View($controller);
        $phpExcel = $view->loadHelper('PhpExcel');
        $templatePath = $webroot . 'reports/Olap_Cube_Reports/Olap_Cube/olap_report_template.xlsx';
        if (file_exists($templatePath)) {
             $phpExcel->loadWorksheet($templatePath);
             $phpExcel->setDefaultFont('Calibri', 12);
        } 

        $i = 0;
     	foreach($data[0] as $key=>$value){
            $phpExcel->changeCell($value,$this->getNameFromNumber($i).'1'); 
            $i++;
        }
        $i =2;
       	foreach($data as $key=>$value){ 
       		if($key==0){
       			continue;
       		}
       		$j=0;
       	  	foreach ($value as $key2=>$value2){
       	  		if(!empty($value2)){
	       	  		$phpExcel->changeCell($value2,$this->getNameFromNumber($j).$i); 
	       	  	}
           		$j++;
           	}
           	$i++;
       	}
 
        $phpExcel->output('olap_report_' . date('Ymdhis') . '.xlsx'); 
    }

    public function getNameFromNumber($num) {
	    $numeric = $num % 26;
	    $letter = chr(65 + $numeric);
	    $num2 = intval($num / 26);
	    if ($num2 > 0) {
	        return $this->getNameFromNumber($num2 - 1) . $letter;
	    } else {
	        return $letter;
	    }
	}


	public function olapReportDisplay($controller, $params) {
		if($controller->Session->check('olap_report')){
			$controller->set('data', $controller->Session->read('olap_report'));
		}else{
			$controller->redirect(array('action'=>'olapReport'));
		}

		$controller->set('modelName', $this->name);
		$controller->set('subheader', $this->headerDefault);
	} 

}
?>
	