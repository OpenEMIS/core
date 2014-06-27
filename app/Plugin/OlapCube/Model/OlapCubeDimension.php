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

	public $headerDefault = 'OLAP Reports';

	public function olapReport($controller, $params){
		$olapCube = ClassRegistry::init('OlapCube');
	  	$controller->Navigation->addCrumb('OLAP Reports');

		$cubeOptions = $olapCube->find('list', array('fields'=>array('id','cube'),'conditions'=>array('visible'=>1), 'order'=>array('order')));

		$controller->set('cubeOptions', $cubeOptions);

        $cubeOptionsId = isset($params['pass'][0]) ? $params['pass'][0] : key($cubeOptions);
        $cubeCriteriaId = isset($params['pass'][3]) ? $params['pass'][3] : '';
 		$dimensionOptions = $this->find('list', array('fields'=>array('id','dimension'), 'conditions' => array('olap_cube_id' => $cubeOptionsId, 'visible' => 1), 'recursive' => -1, 'order'=>array('order')));
        $criteriaOptions = $this->find('list', array('fields'=>array('id','dimension'), 'conditions' => array('olap_cube_id' => $cubeOptionsId, 'visible' => 1), 'recursive' => -1, 'order'=>array('order')));

       	
        $cubeRowId = isset($params['pass'][1]) ? $params['pass'][1] : key($dimensionOptions);
        $cubeColumnId = isset($params['pass'][2]) ? $params['pass'][2] : key($dimensionOptions);
        if($cubeCriteriaId){
        	$criteriaDimensions = $this->find('first',
				array(
					'recursive'=>-1,
					'conditions'=>array('OlapCubeDimension.id' => $cubeCriteriaId)
				)
			);

        	if($cubeCriteriaId=='8' || $cubeCriteriaId=='17' || $cubeCriteriaId=='25'){
        		//Gender
    			$controller->set('filterFields', array($criteriaDimensions['OlapCubeDimension']['table_name'].'.male'=>'Male', $criteriaDimensions['OlapCubeDimension']['table_name'].'.female'=>'Female'));
        	}else{
				if(!empty($criteriaDimensions)){
					$olapCriteria = ClassRegistry::init($criteriaDimensions['OlapCubeDimension']['table_name']);
					$filterFields = $olapCriteria->find('list', array('fields'=>array($criteriaDimensions['OlapCubeDimension']['table_field'],$criteriaDimensions['OlapCubeDimension']['table_field']), 'order'=>array('order'), 'group'=>array($criteriaDimensions['OlapCubeDimension']['table_group'])));
		    	  	$controller->set('filterFields', $filterFields);
	    	  	}
    	  	}
        }

        $controller->set('dimensionOptions', $dimensionOptions);
      	$controller->set('criteriaOptions', $criteriaOptions);
        $controller->set('selectedCubeOptions', $cubeOptionsId);

 		$controller->set('selectedCubeRows', $cubeRowId);
 		$controller->set('selectedCubeColumns', $cubeColumnId);
 		$controller->set('selectedCubeCriterias', $cubeCriteriaId);

       	if(!$controller->request->is('get')){
       		$controller->Session->delete('Olap');
       		ini_set('memory_limit', '999M');
			set_time_limit(0);
       		$data = $controller->request->data;

       		$cubeId = $data['OlapCubeDimension']['cube_id'];
       		$rowId = $data['OlapCubeDimension']['row_id'];
   			$columnId = $data['OlapCubeDimension']['column_id'];
   			$criteriaId = $data['OlapCubeDimension']['criteria_id'];
   			$criteria = isset($data['OlapCubeDimension']['field']) ? $data['OlapCubeDimension']['field'] : '';

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
			if(!empty($criteriaId)){
				$criteriaDimensions = $this->find('first',
					array(
						'recursive'=>-1,
						'conditions'=>array('OlapCubeDimension.id' => $criteriaId)
					)
				);
			}

			$str = $rowDimensions['OlapCubeDimension']['table_join'];

			if(!empty($columnDimensions['OlapCubeDimension']['table_join'])){
				$str .= ',' . $columnDimensions['OlapCubeDimension']['table_join'];

			}
			if(isset($criteriaDimensions) && !empty($criteriaDimensions)){
				$str .= ',' . $criteriaDimensions['OlapCubeDimension']['table_join'];
			}
			if(substr($str, 0, 1)==','){
				$str =  substr($str, 1);
			}
			 
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
			$modelTableName = $rowDimensions['OlapCubeDimension']['table_parent'];
			$modelTable = ClassRegistry::init($modelTableName);


			$group = array();
			if(!empty($columnDimensions['OlapCubeDimension']['table_group'])){
				$group[] = $columnDimensions['OlapCubeDimension']['table_group'];
			}
			if(!empty($rowDimensions['OlapCubeDimension']['table_group'])){
				$group[] = $rowDimensions['OlapCubeDimension']['table_group'];
			}
			$order = $rowDimensions['OlapCubeDimension']['table_field'];

			$conditions = array();
			if(isset($criteria) && !empty($criteria)){
				foreach($criteria as $c){
					$conditions['OR'][] = array($criteriaDimensions['OlapCubeDimension']['table_field'] =>$c);
				}
			}

			//$conditions['Institution.id'] = 16039;
 
			$rowField = $rowDimensions['OlapCubeDimension']['table_field'];
			$columnField = $columnDimensions['OlapCubeDimension']['table_field'];
			$computeField = $columnDimensions['OlapCubeDimension']['table_compute'];
			if($rowDimensions['OlapCubeDimension']['table_aggregate']=='1'){
				$computeField = $rowDimensions['OlapCubeDimension']['table_compute'];
			}
			$computeRowField = $rowDimensions['OlapCubeDimension']['table_compute'];
			$computeColumnField = $columnDimensions['OlapCubeDimension']['table_compute'];


			$cubeRowTable = $rowDimensions['OlapCubeDimension']['table_name'];
			$cubeColumnTable = $columnDimensions['OlapCubeDimension']['table_name'];



			$rowFieldCount = 1;
			$columnFieldCount = 1;
			$computeFieldCount = 1;
			$switchCompute = false;
			$fields = array();
			$oFields = array();
			$oCFields = array();

			$a = 0;
			if(strpos($rowField,'CONCAT')!==false){
				//$modelTable->virtualFields['vf'] = $rowField;
			  	//$fields[] = "{$modelTableName}.vf";
		  		$fields[] = "{$rowField} as CubeRow1";
		  		$oFields[] = "CubeRow1";
			}else if(strpos($rowField, ',')!==false && strpos($rowField, 'CONCAT')==false){
				$arrRowField = split(',', $rowField);
				$rowFieldCount = count($arrRowField);
				$a = 0;
				foreach($arrRowField as $r){
					$fields[] = "{$r} as CubeRow" . ($a+1);
					$oFields[] = "CubeRow" . ($a+1);
					$a++;
				}
			}else{
				$fields[] = "{$rowField} as CubeRow1";
				$oFields[] = "CubeRow1";
			}

			if(strpos($columnField,'CONCAT')!==false){
			 	//$modelTable->virtualFields['vf'] = $columnField;
			  	//$fields[] = "{$modelTableName}.vf";
			  	$fields[] = "{$columnField} as CubeColumn1";
			  	$oFields[] = "CubeColumn1";
			}else if(strpos($columnField, ',')!==false && strpos($columnField, 'CONCAT')==false){
				$arrColumnField = split(',', $columnField);
				$columnFieldCount = count($arrColumnField);
				$a = 0;
				foreach($arrColumnField as $r){
					$fields[] = "{$r} as CubeColumn" . ($a+1);
					$oFields[] = "CubeColumn" . ($a+1);
					$a++;
				}
			}else{
				$fields[] = "{$columnField} as CubeColumn1";
				$oFields[] = "CubeColumn1";
			}
			if(strpos($computeColumnField, ',')!==false || strpos($computeRowField, ',')!==false){
				$arrComputeField = array();
				if(strpos($computeColumnField, ',')!==false){
					$arrComputeField = split(',', $computeColumnField);
				}else{
					$arrComputeField = split(',', $computeRowField);
					$switchCompute = true;
				}
				
				$computeFieldCount = count($arrComputeField);
				$a = 0;
				foreach($arrComputeField as $r){
					$fields[] = "{$r} as Number" . ($a+1);
					$oCFields[] = "SUM(Number".($a+1).") as Number".($a+1);
					$a++;
				}
			}else{
				$fields[] = "{$computeField} as Number1";
				$oCFields[] = "COUNT(Number1) as Number1";
			}

			$dbo = $modelTable->getDataSource();
			$subQuery = $dbo->buildStatement(
				array(
					'fields' => $fields,
					'joins'=> $options,
					'table' => $dbo->fullTableName($modelTable),
					'alias' => $modelTableName,
					'group' =>  $group,
					'conditions' => $conditions,
					'order' => $order,
					'limit' => null
				)
				,$modelTable
			);

			//pr($subQuery);
			$outerQuery = $dbo->buildStatement(
				array(
					'fields' => array_merge($oFields,$oCFields),
					'table' => '('.$subQuery.')',
					'alias' => $modelTableName,
					'group' => $oFields
				)
				,$modelTable
			);
  			//pr($outerQuery);
			
			$modelData = $modelTable->query($outerQuery);
			pr($outerQuery);
			exit;
			/*
 			$modelData = $modelTable->find('all',
				array(
					'recursive'=>-1,
					'fields'=>$fields,
					'joins'=> $options,
					'conditions'=>$conditions,
					'group' => $group,
					'order' => $order
					)
			);*/

 			/*$log = $modelTable->getDataSource()->getLog(false, false);
			pr($log);

			exit;*/

			//pr($modelData);
		
			$layout = array();
			$rowName = array();
			$columnName = array();
			$layout = array();

		
			if(!empty($modelData)){
				foreach($modelData as $result){
					$cResult = (isset($result[$modelTableName]['CubeColumn1'])? $result[$modelTableName]['CubeColumn1'] :(isset($result[0]['CubeColumn1']) && $columnFieldCount==1 ? $result[0]['CubeColumn1'] : null));
					$rResult = (isset($result[$modelTableName]['CubeRow1'])? $result[$modelTableName]['CubeRow1'] :(isset($result[0]['CubeRow1']) && $rowFieldCount==1 ? $result[0]['CubeRow1'] : null));

					/*
					$temp = isset($result[$cubeRowTable])? $result[$cubeRowTable] : array();
					if(!array_key_exists('CubeRow1', $temp)){
						$cubeRowTable = 0;
						$temp = isset($result[$cubeRowTable])? $result[$cubeRowTable] : array();
						if(!array_key_exists('CubeRow1', $temp)){
							$cubeRowTable = $columnDimensions['OlapCubeDimension']['table_name'];
						}
					}
					
					for($ii=1;$ii<=$rowFieldCount;$ii++){
						for($jj=1;$jj<=$columnFieldCount;$jj++){
							if($switchCompute){
								$layout[$result[$cubeRowTable]['CubeRow'.$ii]][$result[$cubeColumnTable]['CubeColumn'.$jj]] = $result[0]['Number'.$ii];
							}else{
								$layout[$result[$cubeRowTable]['CubeRow'.$ii]][$result[$cubeColumnTable]['CubeColumn'.$jj]] = $result[0]['Number'.$jj];
							}
						}
					}

					for($ii=1;$ii<=$columnFieldCount;$ii++){
						if(!in_array($result[$cubeColumnTable]['CubeColumn'.$ii], $columnName)){
							array_push($columnName, $result[$cubeColumnTable]['CubeColumn'.$ii]);
						}
					}*/

					
					for($ii=1;$ii<=$rowFieldCount;$ii++){
						for($jj=1;$jj<=$columnFieldCount;$jj++){
							if($rowFieldCount>1 || $columnFieldCount>1){
								if($switchCompute){
									$layout[$result[$modelTableName]['CubeRow'.$ii]][$result[$modelTableName]['CubeColumn'.$jj]] = $result[0]['Number'.$ii];
								}else{
									$layout[$result[$modelTableName]['CubeRow'.$ii]][$result[$modelTableName]['CubeColumn'.$jj]] = $result[0]['Number'.$jj];
								}
								if(!in_array($result[$modelTableName]['CubeColumn'.$jj], $columnName)){
									array_push($columnName, $result[$modelTableName]['CubeColumn'.$jj]);
								}
							}else{
								if($switchCompute){
									$layout[$rResult][$cResult] = $result[0]['Number'.$ii];
								}else{
									$layout[$rResult][$cResult] = $result[0]['Number'.$jj];
								}
								if(!in_array($cResult, $columnName)){
									array_push($columnName, $cResult);
								}
							}
						}
					}
					
					/*
					for($ii=1;$ii<=$rowFieldCount;$ii++){
						for($jj=1;$jj<=$columnFieldCount;$jj++){
							if($switchCompute){
								$layout[$result[$cubeRowTable]['CubeRow'.$ii]][$result[$cubeColumnTable]['CubeColumn'.$jj]] = $result[0]['Number'.$ii];
							}else{
								$layout[$result[$cubeRowTable]['CubeRow'.$ii]][$result[$cubeColumnTable]['CubeColumn'.$jj]] = $result[0]['Number'.$jj];
							}
						}
					}*/

				}
			}

			$controller->Session->write('Olap.olap_cube', $cubeId);
			$controller->Session->write('Olap.olap_row', $rowId);
			$controller->Session->write('Olap.olap_column', $columnId);
			$controller->Session->write('Olap.olap_report', $layout);
			$controller->Session->write('Olap.olap_report_column', $columnName);
			$controller->redirect(array('action'=>'olapReportDisplay'));
       	}

       	$controller->set('modelName', $this->name);
		$controller->set('subheader', $this->headerDefault);
 	}

 	private function array_key_exists_wildcard ( $arr, $nee )
	{
	    $nee = str_replace( '\*', '.*?', preg_quote( $nee, '/' ) );
	    return preg_grep( '/^' . $nee . '$/i', array_keys( $arr ) );
	}  


   	public function olapReportExport($controller, $params) { //$this->genReport('Site Details','CSV');
        $controller->autoRender = false;
        if($controller->Session->check('olap_report')){
       		$this->genXLSX($controller, $controller->Session->read('Olap.olap_report'));
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
		ini_set('memory_limit', '999M');
		set_time_limit(0);
		if($controller->Session->check('Olap.olap_report')){
			$controller->set('data', $controller->Session->read('Olap.olap_report'));
			$controller->set('column', $controller->Session->read('Olap.olap_report_column'));
		}else{
			$controller->redirect(array('action'=>'olapReport'));
		}

	 	$controller->Navigation->addCrumb('OLAP Reports', array('plugin' => 'OlapCube', 'action' => 'olapReport'));
		$controller->Navigation->addCrumb('Result');

		$controller->set('modelName', $this->name);
		$controller->set('subheader', $this->headerDefault);
	} 

}
?>
	