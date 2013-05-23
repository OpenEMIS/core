<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppController', 'Controller'); 

class InstitutionSitesController extends AppController {
	public $institutionSiteId;
    public $institutionSiteObj;
	
	public $uses = array(
		'Area',
		'AreaLevel',
		'Bank',
		'BankBranch',
		'EducationSubject',
		'EducationGrade',
		'EducationProgramme',
		'EducationFieldOfStudy',
		'EducationCertification',
		'EducationCycle',
		'EducationLevel',
		'EducationSystem',
		'Institution',
		'InstitutionSiteClass',
		'InstitutionSiteClassTeacher',
		'InstitutionSiteClassGrade',
		'InstitutionSiteClassGradeStudent',
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
		'InstitutionSiteStudent',
		'InstitutionSiteTeacher',
		'InstitutionSiteStaff',
		'CensusStudent',
		'SecurityUserRole',
		'SecurityRoleInstitutionSite',
		'SchoolYear',
		'Students.Student',
		'Teachers.Teacher',
		'Teachers.TeacherCategory',
		'Staff.Staff',
		'Staff.StaffCategory'
	);
	
	public $helpers = array('Paginator');
	
	public $components = array(
		'Paginator',
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
	
	public  function viewMap(){
		$this->layout = false;
		$string = @file_get_contents('http://www.google.com');
		if ($string){
			$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
			$this->set('data', $data);
		}else{
			$this->autoRender = false;
		}
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
	
	public function programmesGradeList() {
		$this->layout = 'ajax';
		$programmeId = $this->params->query['programmeId'];
		$exclude = $this->params->query['exclude'];
		$gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, $exclude);
		$this->set('gradeOptions', $gradeOptions);
		$this->render('/Elements/programmes/grade_options');
	}
	
	public function programmes() {
		$this->Navigation->addCrumb('Programmes');
		
		$yearOptions = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
		
		$data = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $selectedYear);
		
		foreach($data as $i => $obj) {
			$data[$i]['gender'] = $this->InstitutionSiteStudent->getGenderTotal($obj['id'], $selectedYear);
		}
		
		// Checking if user has access to add
		$_add_programme = $this->AccessControl->check('InstitutionSites', 'programmesAdd');
		$this->set('_add_programme', $_add_programme);
		// End Access Control
		
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('data', $data);
	}
	
	public function programmesAdd() {
		$yearId = $this->params['pass'][0];
		if($this->request->is('get')) {
			$this->layout = 'ajax';
			
			$data = $this->EducationProgramme->getAvailableProgrammeOptions($this->institutionSiteId, $yearId);
			$this->set('data', $data);
		} else {
			$this->autoRender = false;
			$programmeId = $this->params->data['programmeId'];
			
			$obj = array(
				'education_programme_id' => $programmeId,
				'institution_site_id' => $this->institutionSiteId,
				'school_year_id' => $yearId
			);
			
			$this->InstitutionSiteProgramme->create();
			$result = $this->InstitutionSiteProgramme->save($obj);
			$return = array();
			if($result) {
				$this->Utility->setAjaxResult('success', $return);
			} else {
				$this->Utility->setAjaxResult('error', $return);
				$return['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
			}
			return json_encode($return);
		}
	}
	
	public function programmesView() {
		$this->Navigation->addCrumb('Programmes', array('controller' => 'InstitutionSites', 'action' => 'programmes'));
		$this->Navigation->addCrumb('Details');
		
		$yearOptions = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
		$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId, $selectedYear);
		$selectedProgramme = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($programmeOptions);
		
		if(!array_key_exists($selectedProgramme, $programmeOptions)) {
			$selectedProgramme = key($programmeOptions);
		}
		
		$data = array();
		if(empty($programmeOptions)) {
			$programmeOptions[] = '-- ' . __('No Programme') . ' --';
		} else {
			$data = $this->InstitutionSiteStudent->getStudentList($selectedProgramme, $this->institutionSiteId, $selectedYear);
		}
		
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('programmeOptions', $programmeOptions);
		$this->set('selectedProgramme', $selectedProgramme);
		$this->set('data', $data);
	}
	
