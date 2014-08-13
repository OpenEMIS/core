<?php
/*
Copyright (c) 2012-2013 Luis E. S. Dias - www.smartbyte.com.br

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
App::uses('AppController', 'Controller');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

class ReportController extends AppController {
    
    public $uses = array('ReportTemplate');
    public $helpers = array('Number', 'Paginator');
    public $path = null;
	public $models = array(
		'InstitutionSite' => 'Institution Site', 
		'Students.Student' => 'Student', 
		//'Teachers.Teacher' => 'Teacher', 
		'Staff.Staff' => 'Staff', 
		'CensusStudent' => 'Census Student'
	);
	
	public $components = array(
        'Paginator'
    );
    
    public function __construct( $request = NULL, $response = NULL ) {
        $reportPath = Configure::read('ReportManager.reportPath');
        if ( !isset($reportPath) )
            $reportPath = 'tmp'.DS.'reports'.DS;
        $this->path = $reportPath;
        if(!is_dir(APP.$this->path)) {
            $folder = new Folder();
            $folder->create(APP.$this->path);
        }
        parent::__construct($request,$response);        
    }
	
	public function beforeFilter() {
        parent::beforeFilter();
		$this->bodyTitle = 'Reports';
        $this->Navigation->addCrumb('Reports', array('controller' => 'Reports', 'action' => 'index'));
		$this->Navigation->addCrumb('Custom Reports');
    }
    
    public function index() {
		$globalReport = $this->ReportTemplate->find('all', array('conditions' => array('security_user_id' => 0)));
		$myReport = $this->ReportTemplate->find('all', array('conditions' => array('security_user_id' => $this->Auth->user('id'))));
		$this->set('globalReport', $globalReport);
		$this->set('myReport', $myReport);
		
        if (empty($this->request->data)) {
            $modelIgnoreList = Configure::read('ReportManager.modelIgnoreList'); 
            
            //$models = App::objects('Model');
			$models = $this->models;
            //$models = array_combine($models,$models);
            
            if ( isset($modelIgnoreList) && is_array($modelIgnoreList)) {
                foreach ($modelIgnoreList as $model) {
                    if (isset($models[$model]));
                        unset($models[$model]);
                }                
            }
            $this->set('files',$this->listReports());
            $this->set('models',$models);
        } else {
            if (isset($this->request->data['Report']['new'])) {
                $reportButton = 'new';
                $modelClass = $this->request->data['Report']['model'];
                //$oneToManyOption = $this->request->data['Report']['one_to_many_option'];
                $this->redirect(array('action'=>'reportsWizard',$reportButton, $modelClass));
            }
                
            if (isset($this->request->data['load'])) {
                $reportButton = 'load';
                $reportId = $this->request->data['Report']['id'];
                $this->redirect(array('action'=>'reportsWizard', $reportButton, $reportId));                
            }
                
            $this->redirect(array('action'=>'index'));
        }
    }
	
	public function reportsNew() {
		$models = $this->models;
		//$models = array_combine($models,$models);
		$this->set('models', $models);
	}
	
	public function reportsView($id=null) {
		if(!is_null($id)) {
			$data = $this->ReportTemplate->findById($id);
			$this->Session->write('CustomReport.id', $id);
			$this->set('data', $data);
			$outputOptions = array('html' => 'HTML', 'xls' => 'Excel');
			$this->set('outputOptions', $outputOptions);
		} else {
			return $this->redirect(array('action' => 'index'));
		}
	}
	
	public function reportsDelete() {
		$id = $this->Session->read('CustomReport.id');
		$this->ReportTemplate->delete($id);
		$this->Utility->alert($this->Utility->getMessage('DELETE_SUCCESS'));
		return $this->redirect(array('action' => 'index'));
    }
	
    public function ajaxGetOneToManyOptions() {
        if ($this->request->is('ajax')) {
            Configure::write('debug',0);
            $this->autoRender = false;
            $this->layout = null;

            $modelClass = $this->request->data['model'];
            $this->loadModel($modelClass);
            $associatedModels = $this->{$modelClass}->getAssociated('hasMany');
            $associatedModels = array_combine($associatedModels, $associatedModels);

            $modelIgnoreList = Configure::read('ReportManager.modelIgnoreList');
            if ( isset($modelIgnoreList) && is_array($modelIgnoreList)) {
                foreach ($modelIgnoreList as $model) {
                    if (isset($associatedModels[$model]));
                        unset($associatedModels[$model]);
                }
            }            
            
            $this->set('associatedModels',$associatedModels);
            $this->render('list_one_to_many_options');
        }
    }

    // calculate the html table columns width
    public function getTableColumnWidth($fieldsLength=array(),$fieldsType=array()) {
        $minWidth = 4;
        $maxWidth = 50;
        $tableColumnWidth = array();
        foreach ($fieldsLength as $field => $length): 
            if ( $length != '') {
                if ( $length < $maxWidth ) 
                    $width = $length * 9;
                else
                    $width = $maxWidth * 9;
                if ( $length < $minWidth ) 
                    $width = $length * 40;                
                $tableColumnWidth[$field] = $width;
            } else {
                $fieldType = $fieldsType[$field];
                switch ($fieldType) {
                    case "date":
                        $width = 120;
                        break;
                    case "float":
                        $width = 150;
                        break;                
                    default:
                        $width = 120;
                        break;
                }
                $tableColumnWidth[$field] = $width;
            }
        endforeach; 
        return $tableColumnWidth;
    }
    
    // calculate the html table width
    public function getTableWidth($tableColumnWidth = array()) {
        $tableWidth = array_sum($tableColumnWidth);
        return $tableWidth;
    }

    public function export2Xls(&$reportData = array(),&$fieldsList=array(), &$fieldsType=array(), &$oneToManyOption=null, &$oneToManyFieldsList=null, &$oneToManyFieldsType = null, &$showNoRelated = false, &$recursive = null, &$fieldList = null, &$order = null, &$conditions = null, &$totalRows = null, &$rowsPerPage = null, &$modelClass = null) {
        App::import('Vendor', 'Excel');
        $xls = new Excel();      
        $xls->buildXlsCustom($reportData,$fieldsList, $fieldsType, $oneToManyOption, $oneToManyFieldsList, $oneToManyFieldsType, $showNoRelated, $recursive, $fieldList, $order, $conditions, $totalRows, $rowsPerPage, $modelClass);
    }
 
    public function saveReport($modelClass = null, $oneToManyOption = null) {
        $content='$reportFields=';
        $content.= var_export($this->request->data,1);
        $content.=';';
        
        if ($this->request->data['Report']['ReportName'] != '') {
            $reportName = str_replace('.', '_', $this->request->data['Report']['ReportName']);
            $reportName = str_replace(' ', '_', $this->request->data['Report']['ReportName']);
        } else {
            $reportName = $modelClass . '_Report_' . date('Ymd_His');
        }
		
		$reportDescription = isset($this->request->data['Report']['ReportDescription']) ? $this->request->data['Report']['ReportDescription'] : '';

		$userId = $this->Auth->user('super_admin') == 0 ? $this->Auth->user('id') : 0;
		$securityUserId = $userId;
		if($userId == 0){
			if(isset($this->request->data['Report']['SharedReport']) && ($this->request->data['Report']['SharedReport'] == 1)){
				$securityUserId = 0;
			}else{
				$securityUserId = $this->Auth->user('id');
				//echo $this->Auth->user('id');
			}
		}
        
		$template = array(
			'name' => $reportName,
			'description' => $reportDescription,
			'model' => $modelClass,
			'query' => $content
		);
		
		if(isset($this->request->data['Report']['id'])) {
			$template['id'] = $this->request->data['Report']['id'];
		} else {
			$this->ReportTemplate->create();
			$template['security_user_id'] = $securityUserId;
		}
		$this->ReportTemplate->save($template);
    }

    public function loadReport($id) {
        //require(APP.$this->path.$fileName);
		if($this->ReportTemplate->exists($id)) {
			$template = $this->ReportTemplate->findById($id);
			$query = $template['ReportTemplate']['query'];
			eval($query);
			$this->request->data = $reportFields;
			$this->request->data['Report']['MainModel'] = $template['ReportTemplate']['model'];
        	$this->set($this->request->data);
		} else {
			return $this->redirect(array('action' => 'index'));
		}
    }

    public function deleteReport($fileName) {
        if ($this->request->is('ajax')) {
            Configure::write('debug',0);
            $this->autoRender = false;
            $this->layout = null;
            
            $fileName = APP.$this->path.$fileName;
            $file = new File($fileName, false, 777);
            $file->delete();
            $this->set('files',$this->listReports());
            $this->render('list_reports');
        }
    }

    public function listReports() {
        $dir = new Folder(APP.$this->path);
        $files = $dir->find('.*\.crp');
        if (count($files)>0)
            $files = array_combine($files,$files);        
        return $files;
    }

    public function reportsWizard($param1 = null,$param2 = null) {
        if (is_null($param1) || is_null($param2)) {
            $this->Session->setFlash(__('Please select a model or a saved report'));
            $this->redirect(array('action'=>'index'));
        }
		
		$outputOptions = array('html' => 'HTML', 'xls' => 'Excel');
		$this->set('outputOptions', $outputOptions);
        
        $reportAction = $param1;
        $modelClass = null;
        $oneToManyOption = null;
        $fileName = null;
        
        if ( $reportAction == "new" ) {
            $modelClass = $param2;
            //$oneToManyOption = $param3;
			
			if(empty($this->request->data) && isset($this->params['pass'][2]) && $this->Session->check('CumtomReportData')){
				$this->request->data = $this->Session->read('CumtomReportData');
			}
        }
		
        if ( $reportAction == "load" ) {
			$modelClass = $this->ReportTemplate->field('model', array('id' => $param2));
			
			if(empty($this->request->data) && $this->Session->check('CumtomReportData')){
				$output = 'html';
				$this->request->data = $this->Session->read('CumtomReportData');
			}else{
				$output = $this->request->data['Report']['Output'];
				$this->loadReport($param2);
			}
			$this->request->data['Report']['Output'] = $output;
			$this->request->data['Report']['SaveReport'] = false;
        }
        
        if (empty($this->request->data)) {
            $displayForeignKeys = Configure::read('ReportManager.displayForeignKeys');
            $modelFieldIgnoreList = Configure::read('ReportManager.modelFieldIgnoreList');
			
            $this->loadModel($modelClass);
			if(strpos($modelClass, '.') !== false) {
				$split = explode('.', $modelClass);
				$modelClass = $split[1];
			}
            $modelSchema = $this->{$modelClass}->schema();
			
			$excludeFields = $this->{$modelClass}->getExcludedFields();
            if (!empty($excludeFields)) {
				foreach ($excludeFields as $field) {
					if(isset($modelSchema[$field])) {
						unset($modelSchema[$field]);
					}
                }
            }
            
            if (isset($displayForeignKeys) && !$displayForeignKeys) {               
                foreach($modelSchema as $field => $value) {
                    if ( substr($field,-3)=='_id' )
                        unset($modelSchema[$field]);
                }
            }
            
            $associatedModels = $this->{$modelClass}->getAssociated();
            $associatedModelsSchema = array();
            foreach ($associatedModels as $key => $value) {
                $className = $this->{$modelClass}->{$value}[$key]['className'];
                $this->loadModel($className);
                $associatedModelsSchema[$key] = $this->{$className}->schema();
				
				$excludeFields = $this->{$modelClass}->getExcludedFields($className);
				if (!empty($excludeFields)) {
					foreach ($excludeFields as $field) {
						if(isset($associatedModelsSchema[$key][$field])) {
							unset($associatedModelsSchema[$key][$field]);
						}
					}                
				}
                
                if (isset($displayForeignKeys) && !$displayForeignKeys) {
                    foreach($associatedModelsSchema as $model => $fields) {
                        foreach($fields as $field => $values) {
                            if ( substr($field,-3)=='_id' ) {
								unset($associatedModelsSchema[$model][$field]);
							}
                        }
                    }
                }
                foreach($associatedModelsSchema as $model => $fields) {
                    foreach($fields as $field => $values) {
                        if ( isset($modelFieldIgnoreList[$model][$field]) ) {
							unset($associatedModelsSchema[$model][$field]);
						}
                    }
                }                
            }

            $this->set('modelClass',$modelClass);
            $this->set('modelSchema',$modelSchema);
            $this->set('associatedModels',$associatedModels);
            $this->set('associatedModelsSchema',$associatedModelsSchema);
            $this->set('oneToManyOption',$oneToManyOption);
        } else {
			//pr($this->request->data);die;
			//pr($this->params['pass']);
			
			$this->Session->write('CumtomReportData', $this->request->data);
			
            $this->loadModel($modelClass);
			if(strpos($modelClass, '.') !== false) {
				$split = explode('.', $modelClass);
				$modelClass = $split[1];
			}
            $associatedModels = $this->{$modelClass}->getAssociated();
            //$oneToManyOption = $this->request->data['Report']['OneToManyOption'];
            
            $fieldList = array();
            $fieldsPosition = array();
            $fieldsType = array();
            $fieldsLength = array();
            
            $conditions = array();
            $conditionsList = array();
            
            $oneToManyFieldsList  = array();
            $oneToManyFieldsPosition  = array();
            $oneToManyFieldsType  = array();
            $oneToManyFieldsLength = array();
			
            foreach ($this->request->data  as $model => $fields) {
                if ( is_array($fields) ) {
                    foreach ($fields  as $field => $parameters) {
                        if ( is_array($parameters) ) {                          
                            if ( (isset($associatedModels[$model]) && 
                                    $associatedModels[$model]!='hasMany') || 
                                    ($modelClass == $model) 
                                ) {
                                if ( $parameters['Add'] ) {
                                    $fieldsPosition[$model.'.'.$field] = ( $parameters['Position']!='' ? $parameters['Position'] : 0 );
                                    $fieldsType[$model.'.'.$field] = $parameters['Type'];
                                    $fieldsLength[$model.'.'.$field] = $parameters['Length'];
                                }
                                $criteria = '';                                    
                                if ($parameters['Example'] != '' && $parameters['Filter']!='null' ) {
                                    if ( $parameters['Not'] ) {
                                        switch ($parameters['Filter']) {
                                            case '=':
                                                $criteria .= ' !'.$parameters['Filter'];
                                                break;
                                            case 'LIKE':
                                                $criteria .= ' NOT '.$parameters['Filter'];
                                                break;
                                            case '>':
                                                $criteria .= ' <=';
                                                break;
                                            case '<':
                                                $criteria .= ' >=';
                                                break;
                                            case '>=':
                                                $criteria .= ' <';
                                                break;
                                            case '<=':
                                                $criteria .= ' >';
                                                break;
                                            case 'null':
                                                $criteria = ' !=';
                                                break;
                                        }
                                    } else {
                                        if ($parameters['Filter']!='=') 
                                            $criteria .= ' '.$parameters['Filter'];
                                    }

                                    if ($parameters['Filter']=='LIKE')
                                        $example = '%'.$parameters['Example'] . '%';
                                    else
                                        $example = $parameters['Example'];

                                    $conditionsList[$model.'.'.$field.$criteria] = $example;
                                }
                                if ( $parameters['Filter']=='null' ) {
                                    $conditionsList[$model.'.'.$field.$criteria] = null;                                        
                                }
                            }
                            // One to many reports
                            if ( $oneToManyOption != '') {
                                if ( isset($parameters['Add']) && $model == $oneToManyOption ) {
                                    $oneToManyFieldsPosition[$model.'.'.$field] = ( $parameters['Position']!='' ? $parameters['Position'] : 0 );
                                    $oneToManyFieldsType[$model.'.'.$field] = $parameters['Type'];
                                    $oneToManyFieldsLength[$model.'.'.$field] = $parameters['Length'];
                                }                                    
                            }

                        } // is array parameters
                    } // foreach field => parameters
                    if (count($conditionsList)>0) {
                        $conditions[$this->request->data['Report']['Logical']] = $conditionsList;
                    }
                } // is array fields
            } // foreach model => fields
            asort($fieldsPosition);
            $fieldList = array_keys($fieldsPosition);
            $order = array();
            if ( isset($this->request->data['Report']['OrderBy1']) )
                $order[] = $this->request->data['Report']['OrderBy1'] . ' ' . $this->request->data['Report']['OrderDirection'];
            if ( isset($this->request->data['Report']['OrderBy2']) )
                $order[] = $this->request->data['Report']['OrderBy2'] . ' ' . $this->request->data['Report']['OrderDirection'];
            
            $tableColumnWidth = $this->getTableColumnWidth($fieldsLength,$fieldsType);
            $tableWidth = $this->getTableWidth($tableColumnWidth);
            
            if ($oneToManyOption == '') {
                $recursive = 0;
                $showNoRelated = false;
            } else {
                $oneToManyTableColumnWidth = $this->getTableColumnWidth($oneToManyFieldsLength,$oneToManyFieldsType);
                $oneToManyTableWidth = $this->getTableWidth($oneToManyTableColumnWidth);                
                asort($oneToManyFieldsPosition);
                $oneToManyFieldsList = array_keys($oneToManyFieldsPosition);
                $showNoRelated = $this->request->data['Report']['ShowNoRelated'];
                $recursive = 1;
            }
			
			if (isset($this->request->data['Report']['ReportName']) && $this->request->data['Report']['ReportName'] != '') {
				$pageTitleReport = $this->request->data['Report']['ReportName'];
			} else {
				$pageTitleReport = $modelClass . ' Report ';
			}
            
			$this->set(compact('tableColumnWidth', 'tableWidth', 'fieldList', 'fieldsType', 'fieldsLength', 'pageTitleReport', 'pageTitleReport'));
            $this->layout = 'report_html';
			
			$rowsPerPage = 100;
			
			$totalRows = $this->{$modelClass}->find('count', array(
				'recursive' => $recursive,
				'fields' => $fieldList,
				'order' => $order,
				'conditions' => $conditions
			));
			//echo $this->request->data['Report']['Output'];
            if ( $this->request->data['Report']['Output'] == 'html') {
				//pr($this->request->data);die;
				if($this->request->data['Report']['MainModel'] != $modelClass){
					return $this->redirect(array('action' => 'index'));
				}
				
				$totalPages = ceil($totalRows / $rowsPerPage);
				
				if(isset($this->params['pass'][2])){
					$currentPage = intval($this->params['pass'][2]);
					if($currentPage < 1){
						$currentPage = 1;
					}
				}else{
					$currentPage = 1;
				}
				
				if ($currentPage > $totalPages) {
					$currentPage = 1;
				}
				
				$reportData = $this->{$modelClass}->find('all',array(
					'recursive'=>$recursive,
					'fields'=>$fieldList,
					'order'=>$order,
					'conditions'=>$conditions,
					'limit' => $rowsPerPage,
					'offset' => (($currentPage-1) * $rowsPerPage)
				));
				
				$this->set(compact('reportData'));
				// params for pagination
				$this->set(compact('param1', 'param2', 'totalRows', 'rowsPerPage', 'totalPages', 'currentPage'));
				
                if ($oneToManyOption == '')
                    $this->render('report_display');
                else {
					$this->set(compact('oneToManyOption', 'oneToManyFieldsList', 'oneToManyFieldsType', 'oneToManyTableColumnWidth', 'oneToManyTableWidth', 'showNoRelated'));
                    $this->render('report_display_one_to_many');
                }
			} else if($this->request->data['Report']['Output'] != 'html' && isset($this->params['pass'][2])){
				return $this->redirect(array('action' => 'index'));
			}else { // Excel file
				$reportData = array();
				
                $this->layout = null;
                $this->export2Xls(
                        $reportData, 
                        $fieldList, 
                        $fieldsType, 
                        $oneToManyOption, 
                        $oneToManyFieldsList, 
                        $oneToManyFieldsType, 
                        $showNoRelated, $recursive, $fieldList, $order, $conditions, $totalRows, $rowsPerPage, $modelClass);
            }
            if ($this->request->data['Report']['SaveReport'])
                $this->saveReport($modelClass,$oneToManyOption);
        }
    }
}