<?php
App::uses('AppController', 'Controller'); 

class InstitutionSitesController extends AppController {
	public $institutionSiteId;
    public $institutionSiteObj;
	
	public $uses = array(
		'Area',
		'AreaLevel',
		'Bank',
		'BankBranch',
		'EducationProgramme',
		'EducationFieldOfStudy',
		'EducationCertification',
		'EducationCycle',
		'EducationLevel',
		'EducationSystem',
		'Institution',
		'InstitutionSiteCustomField',
		'InstitutionSiteCustomFieldOption',
		'InstitutionSiteCustomValue',
		'InstitutionSite',
		'InstitutionSiteHistory',
		'InstitutionSiteOwnership',
		'InstitutionSiteLocality',
		'InstitutionSiteSector',
		'InstitutionSiteStatus',
		'InstitutionSiteProgramme',
		'InstitutionSiteAttachment',
		'InstitutionSiteBankAccount',
		'InstitutionSiteType',
		'CensusStudent',
		'SecurityUserRole',
		'SecurityRoleInstitutionSite'
	);
	
	public $components = array(
		'FileAttachment' => array(
			'model' => 'InstitutionSiteAttachment',
			'foreignKey' => 'institution_site_id'
		)
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		if($this->Session->check('InstitutionId')) {
			$institutionId = $this->Session->read('InstitutionId');
			$institutionName = $this->Institution->field('name', array('Institution.id' => $institutionId));
			$this->Navigation->addCrumb('Institutions', array('controller' => 'Institutions', 'action' => 'index'));
			$this->Navigation->addCrumb($institutionName, array('controller' => 'Institutions', 'action' => 'view'));
			
			if($this->action === 'index' || $this->action === 'add') {
				$this->bodyTitle = $institutionName;
			} else {
				if($this->Session->check('InstitutionSiteId')) {
					$this->institutionSiteId = $this->Session->read('InstitutionSiteId');
					$this->institutionSiteObj = $this->Session->read('InstitutionSiteObj');
					$institutionSiteName = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
					$this->bodyTitle = $institutionName . ' - ' . $institutionSiteName;
					$this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view'));
				} else {
					$this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
				}
			}
		} else {
			$this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
		}
	}
	
	public function index() {
		if(isset($this->params['pass'][0])) {
			$id = $this->params['pass'][0];
			$obj = $this->InstitutionSite->find('first',array('conditions'=>array('InstitutionSite.id' => $id)));
			
			if($obj) {
				$this->Session->write('InstitutionSiteId', $id);
        		$this->Session->write('InstitutionSiteObj', $obj);
				$this->redirect(array('action' => 'view'));
			} else {
				$this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
			}
		} else {
			$this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
		}
    }
	
	public function view() {
		
		
		$this->Navigation->addCrumb('Details');
		
		$levels = $this->AreaLevel->find('list',array('recursive'=>0));
                
		$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
		$areaLevel = $this->fetchtoParent($data['InstitutionSite']['area_id']);
		$areaLevel = array_reverse($areaLevel);
		
		$this->set('data', $data);
		$this->set('arealevel',$areaLevel);
		$this->set('levels',$levels);
	}
	
	public function details() {
		$this->autoRender = false;
		
		if($this->request->is('get')) {  
			/*
			 * DEALING with Area - Starts
			 */

			if($this->institutionSiteObj['InstitutionSite']['area_id'] == 0) $this->institutionSiteObj['InstitutionSite']['area_id'] = 1;

			$lowest =  $this->institutionSiteObj['InstitutionSite']['area_id'];
			$areas = $this->fetchtoParent($lowest);
			$areas = array_reverse($areas);
			//pr($areas);
			
			foreach($areas as $index => &$arrVals){
				$siblings = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $arrVals['parent_id'])));
				$this->Utility->unshiftArray($siblings,array('0'=>'--'.__('Select').'--'));
				