	public function programmesEdit() {
		if($this->request->is('get')) {
			$this->Navigation->addCrumb('Programmes', array('controller' => 'InstitutionSites', 'action' => 'programmes'));
			$this->Navigation->addCrumb('Edit Details');
			
			$yearOptions = $this->SchoolYear->getYearList();
			$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
			$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId, $selectedYear);
			$selectedProgramme = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($programmeOptions);
			
			if(!array_key_exists($selectedProgramme, $programmeOptions)) {
				$selectedProgramme = key($programmeOptions);
			}
			
			$data = array();
			if(empty($programmeOptions)) {
				$this->redirect(array('action' => programmes));
			} else {
				$data = $this->InstitutionSiteStudent->getStudentList($selectedProgramme, $this->institutionSiteId, $selectedYear);
				$this->set('yearOptions', $yearOptions);
				$this->set('selectedYear', $selectedYear);
				$this->set('programmeOptions', $programmeOptions);
				$this->set('selectedProgramme', $selectedProgramme);
				$this->set('data', $data);
			}
		} else {
			$this->autoRender = false;
			$data = $this->data['InstitutionSiteStudent'];
			foreach($data as &$obj) {
				$start = $obj['start_date'];
				$end = $obj['end_date'];
				$obj['start_date'] = sprintf('%d-%d-%d', $start['year'], $start['month'], $start['day']);
				$obj['end_date'] = sprintf('%d-%d-%d', $end['year'], $end['month'], $end['day']);
				$this->InstitutionSiteStudent->save($obj);
			}
		}
	}
	
	public function programmesAddStudent() {
		$this->layout = 'ajax';
		$studentId = $this->params->query['studentId'];
		$name = $this->params->query['name'];
		$idNo = $this->params->query['idNo'];
		$i = $this->params->query['i'];
		$yearId = $this->params['pass'][0];
		$programmeId = $this->params['pass'][1];
		
		$obj = $this->InstitutionSiteStudent->addStudentToProgramme($studentId, $programmeId, $this->institutionSiteId, $yearId);
		
		$this->set('idNo', $idNo);
		$this->set('name', $name);
		$this->set('i', $i);
		$this->set('obj', $obj['InstitutionSiteStudent']);
	}
	
	public function programmesRemoveStudent() {
		$this->autoRender = false;
		$id = $this->params->query['rowId'];
		$yearId = $this->params['pass'][0];
		$programmeId = $this->params['pass'][1];
		
		if($id != -1) {
			$this->InstitutionSiteStudent->delete($id, false);
		} else {
			$conditions = array(
				'school_year_id' => $yearId,
				'education_programme_id' => $programmeId,
				'institution_site_id' => $this->institutionSiteId
			);
			$institutionSiteProgrammeId = $this->InstitutionSiteProgramme->field('id', $conditions);
			$this->InstitutionSiteStudent->deleteAll(array(
				'InstitutionSiteStudent.institution_site_programme_id' => $institutionSiteProgrammeId
			), false);
		}
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
	
	public function classes() {
		$this->Navigation->addCrumb('Classes');
		$yearOptions = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
		$data = $this->InstitutionSiteClass->getListOfClasses($selectedYear, $this->institutionSiteId);
		
		// Checking if user has access to add
		$_add_class = $this->AccessControl->check('InstitutionSites', 'classesAdd');
		$this->set('_add_class', $_add_class);
		// End Access Control
		
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('data', $data);
	}
	
	public function classesAdd() {
		if($this->request->is('get')) {
			$this->Navigation->addCrumb('Add Class');
			$years = $this->SchoolYear->getYearList();
			$yearOptions = array();
			
			$programmeOptions = array();
			foreach($years as $yearId => $year) {
				$programmes = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId, $yearId);
				if(!empty($programmes)) {
					$yearOptions[$yearId] = $year;
					if(empty($programmeOptions)) {
						$programmeOptions = $programmes;
					}
				}
			}
			$displayContent = !empty($programmeOptions);
			
			if($displayContent) {
				$gradeOptions = array();
				$selectedProgramme = false;
				// loop through the programme list until a valid list of grades is found
				foreach($programmeOptions as $programmeId => $name) {
					$gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, array(), true);
					if(!empty($gradeOptions)) {
						$selectedProgramme = $programmeId;
						break;
					}
				}
				
				$this->set('yearOptions', $yearOptions);
				$this->set('programmeOptions', $programmeOptions);
				$this->set('selectedProgramme', $selectedProgramme);
				$this->set('gradeOptions', $gradeOptions);
			} else {
				$this->Utility->alert($this->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
			}
			$this->set('displayContent', $displayContent);
		} else {
			$classData = $this->data['InstitutionSiteClass'];
			$classData['institution_site_id'] = $this->institutionSiteId;
			$this->InstitutionSiteClass->create();
			$classObj = $this->InstitutionSiteClass->save($classData);
			if($classObj) {
				$classId = $classObj['InstitutionSiteClass']['id'];
				$gradesData = $this->data['InstitutionSiteClassGrade'];
				$grades = array();
				foreach($gradesData as $obj) {
					$gradeId = $obj['education_grade_id'];
					if($gradeId>0 && !in_array($gradeId, $grades)) {
						$grades[] = $obj['education_grade_id'];
						$obj['institution_site_class_id'] = $classId;
						$this->InstitutionSiteClassGrade->create();
						$this->InstitutionSiteClassGrade->save($obj);
					}
				}
			}
			$this->redirect(array('action' => 'classesEdit', $classId));
		}
	}
	
	public function classesView() {
		$classId = $this->params['pass'][0];
		$classObj = $this->InstitutionSiteClass->getClass($classId);
		
		if(!empty($classObj)) {
			$className = $classObj['InstitutionSiteClass']['name'];
			$this->Navigation->addCrumb($className);
			
			$grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
			$students = $this->InstitutionSiteClassGradeStudent->getStudentsByGrade(array_keys($grades));
			$teachers = $this->InstitutionSiteClassTeacher->getTeachers($classId);
			
			$this->set('classId', $classId);
			$this->set('className', $className);
			$this->set('year', $classObj['SchoolYear']['name']);
			$this->set('grades', $grades);
			$this->set('students', $students);
			$this->set('teachers', $teachers);
		} else {
			$this->redirect(array('action' => 'classesList'));
		}
	}
	
	public function classesEdit() {
		$classId = $this->params['pass'][0];
		$classObj = $this->InstitutionSiteClass->getClass($classId);
		
		if(!empty($classObj)) {
			$className = $classObj['InstitutionSiteClass']['name'];
			$this->Navigation->addCrumb(__('Edit') . ' ' . $className);
			
			$grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
			$students = $this->InstitutionSiteClassGradeStudent->getStudentsByGrade(array_keys($grades));
			$teachers = $this->InstitutionSiteClassTeacher->getTeachers($classId);
			
			$this->set('classId', $classId);
			$this->set('className', $className);
			$this->set('year', $classObj['SchoolYear']['name']);
			$this->set('grades', $grades);
			$this->set('students', $students);
			$this->set('teachers', $teachers);
		} else {
			$this->redirect(array('action' => 'classesList'));
		}
	}
	
	public function classesAddGrade() {
		$this->layout = 'ajax';
		$exclude = isset($this->params->query['exclude']) ? $this->params->query['exclude'] : array();
		$index = $this->params->query['index'];
		$yearId = $this->params->query['yearId'];
		$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId, $yearId);
		
		$gradeOptions = array();
		$selectedProgramme = false;
		foreach($programmeOptions as $programmeId => $name) {
			$gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, $exclude, true);
			if(!empty($gradeOptions)) {
				$selectedProgramme = $programmeId;
				break;
			}
		}
		$this->set('model', 'InstitutionSiteClassGrade');
		$this->set('index', $index);
		$this->set('gradeOptions', $gradeOptions);
		$this->set('programmeOptions', $programmeOptions);
		$this->set('selectedProgramme', $selectedProgramme);
	}
	
	public function classesStudentAjax() {
		$this->autoRender = false;
		
		if(sizeof($this->params['pass']) == 1) {
			$gradeId = $this->params['pass'][0];
			$studentId = $this->params->query['studentId'];
			$action = $this->params->query['action'];
			
			$result = false;
			if($action === 'add') {
				$data = array('student_id' => $studentId, 'institution_site_class_grade_id' => $gradeId);
				$this->InstitutionSiteClassGradeStudent->create();
				$result = $this->InstitutionSiteClassGradeStudent->save($data);
			} else {
				$result = $this->InstitutionSiteClassGradeStudent->deleteAll(array(
					'InstitutionSiteClassGradeStudent.student_id' => $studentId,
					'InstitutionSiteClassGradeStudent.institution_site_class_grade_id' => $gradeId
				), false);
			}
			
			$return = array();
			if($result) {
				$this->Utility->setAjaxResult('success', $return);
			} else {
				$this->Utility->setAjaxResult('error', $return);
				$return['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
			}
			return json_encode($return);
		}
	}
	
	public function classesAddStudentRow() {
		$this->layout = 'ajax';
		
		if(sizeof($this->params['pass']) == 2) {
			$year = $this->params['pass'][0];
			$gradeId = $this->params['pass'][1];
			$index = $this->params->query['index'];
			$data = $this->InstitutionSiteStudent->getStudentSelectList($year, $this->institutionSiteId, $gradeId);
			$this->set('index', $index);
			$this->set('gradeId', $gradeId);
			$this->set('data', $data);
		}
	}
	
	public function classesCheckName() {
		$this->autoRender = false;
		$name = trim($this->params->query['name']);
		$yearId = $this->params->query['year'];
		$count = $this->params->query['count'];
		
		if($count==0) {
			return $this->Utility->getMessage('SITE_CLASS_NO_GRADES');
		} else if(strlen($name) == 0) {
			return $this->Utility->getMessage('SITE_CLASS_EMPTY_NAME');
		} else if($this->InstitutionSiteClass->isNameExists($name, $this->institutionSiteId, $yearId)) {
			return $this->Utility->getMessage('SITE_CLASS_DUPLICATE_NAME');
		}
		return 'true';
	}
	
	public function classesAddTeacherRow() {
		$this->layout = 'ajax';
		
		if(sizeof($this->params['pass']) == 2) {
			$year = $this->params['pass'][0];
			$classId = $this->params['pass'][1];
			$index = $this->params->query['index'];
			$data = $this->InstitutionSiteTeacher->getTeacherSelectList($year, $this->institutionSiteId);
			$subjects = $this->EducationSubject->getSubjectByClassId($classId);
			
			$this->set('index', $index);
			$this->set('data', $data);
			$this->set('subjects', $subjects);
		}
	}
	
	public function classesTeacherAjax() {
		$this->autoRender = false;
		
		if(sizeof($this->params['pass']) == 1) {
			$classId = $this->params['pass'][0];
			$teacherId = $this->params->query['teacherId'];
			$subjectId = $this->params->query['subjectId'];
			$action = $this->params->query['action'];
			
			$result = false;
			if($action === 'add') {
				$data = array('teacher_id' => $teacherId, 'institution_site_class_id' => $classId, 'education_subject_id' => $subjectId);
				$this->InstitutionSiteClassTeacher->create();
				$result = $this->InstitutionSiteClassTeacher->save($data);
			} else {
				$result = $this->InstitutionSiteClassTeacher->deleteAll(array(
					'InstitutionSiteClassTeacher.teacher_id' => $teacherId,
					'InstitutionSiteClassTeacher.institution_site_class_id' => $classId,
					'InstitutionSiteClassTeacher.education_subject_id' => $subjectId
				), false);
			}
			
			$return = array();
			if($result) {
				$this->Utility->setAjaxResult('success', $return);
			} else {
				$this->Utility->setAjaxResult('error', $return);
				$return['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
			}
			return json_encode($return);
		}
	}
	
	public function classesDeleteTeacher() {
		$this->autoRender = false;
		
		if(sizeof($this->params['pass']) == 1) {
			$gradeId = $this->params['pass'][0];
			$studentId = $this->params->query['studentId'];
			
			$data = array('student_id' => $studentId, 'institution_site_class_grade_id' => $gradeId);
			$this->InstitutionSiteClassGradeStudent->create();
			$obj = $this->InstitutionSiteClassGradeStudent->save($data);
			
			$result = array();
			if($obj) {
				$this->Utility->setAjaxResult('success', $result);
			} else {
				$this->Utility->setAjaxResult('error', $result);
				$result['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
			}
			return json_encode($result);
		}
	}
	
	public function students() {
		App::uses('Sanitize', 'Utility');
		$this->Navigation->addCrumb('Students');
		
		$page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
		
		$selectedYear = "";
		$selectedProgramme = "";
		$searchField = "";
		$orderBy = 'Student.first_name';
		$order = 'asc';
		$yearOptions = $this->SchoolYear->getYearListValues('start_year');
		$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId);
		$prefix = 'InstitutionSiteStudent.Search.%s';
		if($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->data['Student']['SearchField']));
			$selectedYear = $this->data['Student']['school_year'];
			$selectedProgramme = $this->data['Student']['education_programme_id'];
			$orderBy = $this->data['Student']['orderBy'];
			$order = $this->data['Student']['order'];
			
			$this->Session->write(sprintf($prefix, 'SearchField'), $searchField);
			$this->Session->write(sprintf($prefix, 'SchoolYear'), $selectedYear);
			$this->Session->write(sprintf($prefix, 'EducationProgrammeId'), $selectedProgramme);
			$this->Session->write(sprintf($prefix, 'order'), $order);
			$this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
		} else {
			$searchField = $this->Session->read(sprintf($prefix, 'SearchField'));
			$selectedYear = $this->Session->read(sprintf($prefix, 'SchoolYear'));
			$selectedProgramme = $this->Session->read(sprintf($prefix, 'EducationProgrammeId'));
			
			if($this->Session->check(sprintf($prefix, 'orderBy'))) {
				$orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
			}
			if($this->Session->check(sprintf($prefix, 'order'))) {
				$order = $this->Session->read(sprintf($prefix, 'order'));
			}
		}
		$conditions = array('institution_site_id' => $this->institutionSiteId, 'order' => array($orderBy => $order));
		$conditions['search'] = $searchField;
		if(!empty($selectedYear)) {
			$conditions['year'] = $selectedYear;
		}
		
		if(!empty($selectedProgramme)) {
			$conditions['education_programme_id'] = $selectedProgramme;
		}
		
		$this->paginate = array('limit' => 15, 'maxLimit' => 100);
		$data = $this->paginate('InstitutionSiteStudent', $conditions);
		
		if(empty($data)) {
			$this->Utility->alert($this->Utility->getMessage('STUDENT_SEARCH_NO_RESULT'), array('type' => 'info', 'dismissOnClick' => false));
		}
		$this->set('searchField', $searchField);
		$this->set('page', $page);
		$this->set('orderBy', $orderBy);
		$this->set('order', $order);
		$this->set('yearOptions', $yearOptions);
		$this->set('programmeOptions', $programmeOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('selectedProgramme', $selectedProgramme);
		$this->set('data', $data);
	}
	
	public function studentsSearch() {
		$this->layout = 'ajax';
		$master = isset($this->params->query['master']);
		$searchStr = trim($this->params->query['searchStr']);
		$yearId = $this->params->query['yearId'];
		$programmeId = $this->params->query['programmeId'];
		
		if($master) { // searching students in master list
			$limit = 200;
			$data = $this->Student->search($searchStr, $programmeId, $this->institutionSiteId, $yearId, $limit);
			$this->set('searchStr', $searchStr);
			$this->set('data', $data);
		}
	}
	
	public function teachers() {
		$this->Navigation->addCrumb('Teachers');
		$page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
		$model = 'Teacher';
		$orderBy = $model . '.first_name';
		$order = 'asc';
		$yearOptions = $this->SchoolYear->getYearListValues('start_year');
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
		$prefix = sprintf('InstitutionSite%s.List.%%s', $model);
		if($this->request->is('post')) {
			$selectedYear = $this->data[$model]['school_year'];
			$orderBy = $this->data[$model]['orderBy'];
			$order = $this->data[$model]['order'];
			
			$this->Session->write(sprintf($prefix, 'order'), $order);
			$this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
		} else {
			if($this->Session->check(sprintf($prefix, 'orderBy'))) {
				$orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
			}
			if($this->Session->check(sprintf($prefix, 'order'))) {
				$order = $this->Session->read(sprintf($prefix, 'order'));
			}
		}
		$conditions = array('year' => $selectedYear, 'InstitutionSiteTeacher.institution_site_id' => $this->institutionSiteId);
		
		$this->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => sprintf('%s %s', $orderBy, $order));
		$data = $this->paginate('InstitutionSiteTeacher', $conditions);
		
		$this->set('page', $page);
		$this->set('orderBy', $orderBy);
		$this->set('order', $order);
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('data', $data);
	}
	
	public function teachersSearch() {
		$this->autoRender = false;
		$id = trim($this->params->query['id']);
		$result = array();
		if(strlen($id) == 0) {
			$this->Utility->setAjaxResult('alert', $result);
			$result['alertOpt'] = array();
			$result['alertOpt']['type'] = $this->Utility->getAlertType('alert.error');
			$result['alertOpt']['text'] = $this->Utility->getMessage('INVALID_ID_NO');
		} else {
			$obj = $this->Teacher->find('first', array(
				'fields' => array('Teacher.id', 'Teacher.first_name', 'Teacher.last_name', 'Teacher.gender'),
				'conditions' => array('Teacher.identification_no' => $id)
			));
			if($obj) {
				$teacherId = $obj['Teacher']['id'];
				$status = $this->InstitutionSiteTeacher->checkEmployment($this->institutionSiteId, $teacherId);
				
				$this->Utility->setAjaxResult('success', $result);
				$result['id'] = $obj['Teacher']['id'];
				$result['first_name'] = $obj['Teacher']['first_name'];
				$result['last_name'] = $obj['Teacher']['last_name'];
				$result['gender'] = $this->Utility->formatGender($obj['Teacher']['gender']);
				$result['status'] = $status;
			} else {
				$this->Utility->setAjaxResult('alert', $result);
				$result['alertOpt'] = array();
				$result['alertOpt']['type'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = $this->Utility->getMessage('TEACHER_NOT_FOUND');
			}
		}
		return json_encode($result);
	}
	
	public function teachersAdd() {
		$this->Navigation->addCrumb('Teachers', array('controller' => 'InstitutionSites', 'action' => 'teachers'));
		$this->Navigation->addCrumb('Add Teacher');
		$yearOptions = $this->SchoolYear->getYearList('start_year');
		$categoryOptions = $this->TeacherCategory->findList(true);
		
		if($this->request->is('post')) {
			$data = $this->data['InstitutionSiteTeacher'];
			if(isset($data['teacher_id'])) {
				$data['institution_site_id'] = $this->institutionSiteId;
				$data['start_year'] = date('Y', strtotime($data['start_date']));
				$this->InstitutionSiteTeacher->save($data);
				$this->redirect(array('action' => 'teachersView', $data['teacher_id']));
			}
		}
		$this->set('yearOptions', $yearOptions);
		$this->set('categoryOptions', $categoryOptions);
	}
	
	public function teachersView() {
		$this->Navigation->addCrumb('Teachers', array('controller' => 'InstitutionSites', 'action' => 'teachers'));
		$this->Navigation->addCrumb('Teacher Details');
		
		if(isset($this->params['pass'][0])) {
			$teacherId = $this->params['pass'][0];
			$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
			$positions = $this->InstitutionSiteTeacher->getPositions($teacherId, $this->institutionSiteId);
			if(!empty($positions)) {
				$classes = $this->InstitutionSiteClassTeacher->getClasses($teacherId, $this->institutionSiteId);
				$this->set('data', $data);
				$this->set('positions', $positions);
				$this->set('classes', $classes);
			} else {
				$this->redirect(array('action' => 'teachers'));
			}
		} else {
			$this->redirect(array('action' => 'teachers'));
		}
	}
	
	public function teachersEdit() {
		$this->Navigation->addCrumb('Teachers', array('controller' => 'InstitutionSites', 'action' => 'teachers'));
		$this->Navigation->addCrumb('Edit Teacher Details');
		
		if(isset($this->params['pass'][0])) {
			$teacherId = $this->params['pass'][0];
			
			if($this->request->is('get')) {
				$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
				$positions = $this->InstitutionSiteTeacher->getPositions($teacherId, $this->institutionSiteId);
				if(!empty($positions)) {
					$classes = $this->InstitutionSiteClassTeacher->getClasses($teacherId, $this->institutionSiteId);
					$this->set('data', $data);
					$this->set('positions', $positions);
					$this->set('classes', $classes);
				} else {
					$this->redirect(array('action' => 'teachers'));
				}
			} else {
				if(isset($this->data['delete'])) {
					$delete = $this->data['delete'];
					$this->InstitutionSiteTeacher->deleteAll(array('InstitutionSiteTeacher.id' => $delete), false);
				}
				$data = $this->data['InstitutionSiteTeacher'];
				$this->InstitutionSiteTeacher->saveEmployment($data, $this->institutionSiteId, $teacherId);
				$this->redirect(array('action' => 'teachersView', $teacherId));
			}
		} else {
			$this->redirect(array('action' => 'teachers'));
		}
	}
	
	public function teachersAddPosition() {
		$this->layout = 'ajax';
		
		$index = $this->params->query['index'] + 1;
		$categoryOptions = $this->TeacherCategory->findList(true);
		
		$this->set('index', $index);
		$this->set('categoryOptions', $categoryOptions);
	}
	
	public function staff() {
		$this->Navigation->addCrumb('Staff');
		$page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
		$model = 'Staff';
		$orderBy = $model . '.first_name';
		$order = 'asc';
		$yearOptions = $this->SchoolYear->getYearListValues('start_year');
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
		$prefix = sprintf('InstitutionSite%s.List.%%s', $model);
		if($this->request->is('post')) {
			$selectedYear = $this->data[$model]['school_year'];
			$orderBy = $this->data[$model]['orderBy'];
			$order = $this->data[$model]['order'];
			
			$this->Session->write(sprintf($prefix, 'order'), $order);
			$this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
		} else {
			if($this->Session->check(sprintf($prefix, 'orderBy'))) {
				$orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
			}
			if($this->Session->check(sprintf($prefix, 'order'))) {
				$order = $this->Session->read(sprintf($prefix, 'order'));
			}
		}
		$conditions = array('year' => $selectedYear, 'InstitutionSiteStaff.institution_site_id' => $this->institutionSiteId);
		
		$this->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => sprintf('%s %s', $orderBy, $order));
		$data = $this->paginate('InstitutionSiteStaff', $conditions);
		
		$this->set('page', $page);
		$this->set('orderBy', $orderBy);
		$this->set('order', $order);
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('data', $data);
	}
	
	public function staffSearch() {
		$this->autoRender = false;
		$id = trim($this->params->query['id']);
		$result = array();
		if(strlen($id) == 0) {
			$this->Utility->setAjaxResult('alert', $result);
			$result['alertOpt'] = array();
			$result['alertOpt']['type'] = $this->Utility->getAlertType('alert.error');
			$result['alertOpt']['text'] = $this->Utility->getMessage('INVALID_ID_NO');
		} else {
			$obj = $this->Staff->find('first', array(
				'fields' => array('Staff.id', 'Staff.first_name', 'Staff.last_name', 'Staff.gender'),
				'conditions' => array('Staff.identification_no' => $id)
			));
			if($obj) {
				$staffId = $obj['Staff']['id'];
				$status = $this->InstitutionSiteStaff->checkEmployment($this->institutionSiteId, $staffId);
				
				$this->Utility->setAjaxResult('success', $result);
				$result['id'] = $obj['Staff']['id'];
				$result['first_name'] = $obj['Staff']['first_name'];
				$result['last_name'] = $obj['Staff']['last_name'];
				$result['gender'] = $this->Utility->formatGender($obj['Staff']['gender']);
				$result['status'] = $status;
			} else {
				$this->Utility->setAjaxResult('alert', $result);
				$result['alertOpt'] = array();
				$result['alertOpt']['type'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = $this->Utility->getMessage('STAFF_NOT_FOUND');
			}
		}
		return json_encode($result);
	}
	
	public function staffAdd() {
		$this->Navigation->addCrumb('Staff', array('controller' => 'InstitutionSites', 'action' => 'staff'));
		$this->Navigation->addCrumb('Add Staff');
		$yearOptions = $this->SchoolYear->getYearList('start_year');
		$categoryOptions = $this->StaffCategory->findList(true);
		if($this->request->is('post')) {
			$data = $this->data['InstitutionSiteStaff'];
			if(isset($data['staff_id'])) {
				$data['institution_site_id'] = $this->institutionSiteId;
				$data['start_year'] = date('Y', strtotime($data['start_date']));
				$this->InstitutionSiteStaff->save($data);
				$this->redirect(array('action' => 'staffView', $data['staff_id']));
			}
		}
		$this->set('yearOptions', $yearOptions);
		$this->set('categoryOptions', $categoryOptions);
	}
	
	public function staffView() {
		$this->Navigation->addCrumb('Staff', array('controller' => 'InstitutionSites', 'action' => 'staff'));
		$this->Navigation->addCrumb('Staff Details');
		
		if(isset($this->params['pass'][0])) {
			$staffId = $this->params['pass'][0];
			$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
			$positions = $this->InstitutionSiteStaff->getPositions($staffId, $this->institutionSiteId);
			if(!empty($positions)) {
				$this->set('data', $data);
				$this->set('positions', $positions);
			} else {
				$this->redirect(array('action' => 'staff'));
			}
		} else {
			$this->redirect(array('action' => 'staff'));
		}
	}
	
	public function staffEdit() {
		$this->Navigation->addCrumb('Staff', array('controller' => 'InstitutionSites', 'action' => 'staff'));
		$this->Navigation->addCrumb('Edit Staff Details');
		
		if(isset($this->params['pass'][0])) {
			$staffId = $this->params['pass'][0];
			
			if($this->request->is('get')) {
				$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
				$positions = $this->InstitutionSiteStaff->getPositions($staffId, $this->institutionSiteId);
				if(!empty($positions)) {
					$this->set('data', $data);
					$this->set('positions', $positions);
				} else {
					$this->redirect(array('action' => 'staff'));
				}
			} else {
				if(isset($this->data['delete'])) {
					$delete = $this->data['delete'];
					$this->InstitutionSiteStaff->deleteAll(array('InstitutionSiteStaff.id' => $delete), false);
				}
				$data = $this->data['InstitutionSiteStaff'];
				$this->InstitutionSiteStaff->saveEmployment($data, $this->institutionSiteId, $staffId);
				$this->redirect(array('action' => 'staffView', $staffId));
			}
		} else {
			$this->redirect(array('action' => 'staff'));
		}
	}
	
	public function staffAddPosition() {
		$this->layout = 'ajax';
		
		$index = $this->params->query['index'] + 1;
		$categoryOptions = $this->StaffCategory->findList(true);
		
		$this->set('index', $index);
		$this->set('categoryOptions', $categoryOptions);
	}
}