				$colInfo['area_level_'.$index]['options'] = $siblings;
			}
			
			$maxAreaIndex = max(array_keys($areas));//starts with 0
			$totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
			for($i = $maxAreaIndex; $i < $totalAreaLevel;$i++ ){
				$colInfo['area_level_'.($i+1)]['options'] = array('0'=>'--'.__('Select').'--');
			}
			
			/*
			 * DEALING with Area - Ends
			 */
			
			return json_encode($colInfo);
		} else {
			$last_area_id = 0;
			//this key sort is impt so that the lowest area level will be saved correctly
			ksort($this->request->data['InstitutionSite']);
			foreach($this->request->data['InstitutionSite'] as $key => $arrValSave){
				if(stristr($key,'area_level_') == true && ($arrValSave != '' || $arrValSave != 0)){
					$last_area_id = $arrValSave;
				}
			}
			$this->request->data['InstitutionSite']['area_id'] = $last_area_id;
			
			$this->insertHistory($this->Session->read('InstitutionSiteId'));
			$this->InstitutionSite->id = $this->Session->read('InstitutionSiteId');
                        
			$this->log($this->InstitutionSite->save($this->request->data), 'debug');           
		}
	}
	
	public function edit() {
		$id = $this->Session->read('InstitutionSiteId');
		
        $this->InstitutionSite->id = $id;
		$this->Navigation->addCrumb('Edit Details');
		
		if($this->request->is('post')) {
			/**
			 * need to sort the Area to get the the lowest level
			 */
			$last_area_id = 0;
			//this key sort is impt so that the lowest area level will be saved correctly
			ksort($this->request->data['InstitutionSite']);
			foreach($this->request->data['InstitutionSite'] as $key => $arrValSave){
				if(stristr($key,'area_level_') == true && ($arrValSave != '' && $arrValSave != 0)){
					$last_area_id = $arrValSave;
				}
			}
			pr($this->request->data);die;
			die();
			if($last_area_id == 0){
				$last_area_id = $this->institutionSiteObj['InstitutionSite']['area_id'];
			}
			$this->request->data['InstitutionSite']['area_id'] = $last_area_id;
			
			$this->InstitutionSite->set($this->request->data);
			if($this->InstitutionSite->validates()) {
                $this->request->data['InstitutionSite']['latitude'] = trim($this->request->data['InstitutionSite']['latitude']);
                $this->request->data['InstitutionSite']['longitude'] = trim($this->request->data['InstitutionSite']['longitude']);
              
				$rec = $this->InstitutionSite->save($this->request->data);
				
				$this->redirect(array('action' => 'view'));
			}
			
			/**
			 * preserve the dropdown values on error
			 */
			if($last_area_id != 0){
				$areaLevel = $this->fetchtoParent($last_area_id);
				$areaLevel = array_reverse($areaLevel);
				$areadropdowns = array();
				foreach($areaLevel as $index => &$arrVals){
					$siblings = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $arrVals['parent_id'])));
					$this->Utility->unshiftArray($siblings,array('0'=>'--'.__('Select').'--'));
					$areadropdowns['area_level_'.$index]['options'] = $siblings;
				}
				$maxAreaIndex = max(array_keys($areaLevel));//starts with 0
				$totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
				for($i = $maxAreaIndex; $i <= $totalAreaLevel; $i++ ){
					$areadropdowns['area_level_'.($i+1)]['options'] = array('0'=>'--'.__('Select').'--');
				}
			}
			
			
			
		}else{
			$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));
			$this->set('data', $data);
			$areaLevel = $this->fetchtoParent($data['InstitutionSite']['area_id']);
			$areaLevel = array_reverse($areaLevel);
			$areadropdowns = $this->getAllSiteAreaToParent($data['InstitutionSite']['area_id']);
		}
		
		
		$topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1')));
		$disabledAreas = $this->Area->find('list',array('conditions'=>array('Area.visible' => '0')));
		$this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
		$levels = $this->AreaLevel->find('list');
		$visible = true;
        $type = $this->InstitutionSiteType->findList($visible);
		$ownership = $this->InstitutionSiteOwnership->findList($visible);
		$locality = $this->InstitutionSiteLocality->findList($visible);
		$status = $this->InstitutionSiteStatus->findList($visible);
		$this->Utility->unshiftArray($type, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($ownership, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($locality, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($status, array('0'=>'--'.__('Select').'--'));
		
		$this->set('type_options',$type);
        $this->set('ownership_options',$ownership);
        $this->set('locality_options',$locality);
        $this->set('status_options',$status);
		$this->set('arealevel',$areaLevel);
		$this->set('levels',$levels);
		$this->set('areadropdowns',$areadropdowns);
		$this->set('disabledAreas',$disabledAreas);
		
		
        $this->set('highestLevel',$topArea);
    }
	
    public function add() {
		
		$this->Navigation->addCrumb('Add new Institution Site');
		$institutionId = $this->Session->read('InstitutionId');
		$areadropdowns = array('0'=>'--'.__('Select').'--');
		$areaLevel = array();
		if($this->request->is('post')) {
			
			$last_area_id = 0;
            //this key sort is impt so that the lowest area level will be saved correctly
            ksort($this->request->data['InstitutionSite']);
            foreach($this->request->data['InstitutionSite'] as $key => $arrValSave){
                if(stristr($key,'area_level_') == true && ($arrValSave != '' && $arrValSave != 0)){
                    $last_area_id = $arrValSave;
					
                }
				 if(stristr($key,'area_level_') == true){
					 unset($this->request->data['InstitutionSite'][$key]);
				 }
            }
			//pr($this->request->data);die;
            $this->request->data['InstitutionSite']['area_id'] = $last_area_id;
			$this->InstitutionSite->set($this->request->data);
			
			if($this->InstitutionSite->validates()) {
				$newInstitutionSiteRec =  $this->InstitutionSite->save($this->request->data);
				
				$institutionSiteId = $newInstitutionSiteRec['InstitutionSite']['id'];
				
				//** Reinitialize the Site Session by adding the newly added site **/
				$tmp = $this->Session->read('AccessControl.sites');
				array_push($tmp,$institutionSiteId);
				$this->Session->write('AccessControl.sites',$tmp);
				
				//** Reinitialize the Institution + Site Session by adding the newly added site **/
				$sites = $this->Session->read('AccessControl.institutions');
				$sites[$newInstitutionSiteRec['InstitutionSite']['institution_id']][]=$institutionSiteId;
				$this->Session->write('AccessControl.institutions', $sites);
				
				$this->Session->write('InstitutionSiteId', $institutionSiteId);
                                
                //** Push Site to Highest Role Area**/
                //get highest Role of userid
                $highestRole = $this->SecurityUserRole->getHighestUserRole($this->Auth->user('id'));
                $arrSettings = array('security_role_id'=>$highestRole['SecurityRole']['id'],'institution_site_id'=>$institutionSiteId);
                $this->SecurityRoleInstitutionSite->addInstitutionSitetoRole($arrSettings);
                //
				
				$this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
			}
			/**
			 * preserve the dropdown values on error
			 */
			if($last_area_id != 0){
				
				$areaLevel = $this->fetchtoParent($last_area_id);
				$areaLevel = array_reverse($areaLevel);
				$areadropdowns = array();
				foreach($areaLevel as $index => &$arrVals){
					$siblings = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $arrVals['parent_id'])));
					$this->Utility->unshiftArray($siblings,array('0'=>'--'.__('Select').'--'));
					$areadropdowns['area_level_'.$index]['options'] = $siblings;
				}
				$maxAreaIndex = max(array_keys($areaLevel));//starts with 0
				$totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
				for($i = $maxAreaIndex; $i <= $totalAreaLevel; $i++ ){
					$areadropdowns['area_level_'.($i+1)]['options'] = array('0'=>'--'.__('Select').'--');
				}
			}
		}
		$visible = true;
        $type = $this->InstitutionSiteType->findList($visible);
		$ownership = $this->InstitutionSiteOwnership->findList($visible);
		$locality = $this->InstitutionSiteLocality->findList($visible);
		$status = $this->InstitutionSiteStatus->findList($visible);
		$this->Utility->unshiftArray($type, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($ownership, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($locality, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($status, array('0'=>'--'.__('Select').'--'));
        
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1','Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
		
        $this->set('type_options',$type);
        $this->set('ownership_options',$ownership);
        $this->set('locality_options',$locality);
        $this->set('status_options',$status);
        $this->set('institutionId',$institutionId);
        $this->set('arealevel',$areaLevel);
		$this->set('levels',$levels);
		$this->set('areadropdowns',$areadropdowns);
        $this->set('highestLevel',$topArea);
    }
	
	public function delete() {
		$id = $this->Session->read('InstitutionSiteId');
		$name = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $id));
		$this->InstitutionSite->delete($id);
		$this->Utility->alert($name . ' have been deleted successfully.');
		$this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
	}

	public function attachments() {
		$this->Navigation->addCrumb('Attachments');
		$id = $this->Session->read('InstitutionSiteId');
		$data = $this->FileAttachment->getList($id);
        $this->set('data', $data);
		$this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
		$this->render('/Elements/attachment/view');
    }
	
    public function attachmentsEdit() {
		$this->Navigation->addCrumb('Edit Attachments');
		$id = $this->Session->read('InstitutionSiteId');
        
		if($this->request->is('post')) { // save
			$errors = $this->FileAttachment->saveAll($this->data, $_FILES, $id);
			if(sizeof($errors) == 0) {
				$this->Utility->alert('Files have been saved successfully.');
				$this->redirect(array('action' => 'attachments'));
			} else {
				$this->Utility->alert('Some errors have been encountered while saving files.', array('type' => 'error'));
			}
        }
		
        $data = $this->FileAttachment->getList($id);
        $this->set('data',$data);
		$this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
		$this->render('/Elements/attachment/edit');
    }
	
	public function attachmentsAdd() {
		$this->layout = 'ajax';
		$this->set('params', $this->params->query);
		$this->render('/Elements/attachment/add');
	}
       
    public function attachmentsDelete() {
		$this->autoRender = false;
        if($this->request->is('post')) {
			$result = array('alertOpt' => array());
			$this->Utility->setAjaxResult('alert', $result);
			$id = $this->params->data['id'];
			
			if($this->FileAttachment->delete($id)) {
				$result['alertOpt']['text'] = __('File is deleted successfully.');
			} else {
				$result['alertType'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = __('Error occurred while deleting file.');
			}
			
			return json_encode($result);
        }
    }
        
    public function attachmentsDownload($id) {
        $this->FileAttachment->download($id);
    }
	
	private function getAllSiteAreaToParent($siteId) {
		if($this->institutionSiteObj['InstitutionSite']['area_id'] == 0) $this->institutionSiteObj['InstitutionSite']['area_id'] = 1;

		$lowest =  $siteId;
		$areas = $this->fetchtoParent($lowest);
		$areas = array_reverse($areas);
		
		/*foreach($areas as $index => &$arrVals){
			$siblings = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $arrVals['parent_id'])));
			$this->Utility->unshiftArray($siblings,array('0'=>'--'.__('Select').'--'));
			pr($siblings);
			$colInfo['area_level_'.$index]['options'] = $siblings;
		}*/
		$arrDisabledList = array();
		foreach($areas as $index => &$arrVals){
			$siblings = $this->Area->find('all',array('fields'=>Array('Area.id','Area.name','Area.parent_id','Area.visible'),'conditions'=>array('Area.parent_id' => $arrVals['parent_id'])));
			//echo "<br>";
			$opt = array();
			foreach($siblings as &$sibVal){
				 
					 $arrDisabledList[$sibVal['Area']['id']] = array('parent_id'=>$sibVal['Area']['parent_id'],'id'=>$sibVal['Area']['id'],'name'=>$sibVal['Area']['name'],'visible'=>$sibVal['Area']['visible']);
				
					 if(isset($arrDisabledList[$sibVal['Area']['parent_id']])){
						 
						//echo $sibVal['Area']['name']. ' '.$arrDisabledList[$sibVal['Area']['parent_id']]['visible'].' <br>';
						if($arrDisabledList[$sibVal['Area']['parent_id']]['visible'] == 0){
							$sibVal['Area']['visible'] = 0;
							$arrDisabledList[$sibVal['Area']['id']]['visible'] = 0;
						}
						 
					 }
			}
			//pr($arrDisabledList);
			foreach($siblings as $sibVal2){
				$o = array('name'=>$sibVal2['Area']['name'],'value'=>$sibVal2['Area']['id']);
				
				if($sibVal2['Area']['visible'] == 0){
					$o['disabled'] = 'disabled';
					
				}
				$opt[] = $o;
			}
			
			
			
			//pr($opt);
			
			$colInfo['area_level_'.$index]['options'] = $opt;
		}
		
		$maxAreaIndex = max(array_keys($areas));//starts with 0
		$totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
		for($i = $maxAreaIndex; $i < $totalAreaLevel;$i++ ){
			$colInfo['area_level_'.($i+1)]['options'] = array('0'=>'--'.__('Select').'--');
		}
		
		return $colInfo;
	}
	
	public function viewAreaChildren($id) {
		$this->autoRender = false;
		$value =$this->Area->find('list',array('conditions'=>array('Area.parent_id' => $id,'Area.visible' => 1)));
		$this->Utility->unshiftArray($value, array('0'=>'--'.__('Select').'--'));
		echo json_encode($value);
	}
	
	private function fetchtoParent($lowest){
		
		$arrVals = Array();
		//pr($lowest);die;
		//$this->autoRender = false; // AJAX
		$list = $this->Area->find('first', array(
								'fields' => array('Area.id', 'Area.name', 'Area.parent_id', 'Area.area_level_id','AreaLevel.name'),
								'conditions' => array('Area.id' => $lowest)));
	
		//check if not false
		if($list){
			$arrVals[$list['Area']['area_level_id']] = Array('level_id'=>$list['Area']['area_level_id'],'id'=>$list['Area']['id'],'name'=>$list['Area']['name'],'parent_id'=>$list['Area']['parent_id'],'AreaLevelName'=>$list['AreaLevel']['name']);
			if($list['Area']['area_level_id'] > 1){
				if($list['Area']['area_level_id']){
					do {
						$list = $this->Area->find('first', array(
								'fields' => array('Area.id', 'Area.name', 'Area.parent_id', 'Area.area_level_id','AreaLevel.name', 'Area.visible'),
								'conditions' => array('Area.id' => $list['Area']['parent_id'])));
						$arrVals[$list['Area']['area_level_id']] = Array('visible'=>$list['Area']['visible'],'level_id'=>$list['Area']['area_level_id'],'id'=>$list['Area']['id'],'name'=>$list['Area']['name'],'parent_id'=>$list['Area']['parent_id'],'AreaLevelName'=>$list['AreaLevel']['name']);
					} while ($list['Area']['area_level_id'] != 1);
				}
			}
		}
		return $arrVals;
	  //echo $arrVals;
	}
	
	public function additional() {
		$this->Navigation->addCrumb('Additional Info');
		
		$datafields = $this->InstitutionSiteCustomField->find('all',array('conditions'=>array('InstitutionSiteCustomField.visible'=>1,'InstitutionSiteCustomField.institution_site_type_id'=>$this->institutionSiteObj['InstitutionSite']['institution_site_type_id']),'order'=>'InstitutionSiteCustomField.order'));
		$this->InstitutionSiteCustomValue->unbindModel(
			array('belongsTo' => array('InstitutionSite'))
		);
		$datavalues = $this->InstitutionSiteCustomValue->find('all',array('conditions'=>array('InstitutionSiteCustomValue.institution_site_id'=>$this->institutionSiteId)));
		$tmp=array();
		foreach($datavalues as $arrV){
			$tmp[$arrV['InstitutionSiteCustomField']['id']][] = $arrV['InstitutionSiteCustomValue'];
		}
		$datavalues = $tmp;
		//pr($tmp);die;
		$this->set('datafields',$datafields);
		$this->set('datavalues',$tmp);
	}
	
	public function additionalEdit() {
		$this->Navigation->addCrumb('Edit Additional Info');
		
		if ($this->request->is('post')) {
			//pr($this->data);
			//die();
			$arrFields = array('textbox','dropdown','checkbox','textarea');
			/**
			 * Note to Preserve the Primary Key to avoid exhausting the max PK limit
			 */
			foreach($arrFields as $fieldVal){
				if(!isset($this->request->data['InstitutionsSiteCustomFieldValue'][$fieldVal]))  continue;
				foreach($this->request->data['InstitutionsSiteCustomFieldValue'][$fieldVal] as $key => $val){
					if($fieldVal == "checkbox"){
						
						$arrCustomValues = $this->InstitutionSiteCustomValue->find('list',array('fields'=>array('value'),'conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $this->institutionSiteId,'InstitutionSiteCustomValue.institution_site_custom_field_id' => $key)));
							
							$tmp = array();
							if(count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
							foreach($arrCustomValues as $pk => $intVal){
								//pr($val['value']); echo "$intVal";
								if(!in_array($intVal, $val['value'])){
									//echo "not in db so remove \n";
								   $this->InstitutionSiteCustomValue->delete($pk);
								}
							}
							$ctr = 0;
							if(count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
							foreach($val['value'] as $intVal){
								//pr($val['value']); echo "$intVal";
								if(!in_array($intVal, $arrCustomValues)){
									$this->InstitutionSiteCustomValue->create();
									$arrV['institution_site_custom_field_id']  = $key;
									$arrV['value']  = $val['value'][$ctr];
									$arrV['institution_site_id']  = $this->institutionSiteId;
									$this->InstitutionSiteCustomValue->save($arrV);
									unset($arrCustomValues[$ctr]);
								}
								 $ctr++;
							}
					}else{ // if editing reuse the Primary KEY; so just update the record
						$x = $this->InstitutionSiteCustomValue->find('first',array('fields'=>array('id','value'),'conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $this->institutionSiteId,'InstitutionSiteCustomValue.institution_site_custom_field_id' => $key)));
						$this->InstitutionSiteCustomValue->create();
						if($x) $this->InstitutionSiteCustomValue->id = $x['InstitutionSiteCustomValue']['id'];
						$arrV['institution_site_custom_field_id']  = $key;
						$arrV['value']  = $val['value'];
						$arrV['institution_site_id']  = $this->institutionSiteId;
						$this->InstitutionSiteCustomValue->save($arrV);
					}
				}
			}
			$this->redirect(array('action' => 'additional'));
		}
		
		$this->institutionSiteObj['InstitutionSite'];
		$datafields = $this->InstitutionSiteCustomField->find('all',array('conditions'=>array('InstitutionSiteCustomField.visible'=>1,'InstitutionSiteCustomField.institution_site_type_id'=>$this->institutionSiteObj['InstitutionSite']['institution_site_type_id'])));
		$this->InstitutionSiteCustomValue->unbindModel(
			array('belongsTo' => array('InstitutionSite'))
		);
		$datavalues = $this->InstitutionSiteCustomValue->find('all',array('conditions'=>array('InstitutionSiteCustomValue.institution_site_id'=>$this->institutionSiteId)));
		$tmp=array();
		foreach($datavalues as $arrV){
			$tmp[$arrV['InstitutionSiteCustomField']['id']][] = $arrV['InstitutionSiteCustomValue'];
		}
		$datavalues = $tmp;
		// pr($tmp);die;
		// pr($datafields);
		$this->set('datafields',$datafields);
		$this->set('datavalues',$tmp);
	}
	
	public function bankAccounts() {
		$this->Navigation->addCrumb('Bank Accounts');
                
		if($this->request->is('post')) {
			//pr($this->data);
			$this->InstitutionSiteAttachment->create();
			$this->request->data['InstitutionSiteBankAccount']['institution_site_id'] = $this->institutionSiteId;
			$this->InstitutionSiteBankAccount->save($this->request->data['InstitutionSiteBankAccount']);
		}
		
		$data = $this->InstitutionSiteBankAccount->find('all',array('conditions'=>array('InstitutionSiteBankAccount.institution_site_id'=>$this->institutionSiteId)));
		$bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
		$banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
		$this->set('data',$data);
		$this->set('bank',$bank);
		$this->set('banklist',$banklist);
	}
	
	public function bankAccountsEdit() {
		$this->Navigation->addCrumb('Edit Bank Accounts');
		if($this->request->is('post')) { // save
			
			foreach($this->request->data['InstitutionSiteBankAccount'] as &$arrVal){
				if($arrVal['id'] == $this->data['InstitutionSiteBankAccount']['active']){
					$arrVal['active'] = 1;
					unset($this->request->data['InstitutionSiteBankAccount']['active']);
					break;
				}
			}
			//pr($this->request->data['InstitutionSiteBankAccount']);
			//die;
			$this->InstitutionSiteBankAccount->saveAll($this->request->data['InstitutionSiteBankAccount']);
		}
		$data = $this->InstitutionSiteBankAccount->find('all',array('conditions'=>array('InstitutionSiteBankAccount.institution_site_id'=>$this->institutionSiteId)));
		$bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));

		$this->set('data',$data);
		$this->set('bank',$bank);
	}
    
	public function bankAccountsDelete($id) {
		if ($this->request->is('get')) {
			throw new MethodNotAllowedException();
		}
		$this->InstitutionSiteBankAccount->id = $id;
		$info = $this->InstitutionSiteBankAccount->read();
		if ($this->InstitutionSiteBankAccount->delete($id)) {
			 $message = __('Record Deleted!', true);

		}else{
			 $message = __('Error Occured. Please, try again.', true);
		}
		if($this->RequestHandler->isAjax()){
			$this->autoRender = false;
			$this->layout = 'ajax';
			echo json_encode(compact('message'));
		}
	}
	
	public function bankAccountsBankBranches() {
		$this->autoRender = false;
		$bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
		echo json_encode($bank);
	}   
	
	public function programmes() {
		$this->Navigation->addCrumb('Programmes');
                
		if($this->request->is('post')) {
			//pr($this->request->data);die;
			foreach($this->request->data['InstitutionSiteProgramme'] as &$arrVals){
				
				$arrVals['institution_site_id'] = $this->institutionSiteId;
				
			}
			$this->InstitutionSiteProgramme->saveAll($this->request->data['InstitutionSiteProgramme']);
			
			$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'programmes'));
		}
		
		$data = $this->InstitutionSiteProgramme->find('all',array('conditions'=>array('InstitutionSiteProgramme.institution_site_id'=>$this->institutionSiteId)));
		//pr($data);die;
		foreach($data as &$arrVals){
			
			$arrSystem = $this->getEducationSystemByCycleId($arrVals['EducationProgramme']['education_cycle_id']);
			$arrFieldofStudy = $this->getFieldofStudyById($arrVals['EducationProgramme']['education_field_of_study_id']);
			$arrCertification = $this->getCertificationById($arrVals['EducationProgramme']['education_certification_id']);
		   
			$arrVals = array_merge($arrVals,$arrSystem,$arrFieldofStudy,$arrCertification);
		}
		//pr($data);die;
		$newSort = array();
		foreach($data as $arrValsSort){
			
			$newSort[$arrValsSort['EducationSystem']['name']][$arrValsSort['EducationLevel']['name']][] = $arrValsSort;
		}
		$educationSystems = $this->EducationSystem->find('list',array('recursive'=>0));
		
		$this->set('arrEducationSystems', $educationSystems);
		$this->set('data',$newSort);       
	}
	
	public function programmesEdit() {
		$this->Navigation->addCrumb('Edit Programmes');
                
		if($this->request->is('post')) {
			//pr($this->request->data);die;
			foreach($this->request->data['InstitutionSiteProgramme'] as &$arrVals){
				
				$arrVals['institution_site_id'] = $this->institutionSiteId;
				
			}
			//pr($this->request->data);die;
			$this->InstitutionSiteProgramme->saveAll($this->request->data['InstitutionSiteProgramme']);
			
			$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'programmes'));
		}
		
		$data = $this->InstitutionSiteProgramme->find('all',array('conditions'=>array('InstitutionSiteProgramme.institution_site_id'=>$this->institutionSiteId)));
		//pr($data);die;
		foreach($data as &$arrVals){
			$arrSystem = $this->getEducationSystemByCycleId($arrVals['EducationProgramme']['education_cycle_id']);
			$arrFieldofStudy = $this->getFieldofStudyById($arrVals['EducationProgramme']['education_field_of_study_id']);
			$arrCertification = $this->getCertificationById($arrVals['EducationProgramme']['education_certification_id']);
			$arrVals = array_merge($arrVals,$arrSystem,$arrFieldofStudy,$arrCertification);
		}
		//pr($data);die;
		$newSort = array();
		foreach($data as $arrValsSort){
			
			$newSort[$arrValsSort['EducationSystem']['name']][$arrValsSort['EducationLevel']['name']][] = $arrValsSort;
		}
		$educationSystems = $this->EducationSystem->find('list',array('recursive'=>0));
		
		$this->set('arrEducationSystems', $educationSystems);
		$this->set('data',$newSort);
	}
        
	public function programmesAvailable($id){
		$this->layout = 'ajax';
		$this->EducationProgramme->unbindModel(
			array('hasMany' => array('InstitutionSiteProgramme'))
		);
		
		$existingPrograms = $this->InstitutionSiteProgramme->find('list',array('fields'=>array('InstitutionSiteProgramme.id','InstitutionSiteProgramme.education_programme_id'),'conditions'=>array('InstitutionSiteProgramme.institution_site_id'=>$this->institutionSiteId)));
		
		//select all not present on the exisiting site's programs
		$data = $this->EducationProgramme->find('all',array('conditions'=>array('NOT'=>array('EducationProgramme.id' => $existingPrograms))));
	   
		foreach($data as $k => &$arrVals){
			$arrSystem = $this->getEducationSystemByCycleId($arrVals['EducationProgramme']['education_cycle_id']);
			if($arrSystem['EducationSystem']['id'] != $id) { //filter only those program under the educ system selected in the front end
				unset($data[$k]); 
				continue;   
			}else{
				$arrVals = array_merge($arrVals,$arrSystem);
			}
		}
		//pr($data);die;
		$this->set('institution_site_id',$this->institutionSiteId);
		$this->set('data',$data);
	}
        
	private function getEducationSystemByCycleId($cycleId){ 
		$arrResCycle = $this->EducationCycle->find('first',array('recursive'=>0,'conditions'=>array('EducationCycle.id'=>$cycleId)));
		$arrResSystem = $this->EducationSystem->find('first',array('recursive'=>0,'conditions'=>array('EducationSystem.id'=>$arrResCycle['EducationLevel']['education_system_id'])));
		return $arrRes = array_merge($arrResCycle,$arrResSystem);
	}
	
	private function getFieldofStudyById($id){
		return $arrRes = $this->EducationFieldOfStudy->find('first',array('recursive'=>0,'conditions'=>array('EducationFieldOfStudy.id'=>$id)));
	}
	
	private function getCertificationById($id){
		return $arrRes = $this->EducationCertification->find('first',array('recursive'=>0,'conditions'=>array('EducationCertification.id'=>$id)));
	}
        
	public function history() {
		$this->Navigation->addCrumb('History');

		$arrTables = array('InstitutionSiteHistory','InstitutionSiteStatus','InstitutionSiteType','InstitutionSiteOwnership','InstitutionSiteLocality','Area');
		$historyData = $this->InstitutionSiteHistory->find('all',array('conditions'=> array('InstitutionSiteHistory.institution_site_id'=>$this->institutionSiteId),'order'=>array('InstitutionSiteHistory.created' => 'desc')));
		//pr($historyData);
		$data2 = array();
		foreach ($historyData as $key => $arrVal) {

			foreach($arrTables as $table){
			//pr($arrVal);die;
				foreach($arrVal[$table] as $k => $v){
					$keyVal = ($k == 'name')?$table.'_name':$k;
					$keyVal = ($k == 'code')?$table.'_code':$keyVal;
					//echo $k.'<br>';
					$data2[$keyVal][$v] = $arrVal['InstitutionSiteHistory']['created'];
				}
			}

		}
		//pr($data2);die;
		$this->set('data',$this->institutionSiteObj);
	   
		$this->set('data2',$data2);
		$this->set('id',$this->institutionSiteId);
	}      
} 