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
App::uses('AreaHandlerComponent', 'Controller/Component');

class InstitutionSitesController extends AppController {
	public $institutionSiteId;
    public $institutionSiteObj;
	
	public $uses = array(
		'Area',
		'AreaLevel',
		'AreaEducation',
		'AreaEducationLevel',
		'Bank',
		'BankBranch',
		'EducationSubject',
		'EducationGrade',
        'EducationGradeSubject',
		'EducationProgramme',
		'EducationFieldOfStudy',
		'EducationCertification',
		'EducationCycle',
		'EducationLevel',
		'EducationSystem',
		'AssessmentItemType',
		'AssessmentItem',
		'AssessmentItemResult',
		'AssessmentResultType',
		'Institution',
		'InstitutionSiteClass',
		'InstitutionSiteClassTeacher',
        'InstitutionSiteClassSubject',
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
		'SchoolYear',
		'Students.Student',
		'Students.StudentStatus',
		'Students.StudentCategory',
		'Students.StudentBehaviour',
		'Students.StudentBehaviourCategory',
		'Students.StudentAttendance',
		'Teachers.Teacher',
		'Teachers.TeacherStatus',
		'Teachers.TeacherAttendance',
		'Teachers.TeacherCategory',
		'Teachers.TeacherBehaviour',
		'Teachers.TeacherBehaviourCategory',
		'Staff.Staff',
		'Staff.StaffStatus',
		'Staff.StaffAttendance',
		'Staff.StaffCategory',
		'Staff.StaffBehaviour',
		'Staff.StaffBehaviourCategory',
        'SecurityGroupUser',
        'SecurityGroupArea'
	);
	
	public $helpers = array('Paginator');
	
	public $components = array(
		'Paginator',
		'FileAttachment' => array(
			'model' => 'InstitutionSiteAttachment',
			'foreignKey' => 'institution_site_id'
		),
        'AreaHandler'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
                
                $this->Auth->allow('viewMap','siteProfile');
                
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
                        if($this->action == 'siteProfile' || $this->action == 'viewMap'){
                            $this->layout = 'profile';
                        }else{
                            $this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
                        }
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
		$this->Navigation->addCrumb('Overview');
		
		$levels = $this->AreaLevel->find('list',array('recursive'=>0));
		$adminarealevels = $this->AreaEducationLevel->find('list',array('recursive'=>0));
		$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
		
		$areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
		$areaLevel = array_reverse($areaLevel);
	
		$adminarea = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_education_id'],array('AreaEducation','AreaEducationLevel'));
		$adminarea = array_reverse($adminarea);
		
		$this->set('data', $data);
		$this->set('levels',$levels);
		$this->set('adminarealevel',$adminarealevels);
		
		$this->set('arealevel',$areaLevel);
		$this->set('adminarea',$adminarea);
		
	}
	
	public  function viewMap($id = false){
            
		$this->layout = false;
                if($id) $this->institutionSiteId = $id;
		$string = @file_get_contents('http://www.google.com');
		if ($string){
			$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
			$this->set('data', $data);
		}else{
			$this->autoRender = false;
		}
	}

	public function edit() {
		$id = $this->Session->read('InstitutionSiteId');
		
        $this->InstitutionSite->id = $id;
		$this->Navigation->addCrumb('Edit');
		
		if($this->request->is('post')) {
			/**
			 * need to sort the Area to get the the lowest level
			 */
			$last_area_id = 0;
			$last_adminarea_id = 0;
			//this key sort is impt so that the lowest area level will be saved correctly
			ksort($this->request->data['InstitutionSite']);
			foreach($this->request->data['InstitutionSite'] as $key => $arrValSave){
				if(stristr($key,'area_level_') == true && ($arrValSave != '' && $arrValSave != 0)){
					$last_area_id = $arrValSave;
				}
				if(stristr($key,'area_education_level_') == true && ($arrValSave != '' && $arrValSave != 0)){
					$last_adminarea_id = $arrValSave;
				}
			}

			if($last_area_id == 0){
				$last_area_id = '';
			}
			$this->request->data['InstitutionSite']['area_id'] = $last_area_id;
			$this->request->data['InstitutionSite']['area_education_id'] = $last_adminarea_id;
			
			
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
				$areaLevel =$this->AreaHandler->getAreatoParent($last_area_id);

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
			
			if($last_adminarea_id != 0){
				$adminareaLevel = $this->AreaHandler->getAreatoParent($last_adminarea_id,array('AreaEducation','AreaEducationLevel'));
				
				$adminareaLevel = array_reverse($adminareaLevel);
				
				$adminareadropdowns = array();
				foreach($adminareaLevel as $index => &$arrVals){
					$siblings = $this->AreaEducation->find('list',array('conditions'=>array('AreaEducation.parent_id' => $arrVals['parent_id'])));
					$this->Utility->unshiftArray($siblings,array('0'=>'--'.__('Select').'--'));
					$adminareadropdowns['area_education_level_'.$index]['options'] = $siblings;
				}
				
				
				$maxAreaIndex = max(array_keys($adminareaLevel));//starts with 0
				$totalAreaLevel = $this->AreaEducationLevel->find('count'); //starts with 1
				for($i = $maxAreaIndex; $i <= $totalAreaLevel; $i++ ){
					$adminareadropdowns['area_education_level_'.($i+1)]['options'] = array('0'=>'--'.__('Select').'--');
				}
			}
			
			
			
		}else{
			
			$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));
			$this->set('data', $data);
			
			$areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
			$areaLevel = array_reverse($areaLevel);
		
			$adminareaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_education_id'],array('AreaEducation','AreaEducationLevel'));
			$adminareaLevel = array_reverse($adminareaLevel);

			$areadropdowns = $this->AreaHandler->getAllSiteAreaToParent($data['InstitutionSite']['area_id']);
			//pr($areadropdowns);
			//pr($data['InstitutionSite']);
			if(!is_null($data['InstitutionSite']['area_education_id'])){
				$adminareadropdowns = $this->AreaHandler->getAllSiteAreaToParent($data['InstitutionSite']['area_education_id'], array('AreaEducation','AreaEducationLevel'));
				
			}else{
				$topEdArea = $this->AreaEducation->find('list',array('conditions'=>array('parent_id'=>-1)));
				$arr[]  = '--'.__('Select').'--';
				foreach($topEdArea as $k => $v){
					$arr[] = array('name'=>$v,'value'=>$k);
				}
				$adminareadropdowns = array('area_education_level_0'=>array('options'=>$arr));
			}	
		}
		
		$topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1')));
		$disabledAreas = $this->Area->find('list',array('conditions'=>array('Area.visible' => '0')));
		$this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
		$levels = $this->AreaLevel->find('list');
		$adminlevels = $this->AreaEducationLevel->find('list');
		$visible = true;
        $type = $this->InstitutionSiteType->findList($visible);
		$ownership = $this->InstitutionSiteOwnership->findList($visible);
		$locality = $this->InstitutionSiteLocality->findList($visible);
		$status = $this->InstitutionSiteStatus->findList($visible);
		$this->Utility->unshiftArray($type, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($ownership, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($locality, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($status, array('0'=>'--'.__('Select').'--'));

        // Get security group area
        $groupId = $this->SecurityGroupUser->getGroupIdsByUserId($this->Auth->user('id'));
        $filterArea = $this->SecurityGroupArea->getAreas($groupId);

        $this->set('filterArea',$filterArea);
		$this->set('type_options',$type);
        $this->set('ownership_options',$ownership);
        $this->set('locality_options',$locality);
        $this->set('status_options',$status);
		//$this->set('arealevel',$areaLevel);
		//$this->set('adminarealevel',$adminareaLevel);
		//$this->set('levels',$levels);
		//$this->set('adminlevels',$adminlevels);
		//$this->set('areadropdowns',$areadropdowns);
		//$this->set('adminareadropdowns',$adminareadropdowns);
		
		$this->set('disabledAreas',$disabledAreas);
		
		
        $this->set('highestLevel',$topArea);
    }
	
    public function add() {
		
		$this->Navigation->addCrumb('Add New Institution Site');
		$institutionId = $this->Session->read('InstitutionId');
		$areadropdowns = array('0'=>'--'.__('Select').'--');
		$adminareadropdowns = array('0'=>'--'.__('Select').'--');
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
				
				$this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
			}
			/**
			 * preserve the dropdown values on error
			 */
			if($last_area_id != 0){
				
				$areaLevel = $this->AreaHandler->getAreatoParent($last_area_id);
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
		
		$topAdminArea = $this->AreaEducation->find('list',array('conditions'=>array('AreaEducation.parent_id' => '-1','AreaEducation.visible' => 1)));
		
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
		$this->Utility->unshiftArray($topAdminArea, array('0'=>'--'.__('Select').'--'));
		
		$adminlevels = $this->AreaEducationLevel->find('list');

        // Get security group area
        $groupId = $this->SecurityGroupUser->getGroupIdsByUserId($this->Auth->user('id'));
        $filterArea = $this->SecurityGroupArea->getAreas($groupId);

        $this->set('filterArea',$filterArea);
        $this->set('type_options',$type);
        $this->set('ownership_options',$ownership);
        $this->set('locality_options',$locality);
        $this->set('status_options',$status);
        $this->set('institutionId',$institutionId);
        $this->set('arealevel',$areaLevel);
		$this->set('levels',$levels);
		
		$this->set('adminarealevel',$areaLevel);
		$this->set('adminlevels',$adminlevels);
		
		$this->set('areadropdowns',$areadropdowns);
		$this->set('adminareadropdowns',$adminareadropdowns);
        $this->set('highestLevel',$topArea);
		$this->set('highestAdminLevel',$topAdminArea);
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

	public function additional() {
		$this->Navigation->addCrumb('More');
		
		$datafields = $this->InstitutionSiteCustomField->find('all',array('conditions'=>array('InstitutionSiteCustomField.visible'=>1,'InstitutionSiteCustomField.institution_site_type_id'=>(array($this->institutionSiteObj['InstitutionSite']['institution_site_type_id'],0))),'order'=>array('InstitutionSiteCustomField.institution_site_type_id','InstitutionSiteCustomField.order')));
		$this->InstitutionSiteCustomValue->unbindModel(
			array('belongsTo' => array('InstitutionSite'))
		);
		$datavalues = $this->InstitutionSiteCustomValue->find('all',array('conditions'=>array('InstitutionSiteCustomValue.institution_site_id'=>$this->institutionSiteId)));
		$tmp=array();
		foreach($datavalues as $arrV){
			$tmp[$arrV['InstitutionSiteCustomField']['id']][] = $arrV['InstitutionSiteCustomValue'];
		}
		$datavalues = $tmp;
		//pr($datafields);die;
		$this->set('datafields',$datafields);
		$this->set('datavalues',$tmp);
	}
	
	public function additionalEdit() {
		$this->Navigation->addCrumb('Edit More');
		
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
                $datafields = $this->InstitutionSiteCustomField->find('all',array('conditions'=>array('InstitutionSiteCustomField.visible'=>1,'InstitutionSiteCustomField.institution_site_type_id'=>(array($this->institutionSiteObj['InstitutionSite']['institution_site_type_id'],0))),'order'=>array('InstitutionSiteCustomField.institution_site_type_id','InstitutionSiteCustomField.order')));
		//$datafields = $this->InstitutionSiteCustomField->find('all',array('conditions'=>array('InstitutionSiteCustomField.visible'=>1,'InstitutionSiteCustomField.institution_site_type_id'=>$this->institutionSiteObj['InstitutionSite']['institution_site_type_id'])));
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
                
		$data = $this->InstitutionSiteBankAccount->find('all',array('conditions'=>array('InstitutionSiteBankAccount.institution_site_id'=>$this->institutionSiteId)));
		$bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
		$banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
		$this->set('data',$data);
		$this->set('bank',$bank);
		$this->set('banklist',$banklist);
	}

	public function bankAccountsView() {
        $bankAccountId = $this->params['pass'][0];
        $bankAccountObj = $this->InstitutionSiteBankAccount->find('all',array('conditions'=>array('InstitutionSiteBankAccount.id' => $bankAccountId)));
        
        if(!empty($bankAccountObj)) {
            $this->Navigation->addCrumb('Bank Account Details');
            
            $this->Session->write('InstitutionSiteBankAccountId', $bankAccountId);
            $this->set('bankAccountObj', $bankAccountObj);
        }
        $banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
		$this->set('banklist',$banklist);

    }

	public function bankAccountsAdd() {
		$this->Navigation->addCrumb('Add Bank Accounts');
		if($this->request->is('post')) { // save
			$this->InstitutionSiteBankAccount->create();
			if($this->InstitutionSiteBankAccount->save($this->request->data)){
			 	$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
				$this->redirect(array('action' => 'bankAccounts'));
			}
		}
		$bank = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));

        $bankId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : "";
        $bankBranches = $this->BankBranch->find('list', array('conditions'=>array('bank_id'=>$bankId, 'visible'=>1), 'recursive' => -1));
        $this->set('bankBranches', $bankBranches);
        $this->set('selectedBank', $bankId);

		$this->set('institution_site_id', $this->institutionSiteId);
		$this->set('bank',$bank);
	}
	
	public function bankAccountsEdit() {
		$bankBranch = array();

        $bankAccountId = $this->params['pass'][0];
		        
		if($this->request->is('get')) {
            $bankAccountObj = $this->InstitutionSiteBankAccount->find('first',array('conditions'=>array('InstitutionSiteBankAccount.id' => $bankAccountId)));
  
            if(!empty($bankAccountObj)) {
                $this->Navigation->addCrumb('Edit Bank Account Details');
                //$bankAccountObj['StaffQualification']['qualification_institution'] = $institutes[$staffQualificationObj['StaffQualification']['qualification_institution_id']];
                $this->request->data = $bankAccountObj;
		      
 				
            }
         } else {
         	$this->request->data['InstitutionSiteBankAccount']['institution_site_id'] = $this->institutionSiteId;
            if($this->InstitutionSiteBankAccount->save($this->request->data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));	
                $this->redirect(array('action' => 'bankAccountsView', $this->request->data['InstitutionSiteBankAccount']['id']));
            }
     	}

  	 	$bankId = isset($this->params['pass'][1]) ? $this->params['pass'][1] : $bankAccountObj['BankBranch']['bank_id'];
		$this->set('selectedBank', $bankId);

        $bankBranch = $this->BankBranch->find('list', array('conditions'=>array('bank_id'=>$bankId, 'visible'=>1), 'recursive' => -1));
        $this->set('bankBranch', $bankBranch);

     	$bank = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
		$this->set('bank',$bank);

        $this->set('id', $bankAccountId);
	}


	public function bankAccountsDelete($id) {
        if($this->Session->check('InstitutionSiteId') && $this->Session->check('InstitutionSiteBankAccountId')) {
            $id = $this->Session->read('InstitutionSiteBankAccountId');
            $institutionSiteId = $this->Session->read('InstitutionSiteId');
            $name = $this->InstitutionSiteBankAccount->field('account_number', array('InstitutionSiteBankAccount.id' => $id));
            $this->InstitutionSiteBankAccount->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'bankAccounts'));
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
		
		$yearOptions = $this->SchoolYear->getAvailableYears();
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
		$data = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $selectedYear);
		
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('data', $data);
	}
	
	public function programmesEdit() {
		if($this->request->is('get')) {
			$this->Navigation->addCrumb('Edit Programmes');
			
			$yearOptions = $this->SchoolYear->getAvailableYears();
			$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
			$data = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $selectedYear);
			
			$this->set('yearOptions', $yearOptions);
			$this->set('selectedYear', $selectedYear);
			$this->set('data', $data);
		} else {
			$this->autoRender = false;
		}
	}
	
	public function programmesAdd() {
		$yearId = $this->params['pass'][0];
		if($this->request->is('get')) {
			$this->layout = 'ajax';
			
			$data = $this->EducationProgramme->getAvailableProgrammeOptions($this->institutionSiteId, $yearId);
			$_delete_programme = $this->AccessControl->check('InstitutionSites', 'programmesDelete');
			$this->set('data', $data);
			$this->set('_delete_programme', $_delete_programme);
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
	
	public function programmesDelete() {
		if(count($this->params['pass']) == 2) {
			$this->autoRender = false;
			$yearId = $this->params['pass'][0];
			$id = $this->params['pass'][1];
			
			$this->InstitutionSiteProgramme->delete($id, false);
			$this->Utility->alert($this->Utility->getMessage('DELETE_SUCCESS'));
			$this->redirect(array('action' => 'programmes', $yearId));
		}
	}
	
	public function programmesOptions() {
		$this->layout = 'ajax';
		
		$yearId = $this->params->query['yearId'];
		$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeForSelection($this->institutionSiteId, $yearId, false);
		$this->set('programmeOptions', $programmeOptions);
		$this->render('/Elements/programmes/programmes_options');
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
		
		if(empty($data2)) {
			$this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
		} else {
			$adminarealevels = $this->AreaEducationLevel->find('list',array('recursive'=>0));
			$arrEducation = array();
			foreach($data2['area_education_id'] as $val => $time){
				if($val>0){
					$adminarea = $this->AreaHandler->getAreatoParent($val,array('AreaEducation','AreaEducationLevel'));
					$adminarea = array_reverse($adminarea);
	
					$arrVal = '';
					foreach($adminarealevels as $levelid => $levelName){
						$areaVal = array('id'=>'0','name'=>'a');
						foreach($adminarea as $arealevelid => $arrval){
							if($arrval['level_id'] == $levelid) {
								$areaVal = $arrval;
								$arrVal .= ($areaVal['name']=='a'?'':$areaVal['name']).' ('.$levelName.') '.',';
								continue;
							}
						}
					}
					$arrEducation[] =array('val'=> str_replace(',',' &rarr; ',rtrim($arrVal,',')),'time'=>$time);
				}
			}
	
			$myData = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
			$adminarea = $this->AreaHandler->getAreatoParent($myData['InstitutionSite']['area_education_id'],array('AreaEducation','AreaEducationLevel'));
			$adminarea = array_reverse($adminarea);
			$arrVal = '';
			foreach($adminarealevels as $levelid => $levelName){
				$areaVal = array('id'=>'0','name'=>'a');
				foreach($adminarea as $arealevelid => $arrval){
					if($arrval['level_id'] == $levelid) {
						$areaVal = $arrval;
						$arrVal .= ($areaVal['name']=='a'?'':$areaVal['name']).' ('.$levelName.') '.',';
						continue;
					}
				}
			}
			$arrEducationVal = str_replace(',',' &rarr; ',rtrim($arrVal,','));
			$this->set('arrEducation',$arrEducation);
			$this->set('arrEducationVal',$arrEducationVal);
		}
        $data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
		$this->set('data',$data);
		$this->set('data2',$data2);
		$this->set('id',$this->institutionSiteId);
	}
	
	public function classes() {
		$this->Navigation->addCrumb('List of Classes');
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
        $this->Session->write('InstitutionSiteClassId', $classId);
		$classObj = $this->InstitutionSiteClass->getClass($classId);
		
		if(!empty($classObj)) {
			$className = $classObj['InstitutionSiteClass']['name'];
			$this->Navigation->addCrumb($className);
			
			$grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
			$students = $this->InstitutionSiteClassGradeStudent->getStudentsByGrade(array_keys($grades));
			$teachers = $this->InstitutionSiteClassTeacher->getTeachers($classId);
            $subjects = $this->InstitutionSiteClassSubject->getSubjects($classId);
			
			$this->set('classId', $classId);
			$this->set('className', $className);
			$this->set('yearId', $classObj['SchoolYear']['id']);
			$this->set('year', $classObj['SchoolYear']['name']);
			$this->set('grades', $grades);
			$this->set('students', $students);
			$this->set('teachers', $teachers);
                        $this->set('no_of_seats', $classObj['InstitutionSiteClass']['no_of_seats']);
                        $this->set('no_of_shifts', $classObj['InstitutionSiteClass']['no_of_shifts']);
                        
            $this->set('subjects', $subjects);
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
            $subjects = $this->InstitutionSiteClassSubject->getSubjects($classId);
			
			$this->set('classId', $classId);
			$this->set('className', $className);
			$this->set('year', $classObj['SchoolYear']['name']);
			$this->set('grades', $grades);
			$this->set('students', $students);
			$this->set('teachers', $teachers);
                        $this->set('no_of_seats', $classObj['InstitutionSiteClass']['no_of_seats']);
                        $this->set('no_of_shifts', $classObj['InstitutionSiteClass']['no_of_shifts']);
                        
            $this->set('subjects', $subjects);
		} else {
			$this->redirect(array('action' => 'classesList'));
		}
	}
	
	public function classesDelete() {
		$id = $this->params['pass'][0];
		$name = $this->InstitutionSiteClass->field('name', array('InstitutionSiteClass.id' => $id));
		$this->InstitutionSiteClass->delete($id);
		$this->Utility->alert($name . ' have been deleted successfully.');
		$this->redirect(array('action' => 'classes'));
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
				$categoryId = $this->params->query['categoryId'];
				$data = array(
					'student_id' => $studentId, 
					'student_category_id' => $categoryId,
					'institution_site_class_grade_id' => $gradeId
				);
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
			$categoryOptions = $this->StudentCategory->findList(true);
			$this->set('index', $index);
			$this->set('gradeId', $gradeId);
			$this->set('data', $data);
			$this->set('categoryOptions', $categoryOptions);
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

    public function classesAddSubjectRow() {
        $this->layout = 'ajax';

        if(sizeof($this->params['pass']) == 2) {
            $year = $this->params['pass'][0];
            $classId = $this->params['pass'][1];
            $subjects = $this->EducationSubject->getSubjectByClassId($classId);
            $this->set('subjects', $subjects);
        }
    }

    public function classesSubjectAjax() {
        $this->autoRender = false;

        if(sizeof($this->params['pass']) == 1) {
            $classId = $this->params['pass'][0];
            $subjectId = $this->params->query['subjectId'];
            $action = $this->params->query['action'];

            $result = false;
            if($action === 'add') {
                $data = array('institution_site_class_id' => $classId, 'education_grade_subject_id' => $subjectId);
                $this->InstitutionSiteClassSubject->create();
                $result = $this->InstitutionSiteClassSubject->save($data);
            } else {
                $result = $this->InstitutionSiteClassSubject->deleteAll(array(
                    'InstitutionSiteClassSubject.institution_site_class_id' => $classId,
                    'InstitutionSiteClassSubject.education_grade_subject_id' => $subjectId
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

    public function classesDeleteSubject() {
        $id = $this->params['pass'][0];
        $name = $this->InstitutionSiteClassSubject->field('name', array('InstitutionSiteClassSubject.id' => $id));
        $this->InstitutionSiteClassSubject->delete($id);
        $this->Utility->alert('Subject has been deleted successfully.');
        $this->redirect(array('action' => 'classes'));
    }
	
	public function classesAddTeacherRow() {
		$this->layout = 'ajax';
		
		if(sizeof($this->params['pass']) == 2) {
			$year = $this->params['pass'][0];
			$classId = $this->params['pass'][1];
			$index = $this->params->query['index'];
			$data = $this->InstitutionSiteTeacher->getTeacherSelectList($year, $this->institutionSiteId, $classId);

			$this->set('index', $index);
			$this->set('data', $data);
		}
	}
	
	public function classesTeacherAjax() {
		$this->autoRender = false;
		
		if(sizeof($this->params['pass']) == 1) {
			$classId = $this->params['pass'][0];
			$teacherId = $this->params->query['teacherId'];
			$action = $this->params->query['action'];
			
			$result = false;
			if($action === 'add') {
				$data = array('teacher_id' => $teacherId, 'institution_site_class_id' => $classId);
				$this->InstitutionSiteClassTeacher->create();
				$result = $this->InstitutionSiteClassTeacher->save($data);
			} else {
				$result = $this->InstitutionSiteClassTeacher->deleteAll(array(
					'InstitutionSiteClassTeacher.teacher_id' => $teacherId,
					'InstitutionSiteClassTeacher.institution_site_class_id' => $classId
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
	
	public function classesAssessments() {
		if(isset($this->params['pass'][0])) {
			$classId = $this->params['pass'][0];
			$class = $this->InstitutionSiteClass->findById($classId);
			if($class) {
				$class = $class['InstitutionSiteClass'];
				$this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
				$this->Navigation->addCrumb('Results');
				$data = $this->AssessmentItemType->getAssessmentsByClass($classId);
				
				if(empty($data)) {
					$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
				}
				$this->set('classId', $classId);
				$this->set('data', $data);
			} else {
				$this->redirect(array('action' => 'classes'));
			}
		} else {
			$this->redirect(array('action' => 'classes'));
		}
	}
	
	public function classesResults() {
		if(count($this->params['pass'])==2 || count($this->params['pass'])==3) {
			$classId = $this->params['pass'][0];
			$assessmentId = $this->params['pass'][1];
			$class = $this->InstitutionSiteClass->findById($classId);
			$selectedItem = 0;
			if($class) {
				$class = $class['InstitutionSiteClass'];
				$this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
				$this->Navigation->addCrumb('Results');
				$items = $this->AssessmentItem->getItemList($assessmentId);
				if(empty($items)) {
					$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT_ITEM'), array('type' => 'info'));
				} else {
					$selectedItem = isset($this->params['pass'][2]) ? $this->params['pass'][2] : key($items);
					$data = $this->InstitutionSiteClassGradeStudent->getStudentAssessmentResults($classId, $selectedItem);
					if(empty($data)) {
						$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_STUDENTS'), array('type' => 'info'));
					}
					$this->set('itemOptions', $items);
					$this->set('data', $data);
				}
				$this->set('classId', $classId);
				$this->set('assessmentId', $assessmentId);
				$this->set('selectedItem', $selectedItem);
			} else {
				$this->redirect(array('action' => 'classes'));
			}
		} else {
			$this->redirect(array('action' => 'classes'));
		}
	}
	
	public function classesResultsEdit() {
		if(count($this->params['pass'])==2 || count($this->params['pass'])==3) {
			$classId = $this->params['pass'][0];
			$assessmentId = $this->params['pass'][1];
			$class = $this->InstitutionSiteClass->findById($classId);
			$selectedItem = 0;
			if($class) {
				$class = $class['InstitutionSiteClass'];
				$this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
				$this->Navigation->addCrumb('Results');
				$items = $this->AssessmentItem->getItemList($assessmentId);
				if(empty($items)) {
					$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT_ITEM'), array('type' => 'info'));
				} else {
					$selectedItem = isset($this->params['pass'][2]) ? $this->params['pass'][2] : key($items);
					$data = $this->InstitutionSiteClassGradeStudent->getStudentAssessmentResults($classId, $selectedItem);
					if($this->request->is('get')) {
						if(empty($data)) {
							$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_STUDENTS'), array('type' => 'info'));
						}
						$gradingOptions = $this->AssessmentResultType->findList(true);
						$this->set('classId', $classId);
						$this->set('assessmentId', $assessmentId);
						$this->set('selectedItem', $selectedItem);
						$this->set('itemOptions', $items);
						$this->set('gradingOptions', $gradingOptions);
						$this->set('data', $data);
					} else {
						if(isset($this->data['AssessmentItemResult'])) {
							$result = $this->data['AssessmentItemResult'];
							foreach($result as $key => &$obj) {
								$obj['assessment_item_id'] = $selectedItem;
								$obj['institution_site_id'] = $this->institutionSiteId;
							}
							if(!empty($result)) {
								$this->AssessmentItemResult->saveMany($result);
								$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
							}
						}
						$this->redirect(array('action' => 'classesResults', $classId, $assessmentId, $selectedItem));
					}
				}
				$this->set('classId', $classId);
				$this->set('assessmentId', $assessmentId);
				$this->set('selectedItem', $selectedItem);
			} else {
				$this->redirect(array('action' => 'classes'));
			}
		} else {
			$this->redirect(array('action' => 'classes'));
		}
	}
	
	public function students() {
		App::uses('Sanitize', 'Utility');
		$this->Navigation->addCrumb('List of Students');
		
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
		
		// Checking if user has access to add
		$_add_student = $this->AccessControl->check('InstitutionSites', 'studentsAdd');
		$this->set('_add_student', $_add_student);
		// End Access Control
		
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
		$search = trim($this->params->query['searchString']);
		$params = array('limit' => 100);
		$data = $this->Student->search($search, $params);
		$this->set('search', $search);
		$this->set('data', $data);
	}
	
	public function studentsAdd() {
		$this->Navigation->addCrumb('Add Student');
		$yearOptions = $this->SchoolYear->getYearList();
		$yearRange = $this->SchoolYear->getYearRange();
		$statusOptions = $this->StudentStatus->findList(true);
		$programmeOptions = array();
		$selectedYear = '';
		if(!empty($yearOptions)) {
			$selectedYear = key($yearOptions);
			$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeForSelection($this->institutionSiteId, $selectedYear);
		}
		$this->set('yearOptions', $yearOptions);
		$this->set('minYear', current($yearRange));
		$this->set('maxYear', array_pop($yearRange));
		$this->set('programmeOptions', $programmeOptions);
		$this->set('statusOptions', $statusOptions);
	}
	
	public function studentsSave() {
		if($this->request->is('post')) {
			$data = $this->data['InstitutionSiteStudent'];
			if(isset($data['student_id'])) {
				$date = $data['start_date'];
				if(!empty($date['day']) && !empty($date['month']) && !empty($date['year'])) {
					$data['start_year'] = $date['year'];
					$yr = $date['year'];
					$mth = $date['month'];
					$day = $date['day'];
					
					while(!checkdate($mth, $day, $yr)) {
						$day--;
					}
					$data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
					$date = $data['start_date'];
					$student = $this->Student->find('first', array('conditions' => array('Student.id' => $data['student_id'])));
					$name = $student['Student']['first_name'] . ' ' . $student['Student']['last_name'];
					$siteProgrammeId = $data['institution_site_programme_id'];
					$exists = $this->InstitutionSiteStudent->isStudentExistsInProgramme($data['student_id'], $siteProgrammeId, $data['start_year']);
					
					if(!$exists) {
						$duration = $this->EducationProgramme->getDurationBySiteProgramme($siteProgrammeId);
						$startDate = new DateTime(sprintf('%s-%s-%s', $date['year'], $date['month'], $date['day']));
						$endDate = $startDate->add(new DateInterval('P' . $duration . 'Y'));
						$endYear = $endDate->format('Y');
						$data['end_date'] = $endDate->format('Y-m-d');
						$data['end_year'] = $endYear;
						$this->InstitutionSiteStudent->save($data);
						$this->Utility->alert($this->Utility->getMessage('CREATE_SUCCESS'));
					} else {
						$this->Utility->alert($name . ' ' . $this->Utility->getMessage('STUDENT_ALREADY_ADDED'), array('type' => 'error'));
					}
				} else {
					$this->Utility->alert($this->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
				}
				$this->redirect(array('action' => 'studentsAdd'));
			}
		}
	}
	
	public function studentsView() {
		if(isset($this->params['pass'][0])) {
			$studentId = $this->params['pass'][0];
			$this->Session->write('InstitutionSiteStudentId', $studentId);
			$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
			$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
			$this->Navigation->addCrumb($name);
			
			$details = $this->InstitutionSiteStudent->getDetails($studentId, $this->institutionSiteId);
			$classes = $this->InstitutionSiteClassGradeStudent->getListOfClassByStudent($studentId, $this->institutionSiteId);
			$results = $this->AssessmentItemResult->getResultsByStudent($studentId, $this->institutionSiteId);
			$results = $this->AssessmentItemResult->groupItemResults($results);
			$_view_details = $this->AccessControl->check('Students', 'view');
			$this->set('_view_details', $_view_details);
			$this->set('data', $data);
			$this->set('classes', $classes);
			$this->set('results', $results);
			$this->set('details', $details);
		} else {
			$this->redirect(array('action' => 'students'));
		}
	}
	
	public function studentsEdit() {
		if($this->Session->check('InstitutionSiteStudentId')) {
			$studentId = $this->Session->read('InstitutionSiteStudentId');
			if($this->request->is('post')) {
				$postData = $this->request->data['InstitutionSiteStudent'];
				foreach($postData as $i => $obj) {
					$postData[$i]['start_year'] = date('Y', strtotime($obj['start_date']));
					$postData[$i]['end_year'] = date('Y', strtotime($obj['end_date']));
				}
				$this->InstitutionSiteStudent->saveMany($postData);
				$this->Utility->alert($this->Utility->getMessage('UPDATE_SUCCESS'));
				return $this->redirect(array('action' => 'studentsView', $studentId));
			}
			
			$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
			$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
			$this->Navigation->addCrumb($name);
			$statusOptions = $this->StudentStatus->findList(true);
			
			$details = $this->InstitutionSiteStudent->getDetails($studentId, $this->institutionSiteId);
			$classes = $this->InstitutionSiteClassGradeStudent->getListOfClassByStudent($studentId, $this->institutionSiteId);
			$results = $this->AssessmentItemResult->getResultsByStudent($studentId, $this->institutionSiteId);
			$results = $this->AssessmentItemResult->groupItemResults($results);
			$_view_details = $this->AccessControl->check('Students', 'view');
			$this->set('_view_details', $_view_details);
			$this->set('data', $data);
			$this->set('classes', $classes);
			$this->set('results', $results);
			$this->set('details', $details);
			$this->set('statusOptions', $statusOptions);
		} else {
			$this->redirect(array('action' => 'students'));
		}
	}
	
	public function teachers() {
		$this->Navigation->addCrumb('List of Teachers');
		$page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
		$model = 'Teacher';
		$orderBy = $model . '.first_name';
		$order = 'asc';
		$yearOptions = $this->SchoolYear->getYearListValues('start_year');
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : '';
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
		
		// Checking if user has access to add
		$_add_teacher = $this->AccessControl->check('InstitutionSites', 'teachersAdd');
		$this->set('_add_teacher', $_add_teacher);
		// End Access Control
		
		$this->set('page', $page);
		$this->set('orderBy', $orderBy);
		$this->set('order', $order);
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('data', $data);
	}
	
	public function teachersSearch() {
		$this->layout = 'ajax';
		$search = trim($this->params->query['searchString']);
		$params = array('limit' => 100);
		$data = $this->Teacher->search($search, $params);
		$this->set('search', $search);
		$this->set('data', $data);
	}
	
	public function teachersAdd() {
		$this->Navigation->addCrumb('Add Teacher');
		$yearRange = $this->SchoolYear->getYearRange();
		$categoryOptions = $this->TeacherCategory->findList(true);
		$statusOptions = $this->TeacherStatus->findList(true);
		
		$this->set('minYear', current($yearRange));
		$this->set('maxYear', array_pop($yearRange));
		$this->set('categoryOptions', $categoryOptions);
		$this->set('statusOptions', $statusOptions);
	}
	
	public function teachersSave() {
		if($this->request->is('post')) {
			$data = $this->data['InstitutionSiteTeacher'];
			if(isset($data['teacher_id'])) {
				if(!empty($data['start_date']['day']) && !empty($data['start_date']['month']) && !empty($data['start_date']['year'])) {
					$data['institution_site_id'] = $this->institutionSiteId;
					$data['start_year'] = $data['start_date']['year'];
					$yr = $data['start_date']['year'];
					$mth = $data['start_date']['month'];
					$day = $data['start_date']['day'];
					
					while(!checkdate($mth, $day, $yr)) {
						$day--;
					}
					$data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
					$insert = true;
					if(!empty($data['position_no'])) {
						$obj = $this->InstitutionSiteTeacher->isPositionNumberExists($data['position_no'], $data['start_date']);
						if(!$obj) {
							$obj = $this->InstitutionSiteStaff->isPositionNumberExists($data['position_no'], $data['start_date']);
						}
						if($obj) {
							$teacherObj = $this->Teacher->find('first', array(
								'fields' => array('Teacher.identification_no', 'Teacher.first_name', 'Teacher.last_name', 'Teacher.gender'),
								'conditions' => array('Teacher.id' => $data['teacher_id'])
							));
							$position = $data['position_no'];
							$name = '<b>' . trim($obj['first_name'] . ' ' . $obj['last_name']) . '</b>';
							$school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
							$msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
							$msg .= '<br>' . __('Please choose another position number.');
							$this->Utility->alert($msg, array('type' => 'warn'));
							$insert = false;
						}
					}
				} else {
					$this->Utility->alert($this->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
				}
				if($insert) {
					$this->InstitutionSiteTeacher->save($data);
					$this->Utility->alert($this->Utility->getMessage('CREATE_SUCCESS'));
				}
				$this->redirect(array('action' => 'teachersAdd'));
			}
		}
	}
	
	public function teachersView() {
		if(isset($this->params['pass'][0])) {
			$teacherId = $this->params['pass'][0];
			$this->Session->write('InstitutionSiteTeachersId', $teacherId);
			$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
			$name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
			$positions = $this->InstitutionSiteTeacher->getPositions($teacherId, $this->institutionSiteId);
			$this->Navigation->addCrumb($name);
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
		if(isset($this->params['pass'][0])) {
			$teacherId = $this->params['pass'][0];
			
			if($this->request->is('get')) {
				$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
				$name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
				$positions = $this->InstitutionSiteTeacher->getPositions($teacherId, $this->institutionSiteId);
				$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $data['Teacher']['id']));
				$this->Navigation->addCrumb('Edit');
				
				if(!empty($positions)) {
					$classes = $this->InstitutionSiteClassTeacher->getClasses($teacherId, $this->institutionSiteId);
					$statusOptions = $this->TeacherStatus->findList(true);
					$_view_details = $this->AccessControl->check('Teachers', 'view');
					$this->set('_view_details', $_view_details);
					$this->set('data', $data);
					$this->set('positions', $positions);
					$this->set('classes', $classes);
					$this->set('statusOptions', $statusOptions);
				} else {
					$this->redirect(array('action' => 'teachers'));
				}
			} else {
				if(isset($this->data['delete'])) {
					$delete = $this->data['delete'];
					$this->InstitutionSiteTeacher->deleteAll(array('InstitutionSiteTeacher.id' => $delete), false);
				}
				$data = $this->data['InstitutionSiteTeacher'];
				// checking for existing position number
				foreach($data as $i => $row) {
					if(!array_key_exists('id', $row)) {
						if($row['position_no'] === __('Position No')) {
							$data[$i]['position_no'] = null;
						} else {
							$obj = $this->InstitutionSiteTeacher->isPositionNumberExists($row['position_no'], $row['start_date']);
							if(!$obj) {
								$obj = $this->InstitutionSiteStaff->isPositionNumberExists($row['position_no'], $row['start_date']);
							}
							if($obj) {
								$position = $row['position_no'];
								$name = '<b>' . trim($obj['first_name'] . ' ' . $obj['last_name']) . '</b>';
								$school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
								$msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
								$msg .= '<br>' . __('Please choose another position number.');
								$this->Utility->alert($msg, array('type' => 'warn'));
								unset($data[$i]);
							}
						}
					}
				}
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
		$statusOptions = $this->TeacherStatus->findList(true);
		
		$this->set('index', $index);
		$this->set('categoryOptions', $categoryOptions);
		$this->set('statusOptions', $statusOptions);
	}
	
	public function staff() {
		$this->Navigation->addCrumb('List of Staff');
		$page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
		$model = 'Staff';
		$orderBy = $model . '.first_name';
		$order = 'asc';
		$yearOptions = $this->SchoolYear->getYearListValues('start_year');
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : '';
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
		
		// Checking if user has access to add
		$_add_staff = $this->AccessControl->check('InstitutionSites', 'staffAdd');
		$this->set('_add_staff', $_add_staff);
		// End Access Control
		
		$this->set('page', $page);
		$this->set('orderBy', $orderBy);
		$this->set('order', $order);
		$this->set('yearOptions', $yearOptions);
		$this->set('selectedYear', $selectedYear);
		$this->set('data', $data);
	}
	
	public function staffSearch() {
		$this->layout = 'ajax';
		$search = trim($this->params->query['searchString']);
		$params = array('limit' => 100);
		$data = $this->Staff->search($search, $params);
		$this->set('search', $search);
		$this->set('data', $data);
	}
	
	public function staffAdd() {
		$this->Navigation->addCrumb('Add Staff');
		$yearRange = $this->SchoolYear->getYearRange();
		$categoryOptions = $this->StaffCategory->findList(true);
		$statusOptions = $this->StaffStatus->findList(true);
		
		$this->set('minYear', current($yearRange));
		$this->set('maxYear', array_pop($yearRange));
		$this->set('categoryOptions', $categoryOptions);
		$this->set('statusOptions', $statusOptions);
	}
	
	public function staffSave() {
		if($this->request->is('post')) {
			$data = $this->data['InstitutionSiteStaff'];
			if(isset($data['staff_id'])) {
				if(!empty($data['start_date']['day']) && !empty($data['start_date']['month']) && !empty($data['start_date']['year'])) {
					$data['institution_site_id'] = $this->institutionSiteId;
					$data['start_year'] = $data['start_date']['year'];
					$data['start_year'] = $data['start_date']['year'];
					$yr = $data['start_date']['year'];
					$mth = $data['start_date']['month'];
					$day = $data['start_date']['day'];
					
					while(!checkdate($mth, $day, $yr)) {
						$day--;
					}
					$data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
					$insert = true;
					if(!empty($data['position_no'])) {
						$obj = $this->InstitutionSiteStaff->isPositionNumberExists($data['position_no'], $data['start_date']);
						if(!$obj) {
							$obj = $this->InstitutionSiteTeacher->isPositionNumberExists($data['position_no'], $data['start_date']);
						}
						if($obj) {
							$staffObj = $this->Staff->find('first', array(
								'fields' => array('Staff.identification_no', 'Staff.first_name', 'Staff.last_name', 'Staff.gender'),
								'conditions' => array('Staff.id' => $data['staff_id'])
							));
							$position = $data['position_no'];
							$name = '<b>' . trim($obj['first_name'] . ' ' . $obj['last_name']) . '</b>';
							$school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
							$msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
							$msg .= '<br>' . __('Please choose another position number.');
							$this->Utility->alert($msg, array('type' => 'warn'));
							$insert = false;
						}
					}
				} else {
					$this->Utility->alert($this->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
				}
				if($insert) {
					$this->InstitutionSiteStaff->save($data);
					$this->Utility->alert($this->Utility->getMessage('CREATE_SUCCESS'));
				}
				$this->redirect(array('action' => 'staffAdd'));
			}
		}
	}
	
	public function staffView() {
		if(isset($this->params['pass'][0])) {
			$staffId = $this->params['pass'][0];
			$this->Session->write('InstitutionSiteStaffId', $staffId);
			$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
			$name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
			$positions = $this->InstitutionSiteStaff->getPositions($staffId, $this->institutionSiteId);
			$this->Navigation->addCrumb($name);
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
		if(isset($this->params['pass'][0])) {
			$staffId = $this->params['pass'][0];
			
			if($this->request->is('get')) {
				$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
				$name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
				$positions = $this->InstitutionSiteStaff->getPositions($staffId, $this->institutionSiteId);
				$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $data['Staff']['id']));
				$this->Navigation->addCrumb('Edit');
				if(!empty($positions)) {
					$statusOptions = $this->StaffStatus->findList(true);
					$_view_details = $this->AccessControl->check('Staff', 'view');
					$this->set('_view_details', $_view_details);
					$this->set('data', $data);
					$this->set('positions', $positions);
					$this->set('statusOptions', $statusOptions);
				} else {
					$this->redirect(array('action' => 'staff'));
				}
			} else {
				if(isset($this->data['delete'])) {
					$delete = $this->data['delete'];
					$this->InstitutionSiteStaff->deleteAll(array('InstitutionSiteStaff.id' => $delete), false);
				}
				$data = $this->data['InstitutionSiteStaff'];
				// checking for existing position number
				foreach($data as $i => $row) {
					if(!array_key_exists('id', $row)) {
						if($row['position_no'] === __('Position No')) {
							$data[$i]['position_no'] = null;
						} else {
							$obj = $this->InstitutionSiteTeacher->isPositionNumberExists($row['position_no'], $row['start_date']);
							if(!$obj) {
								$obj = $this->InstitutionSiteStaff->isPositionNumberExists($row['position_no'], $row['start_date']);
							}
							if($obj) {
								$position = $row['position_no'];
								$name = '<b>' . trim($obj['first_name'] . ' ' . $obj['last_name']) . '</b>';
								$school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
								$msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
								$msg .= '<br>' . __('Please choose another position number.');
								$this->Utility->alert($msg, array('type' => 'warn'));
								unset($data[$i]);
							}
						}
					}
				}
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
		$statusOptions = $this->StaffStatus->findList(true);
		
		$this->set('index', $index);
		$this->set('categoryOptions', $categoryOptions);
		$this->set('statusOptions', $statusOptions);
	}
	
	//TEACHER CUSTOM FIELD PER YEAR - STARTS - 
	private function teachersCustFieldYrInits(){
		$action = $this->action;
		$siteid = $this->institutionSiteId;
		$id = @$this->request->params['pass'][0];
		$years = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('teacher_id'=>$id,'institution_site_id'=>$siteid,'school_year_id'=>$selectedYear);
		$arrMap = array('CustomField'=>'TeacherDetailsCustomField',
						'CustomFieldOption'=>'TeacherDetailsCustomFieldOption',
						'CustomValue'=>'TeacherDetailsCustomValue',
						'Year'=>'SchoolYear');
		//BreadCrumb -- jeff logic
		$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $id)));
		$name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
		$positions = $this->InstitutionSiteTeacher->getPositions($id, $this->institutionSiteId);
		$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $data['Teacher']['id']));
		
		return compact('action','siteid','id','years','selectedYear','condParam','arrMap');
	}
	
	public function teachersCustFieldYrView(){
		extract($this->teachersCustFieldYrInits());
		$this->Navigation->addCrumb('Academic');
		$customfield = $this->Components->load('CustomField',$arrMap);
		$data = array();
		if($id && $selectedYear && $siteid) $data = $customfield->getCustomFieldView($condParam);
		$displayEdit = true;
		if(count($data['dataFields']) == 0) {
			$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
			$displayEdit = false;
		}
		$this->set(compact('arrMap','selectedYear','years','action','id','displayEdit'));
		$this->set($data);
        $this->set('id',$id);
        $this->set('myview', 'teachersView');
		$this->render('/Elements/customfields/view');
	}
	
	public function teachersCustFieldYrEdit(){
		if ($this->request->is('post')) {
			extract($this->teachersCustFieldYrInits());
			$customfield = $this->Components->load('CustomField',$arrMap);
			$cond = array('institution_site_id' => $siteid, 
						  'teacher_id' => $id, 
						  'school_year_id' => $selectedYear);
			$customfield->saveCustomFields($this->request->data,$cond);
			$this->redirect(array('action' => 'teachersCustFieldYrView',$id,$selectedYear));
		}else{
			$this->teachersCustFieldYrView();
			$this->render('/Elements/customfields/edit');
		}
	}
	//TEACHER CUSTOM FIELD PER YEAR - ENDS - 
	
	
	
	
	
	//STUDENTS CUSTOM FIELD PER YEAR - STARTS - 
	private function studentsCustFieldYrInits(){
		$action = $this->action;
		$siteid = $this->institutionSiteId;
		$id = @$this->request->params['pass'][0];
		$years = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('student_id'=>$id,'institution_site_id'=>$siteid,'school_year_id'=>$selectedYear);
		$arrMap = array('CustomField'=>'StudentDetailsCustomField',
						'CustomFieldOption'=>'StudentDetailsCustomFieldOption',
						'CustomValue'=>'StudentDetailsCustomValue',
						'Year'=>'SchoolYear');
		
		$studentId = $this->params['pass'][0];
		$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
		$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
		$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $data['Student']['id']));
		return compact('action','siteid','id','years','selectedYear','condParam','arrMap');
	}
	
	public function studentsCustFieldYrView(){
		extract($this->studentsCustFieldYrInits());
		$this->Navigation->addCrumb('Academic');
		$customfield = $this->Components->load('CustomField',$arrMap);
		$data = array();
		if($id && $selectedYear && $siteid) $data = $customfield->getCustomFieldView($condParam);
		
		$displayEdit = true;
		if(count($data['dataFields']) == 0) {
			$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
			$displayEdit = false;
		}
		$this->set(compact('arrMap','selectedYear','years','action','id','displayEdit'));
		$this->set($data);
        $this->set('id',$id);
        $this->set('myview', 'studentsView');
		$this->render('/Elements/customfields/view');
	}
	
	public function studentsCustFieldYrEdit(){
		if ($this->request->is('post')) {
			extract($this->studentsCustFieldYrInits());
			$customfield = $this->Components->load('CustomField',$arrMap);
			$cond = array('institution_site_id' => $siteid, 
						  'student_id' => $id, 
						  'school_year_id' => $selectedYear);
			$customfield->saveCustomFields($this->request->data,$cond);
			$this->redirect(array('action' => 'studentsCustFieldYrView',$id,$selectedYear));
		}else{
			$this->studentsCustFieldYrView();
			$this->render('/Elements/customfields/edit');
		}
	}
	//STUDENTS CUSTOM FIELD PER YEAR - ENDS - 
	
	
	
	//STAFF CUSTOM FIELD PER YEAR - STARTS - 
	private function staffCustFieldYrInits(){
		$action = $this->action;
		$siteid = $this->institutionSiteId;
		$id = @$this->request->params['pass'][0];
		$years = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('staff_id'=>$id,'institution_site_id'=>$siteid,'school_year_id'=>$selectedYear);
		$arrMap = array('CustomField'=>'StaffDetailsCustomField',
						'CustomFieldOption'=>'StaffDetailsCustomFieldOption',
						'CustomValue'=>'StaffDetailsCustomValue',
						'Year'=>'SchoolYear');
		
		$staffId = $this->params['pass'][0];
		$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
		$name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
		$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $data['Staff']['id']));
		return compact('action','siteid','id','years','selectedYear','condParam','arrMap');
	}
	
	public function staffCustFieldYrView(){
		extract($this->staffCustFieldYrInits());
		$this->Navigation->addCrumb('Academic');
		$customfield = $this->Components->load('CustomField',$arrMap);
		$data = array();
		if($id && $selectedYear && $siteid) $data = $customfield->getCustomFieldView($condParam);
		$displayEdit = true;
		if(count($data['dataFields']) == 0) {
			$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
			$displayEdit = false;
		}
		$this->set(compact('arrMap','selectedYear','years','action','id','displayEdit'));
		$this->set($data);
        $this->set('id',$id);
        $this->set('myview', 'staffView');
		$this->render('/Elements/customfields/view');
	}
	
	public function staffCustFieldYrEdit(){
		if ($this->request->is('post')) {
			extract($this->staffCustFieldYrInits());
			$customfield = $this->Components->load('CustomField',$arrMap);
			$cond = array('institution_site_id' => $siteid, 
						  'staff_id' => $id, 
						  'school_year_id' => $selectedYear);
			$customfield->saveCustomFields($this->request->data,$cond);
			$this->redirect(array('action' => 'staffCustFieldYrView',$id,$selectedYear));
		}else{
			$this->staffCustFieldYrView();
			$this->render('/Elements/customfields/edit');
		}
	}

	//STAFF CUSTOM FIELD PER YEAR - ENDS -

    // STUDENT BEHAVIOUR PART
    public function studentsBehaviour(){
        extract($this->studentsCustFieldYrInits());
        $this->Navigation->addCrumb('List of Behaviour');
        
        $data = $this->StudentBehaviour->getBehaviourData($id, $this->institutionSiteId);
        
		if(empty($data)) {
			$this->Utility->alert($this->Utility->getMessage('STUDENT_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
		}
		
        $this->set('id', $id);
        $this->set('data', $data);
    }

    public function studentsBehaviourAdd() {
        if($this->request->is('get')) {
            $studentId = $this->params['pass'][0];
            $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
           	$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
            $this->Navigation->addCrumb('Add Behaviour');
            
            $yearOptions = array();
			$yearOptions = $this->SchoolYear->getYearList();
			
			$categoryOptions = array();
			$categoryOptions = $this->StudentBehaviourCategory->getCategory();
			$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
			$this->set('institution_site_id', $this->institutionSiteId);
			$this->set('institutionSiteOptions', $institutionSiteOptions);
			$this->set('id',$studentId);
           	$this->set('categoryOptions', $categoryOptions);
		    $this->set('yearOptions', $yearOptions);
        } else {
            $studentBehaviourData = $this->data['InstitutionSiteStudentBehaviour'];
			$studentBehaviourData['institution_site_id'] = $this->institutionSiteId;
			
            $this->StudentBehaviour->create();
			if(!$this->StudentBehaviour->save($studentBehaviourData)){
				// Validation Errors
				//debug($this->StudentBehaviour->validationErrors); 
				//die;
			} else {
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
			}
			
            $this->redirect(array('action' => 'studentsBehaviour', $studentBehaviourData['student_id']));
        }
    }

    public function studentsBehaviourView() {
		$studentBehaviourId = $this->params['pass'][0];
		$studentBehaviourObj = $this->StudentBehaviour->find('all',array('conditions'=>array('StudentBehaviour.id' => $studentBehaviourId)));
		
		if(!empty($studentBehaviourObj)) {
			$studentId = $studentBehaviourObj[0]['StudentBehaviour']['student_id'];
            $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
           	$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
			$this->Navigation->addCrumb('Behaviour Details');
			
			$yearOptions = array();
			$yearOptions = $this->SchoolYear->getYearList();
			$categoryOptions = array();
			$categoryOptions = $this->StudentBehaviourCategory->getCategory();

			$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));

			$this->set('institution_site_id', $this->institutionSiteId);
			$this->Session->write('StudentBehavourId', $studentBehaviourId);
			$this->set('categoryOptions', $categoryOptions);
			$this->set('institutionSiteOptions', $institutionSiteOptions);
		    $this->set('yearOptions', $yearOptions);
			$this->set('studentBehaviourObj', $studentBehaviourObj);
		} else {
			//$this->redirect(array('action' => 'classesList'));
		}
    }
	
	public function studentsBehaviourEdit() {
		if($this->request->is('get')) {
			$studentBehaviourId = $this->params['pass'][0];
			$studentBehaviourObj = $this->StudentBehaviour->find('all',array('conditions'=>array('StudentBehaviour.id' => $studentBehaviourId)));
			
			if(!empty($studentBehaviourObj)) {
				$studentId = $studentBehaviourObj[0]['StudentBehaviour']['student_id'];

				if($studentBehaviourObj[0]['StudentBehaviour']['institution_site_id']!=$this->institutionSiteId){
					$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
 					$this->redirect(array('action' => 'studentsBehaviourView', $studentBehaviourId));
				}
				$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
				$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
				$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
				$this->Navigation->addCrumb('Edit Behaviour Details');
				
				$categoryOptions = array();
				$categoryOptions = $this->StudentBehaviourCategory->getCategory();
				$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
				$this->set('institutionSiteOptions', $institutionSiteOptions);
				$this->set('categoryOptions', $categoryOptions);
				$this->set('studentBehaviourObj', $studentBehaviourObj);
			} else {
				//$this->redirect(array('action' => 'studentsBehaviour'));
			}
		 } else {
			$studentBehaviourData = $this->data['InstitutionSiteStudentBehaviour'];
			$studentBehaviourData['institution_site_id'] = $this->institutionSiteId;
			
            $this->StudentBehaviour->create();
			if(!$this->StudentBehaviour->save($studentBehaviourData)){
				// Validation Errors
				//debug($this->StudentBehaviour->validationErrors); 
				//die;
			} else {
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
			}
            
            $this->redirect(array('action' => 'studentsBehaviourView', $studentBehaviourData['id']));
		 }
	}
	
	public function studentsBehaviourDelete() {
		if($this->Session->check('InstitutionSiteStudentId') && $this->Session->check('StudentBehavourId')) {
			$id = $this->Session->read('StudentBehavourId');
			$studentId = $this->Session->read('InstitutionSiteStudentId');
			$name = $this->StudentBehaviour->field('title', array('StudentBehaviour.id' => $id));
			$institution_site_id = $this->StudentBehaviour->field('institution_site_id', array('StudentBehaviour.id' => $id));
			if($institution_site_id!=$this->institutionSiteId){
				$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
				$this->redirect(array('action' => 'studentsBehaviourView', $id));
			}
			$this->StudentBehaviour->delete($id);
			$this->Utility->alert($name . ' have been deleted successfully.');
			$this->redirect(array('action' => 'studentsBehaviour', $studentId));
		}
	}
	
	public function studentsBehaviourCheckName() {
		$this->autoRender = false;
		$title = trim($this->params->query['title']);
		
		if(strlen($title) == 0) {
			return $this->Utility->getMessage('SITE_STUDENT_BEHAVIOUR_EMPTY_TITLE');
		} 
		
		return 'true';
	}
    // END STUDENT BEHAVIOUR PART

    // CLASS ATTENDANCE PART
    public function classesAttendance(){
        $classId = $this->Session->read('InstitutionSiteClassId');
        $classObj = $this->InstitutionSiteClass->getClass($classId);

        if(!empty($classObj)) {
            $className = $classObj['InstitutionSiteClass']['name'];
            $this->Navigation->addCrumb($className, array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
            $this->Navigation->addCrumb('Attendance');
            $yearId = $classObj['InstitutionSiteClass']['school_year_id'];

            $grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
            $students = $this->InstitutionSiteClassGradeStudent->getStudentsAttendance($classId,array_keys($grades),$yearId);

            $this->set('classId', $classId);
            $this->set('selectedYear', $yearId);
            $this->set('grades', $grades);
            $this->set('students', $students);
        } else {
            $this->redirect(array('action' => 'classesList'));
        }
    }

    public function classesAttendanceEdit() {
        if($this->request->is('get')) {
            $classId = $this->Session->read('InstitutionSiteClassId');
            $classObj = $this->InstitutionSiteClass->getClass($classId);

            if(!empty($classObj)) {
                $className = $classObj['InstitutionSiteClass']['name'];
                $this->Navigation->addCrumb($className, array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
                $this->Navigation->addCrumb('Attendance');
                $yearId = $classObj['InstitutionSiteClass']['school_year_id'];
				
                $grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
                $students = $this->InstitutionSiteClassGradeStudent->getStudentsAttendance($classId,array_keys($grades),$yearId);

                $this->set('classId', $classId);
                $this->set('selectedYear', $yearId);
                $this->set('grades', $grades);
                $this->set('students', $students);
            } else {
                $this->redirect(array('action' => 'classesList'));
            }
        } else {
            $classId = $this->Session->read('InstitutionSiteClassId');
            $classObj = $this->InstitutionSiteClass->getClass($classId);
            $yearId = $classObj['InstitutionSiteClass']['school_year_id'];
            $classId = $this->request->data['ClassesAttendance']['institution_site_class_id'];
            $myArr = array();
            if(isset($this->request->data['Attendance'])){
                foreach($this->request->data['Attendance'] as $obj) {
                    $data = $obj;
                    if($obj['id']==0){
                        unset($data['id']);
                    }
                    $data['school_year_id'] = $yearId;
                    $data['institution_site_class_id'] = $classId;
                    $myArr[] = $data;
                }
                $this->StudentAttendance->saveAll($myArr);
                $this->Utility->alert($this->Utility->getMessage('SITE_STUDENT_ATTENDANCE_UPDATED'));
            }
            $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'classesAttendance', $yearId));
        }
    }
    // END CLASS ATTENDANCE PART
	
	// TEACHER ATTENDANCE PART
    public function teachersAttendance(){
		if($this->Session->check('InstitutionSiteTeachersId')){
			$teacherId = $this->Session->read('InstitutionSiteTeachersId');
			$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
			$name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
			$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
			$this->Navigation->addCrumb('Attendance');
			
			$id = $teacherId;
			$yearList = $this->SchoolYear->getYearList();
			$yearId = $this->getAvailableYearId($yearList);
			$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));
            
			$data = $this->TeacherAttendance->getAttendanceData($this->Session->read('InstitutionSiteTeachersId'),$yearId);
			$this->set('selectedYear', $yearId);
			$this->set('years', $yearList);
			$this->set('data', $data);
			$this->set('schoolDays', $schoolDays);
            $this->set('id', $teacherId);
		}
    }

    public function teachersAttendanceEdit() {
        if($this->request->is('get')) {
			if($this->Session->check('InstitutionSiteTeachersId')){
				$teacherId = $this->Session->read('InstitutionSiteTeachersId');
				$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
				$name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
				$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
				$this->Navigation->addCrumb('Edit Attendance');
				
				$yearList = $this->SchoolYear->getYearList();
				$yearId = $this->getAvailableYearId($yearList);
				$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));
				$data = $this->TeacherAttendance->getAttendanceData($this->Session->read('InstitutionSiteTeachersId'),$yearId);				
				
				$this->set('teacherid',$this->Session->read('InstitutionSiteTeachersId'));
				$this->set('institutionSiteId',$this->institutionSiteId);
				$this->set('selectedYear', $yearId);
				$this->set('years', $yearList);
				$this->set('schoolDays', $schoolDays);
				$this->set('data', $data);
			}
		} else {
			$schoolDayNo = $this->request->data['schoolDays'];
			$totalNo = $this->request->data['TeachersAttendance']['total_no_attend'] + $this->request->data['TeachersAttendance']['total_no_absence'];
			unset($this->request->data['schoolDays']);
			
			$data = $this->request->data['TeachersAttendance'];
			$yearId = $data['school_year_id'];
			
			if($schoolDayNo<$totalNo){
				$this->Utility->alert('Total no of days Attended and Total no of days Absent cannot exceed the no of School Days.', array('type' => 'error'));
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'teachersAttendanceEdit', $yearId));
			}else{
                $thisId = $this->TeacherAttendance->findID($this->Session->read('InstitutionSiteTeachersId'),$yearId);
                if($thisId!='')
                {
                    $data['id'] = $thisId;
                }
				$this->TeacherAttendance->save($data);
				$this->Utility->alert($this->Utility->getMessage('SITE_TEACHER_ATTENDANCE_UPDATED'));
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'teachersAttendance', $yearId));
			}
		}
    }
	// END TEACHER ATTENDANCE PART


	// TEACHER BEHAVIOUR PART
	 public function teachersBehaviour(){
        extract($this->teachersCustFieldYrInits());
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->TeacherBehaviour->getBehaviourData($id);
		if(empty($data)) {
			$this->Utility->alert($this->Utility->getMessage('TEACHER_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
		}
		
        $this->set('id', $id);
        $this->set('data', $data);
    }

    public function teachersBehaviourAdd() {
        if($this->request->is('get')) {
            $teacherId = $this->params['pass'][0];
            $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
            $name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
           	$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
            $this->Navigation->addCrumb('Add Behaviour');
            
            $yearOptions = array();
			$yearOptions = $this->SchoolYear->getYearList();
			
			$categoryOptions = array();
			$categoryOptions = $this->TeacherBehaviourCategory->getCategory();
			$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
			$this->set('institution_site_id', $this->institutionSiteId);
			$this->set('institutionSiteOptions', $institutionSiteOptions);
			$this->set('id',$teacherId);
           	$this->set('categoryOptions', $categoryOptions);
		    $this->set('yearOptions', $yearOptions);
        } else {
            $teacherBehaviourData = $this->data['InstitutionSiteTeacherBehaviour'];
			$teacherBehaviourData['institution_site_id'] = $this->institutionSiteId;
			
            $this->TeacherBehaviour->create();
			if(!$this->TeacherBehaviour->save($teacherBehaviourData)){
			} else {
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
			}
			
            $this->redirect(array('action' => 'teachersBehaviour', $teacherBehaviourData['teacher_id']));
        }
    }

    public function teachersBehaviourView() {
		$teacherBehaviourId = $this->params['pass'][0];
		$teacherBehaviourObj = $this->TeacherBehaviour->find('all',array('conditions'=>array('TeacherBehaviour.id' => $teacherBehaviourId)));
		
		if(!empty($teacherBehaviourObj)) {
			$teacherId = $teacherBehaviourObj[0]['TeacherBehaviour']['teacher_id'];
            $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
            $name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
           	$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
			$this->Navigation->addCrumb('Behaviour Details');
			
			$yearOptions = array();
			$yearOptions = $this->SchoolYear->getYearList();
			$categoryOptions = array();
			$categoryOptions = $this->TeacherBehaviourCategory->getCategory();
			$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
			$this->set('institution_site_id', $this->institutionSiteId);
			$this->set('institutionSiteOptions', $institutionSiteOptions);
			
			$this->Session->write('TeacherBehaviourId', $teacherBehaviourId);
			$this->set('categoryOptions', $categoryOptions);
		    $this->set('yearOptions', $yearOptions);
			$this->set('teacherBehaviourObj', $teacherBehaviourObj);
		} else {
			//$this->redirect(array('action' => 'classesList'));
		}
    }
	
	public function teachersBehaviourEdit() {
		if($this->request->is('get')) {
			$teacherBehaviourId = $this->params['pass'][0];
			$teacherBehaviourObj = $this->TeacherBehaviour->find('all',array('conditions'=>array('TeacherBehaviour.id' => $teacherBehaviourId)));
			
			if(!empty($teacherBehaviourObj)) {
				$teacherId = $teacherBehaviourObj[0]['TeacherBehaviour']['teacher_id'];
				if($teacherBehaviourObj[0]['TeacherBehaviour']['institution_site_id']!=$this->institutionSiteId){
					$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
 					$this->redirect(array('action' => 'teachersBehaviourView', $teacherBehaviourId));
				}
				$data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
				$name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
				$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
				$this->Navigation->addCrumb('Edit Behaviour Details');
				
				$categoryOptions = array();
				$categoryOptions = $this->TeacherBehaviourCategory->getCategory();
				$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
				$this->set('institutionSiteOptions', $institutionSiteOptions);
				
				$this->set('categoryOptions', $categoryOptions);
				$this->set('teacherBehaviourObj', $teacherBehaviourObj);
			} else {
				//$this->redirect(array('action' => 'studentsBehaviour'));
			}
		 } else {
			$teacherBehaviourData = $this->data['InstitutionSiteTeacherBehaviour'];
			$teacherBehaviourData['institution_site_id'] = $this->institutionSiteId;
			
            $this->TeacherBehaviour->create();
			if(!$this->TeacherBehaviour->save($teacherBehaviourData)){
			} else {
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
			}
            
            $this->redirect(array('action' => 'teachersBehaviourView', $teacherBehaviourData['id']));
		 }
	}
	
	public function teachersBehaviourDelete() {
		if($this->Session->check('InstitutionSiteTeachersId') && $this->Session->check('TeacherBehaviourId')) {
			$id = $this->Session->read('TeacherBehaviourId');
			$teacherId = $this->Session->read('InstitutionSiteTeachersId');
			$name = $this->TeacherBehaviour->field('title', array('TeacherBehaviour.id' => $id));
			$institution_site_id = $this->TeacherBehaviour->field('institution_site_id', array('TeacherBehaviour.id' => $id));
			if($institution_site_id!=$this->institutionSiteId){
				$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
				$this->redirect(array('action' => 'teachersBehaviourView', $id));
			}
			$this->TeacherBehaviour->delete($id);
			$this->Utility->alert($name . ' have been deleted successfully.');
			$this->redirect(array('action' => 'teachersBehaviour', $teacherId));
		}
	}
	
	public function teachersBehaviourCheckName() {
		$this->autoRender = false;
		$title = trim($this->params->query['title']);
		
		if(strlen($title) == 0) {
			return $this->Utility->getMessage('SITE_TEACHER_BEHAVIOUR_EMPTY_TITLE');
		} 
		
		return 'true';
	}
    // END TEACHER BEHAVIOUR PART

	
	// STAFF ATTENDANCE PART
    public function staffAttendance(){
		if($this->Session->check('InstitutionSiteStaffId')){
			$staffId = $this->Session->read('InstitutionSiteStaffId');
			$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
			$name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
			$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
			$this->Navigation->addCrumb('Attendance');
			
			$id = @$this->request->params['pass'][0];
			$yearList = $this->SchoolYear->getYearList();
			$yearId = $this->getAvailableYearId($yearList);
			$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));
			
			$data = $this->StaffAttendance->getAttendanceData($this->Session->read('InstitutionSiteStaffId'),isset($id)? $id:$yearId);							
			
			$this->set('selectedYear', $yearId);
			$this->set('years', $yearList);
			$this->set('data', $data);
			$this->set('schoolDays', $schoolDays);
            $this->set('id', $staffId);
		}
    }

    public function staffAttendanceEdit() {
        if($this->request->is('get')) {
			if($this->Session->check('InstitutionSiteStaffId')){
				$staffId = $this->Session->read('InstitutionSiteStaffId');
				$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
				$name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
				$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
				$this->Navigation->addCrumb('Edit Attendance');
				
				$yearList = $this->SchoolYear->getYearList();
				$yearId = $this->getAvailableYearId($yearList);
				$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));
				
				$data = $this->StaffAttendance->getAttendanceData($this->Session->read('InstitutionSiteStaffId'),$yearId);				
				
				$this->set('staffid',$this->Session->read('InstitutionSiteStaffId'));
				$this->set('institutionSiteId',$this->institutionSiteId);
				$this->set('selectedYear', $yearId);
				$this->set('years', $yearList);
				$this->set('schoolDays', $schoolDays);
				$this->set('data', $data);
			}
		} else {
			$schoolDayNo = $this->request->data['schoolDays'];
			$totalNo = $this->request->data['StaffAttendance']['total_no_attend'] + $this->request->data['StaffAttendance']['total_no_absence'];
			unset($this->request->data['schoolDays']);
			
			$data = $this->request->data['StaffAttendance'];
			$yearId = $data['school_year_id'];
			
			if($schoolDayNo<$totalNo){
				$this->Utility->alert('Total no of days Attended and Total no of days Absent cannot exceed the no of School Days.', array('type' => 'error'));
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'staffAttendanceEdit', $yearId));
			}else{
                $thisId = $this->StaffAttendance->findID($this->Session->read('InstitutionSiteStaffId'),$yearId);
                if($thisId!='')
                {
                    $data['id'] = $thisId;
                }
				$this->StaffAttendance->save($data);
				$this->Utility->alert($this->Utility->getMessage('SITE_STAFF_ATTENDANCE_UPDATED'));
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'staffAttendance', $yearId));
			}
		}
    }
	// END STAFF ATTENDANCE PART

	// STAFF BEHAVIOUR PART
	public function staffBehaviour(){
        extract($this->staffCustFieldYrInits());
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->StaffBehaviour->getBehaviourData($id);
		if(empty($data)) {
			$this->Utility->alert($this->Utility->getMessage('TEACHER_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
		}
		
        $this->set('id', $id);
        $this->set('data', $data);
    }

    public function staffBehaviourAdd() {
        if($this->request->is('get')) {
            $staffId = $this->params['pass'][0];
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
           	$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
            $this->Navigation->addCrumb('Add Behaviour');
            
            $yearOptions = array();
			$yearOptions = $this->SchoolYear->getYearList();
			
			$categoryOptions = array();
			$categoryOptions = $this->StaffBehaviourCategory->getCategory();
			$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
			$this->set('institution_site_id', $this->institutionSiteId);
			$this->set('institutionSiteOptions', $institutionSiteOptions);
			$this->set('id',$staffId);
           	$this->set('categoryOptions', $categoryOptions);
		    $this->set('yearOptions', $yearOptions);
        } else {
            $staffBehaviourData = $this->data['InstitutionSiteStaffBehaviour'];
			$staffBehaviourData['institution_site_id'] = $this->institutionSiteId;
			
            $this->StaffBehaviour->create();
			if(!$this->StaffBehaviour->save($staffBehaviourData)){
			} else {
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
			}
			
            $this->redirect(array('action' => 'staffBehaviour', $staffBehaviourData['staff_id']));
        }
    }

    public function staffBehaviourView() {
		$staffBehaviourId = $this->params['pass'][0];
		$staffBehaviourObj = $this->StaffBehaviour->find('all',array('conditions'=>array('StaffBehaviour.id' => $staffBehaviourId)));
		
		if(!empty($staffBehaviourObj)) {
			$staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
           	$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
			$this->Navigation->addCrumb('Behaviour Details');
		
			$yearOptions = array();
			$yearOptions = $this->SchoolYear->getYearList();
			$categoryOptions = array();
			$categoryOptions = $this->StaffBehaviourCategory->getCategory();
			$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
			$this->set('institution_site_id', $this->institutionSiteId);
			$this->set('institutionSiteOptions', $institutionSiteOptions);
			$this->Session->write('StaffBehaviourId', $staffBehaviourId);
			$this->set('categoryOptions', $categoryOptions);
		    $this->set('yearOptions', $yearOptions);
			$this->set('staffBehaviourObj', $staffBehaviourObj);
		} else {
			//$this->redirect(array('action' => 'classesList'));
		}
    }
	
	public function staffBehaviourEdit() {
		if($this->request->is('get')) {
			$staffBehaviourId = $this->params['pass'][0];
			$staffBehaviourObj = $this->StaffBehaviour->find('all',array('conditions'=>array('StaffBehaviour.id' => $staffBehaviourId)));
			
			if(!empty($staffBehaviourObj)) {
				$staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
				if($staffBehaviourObj[0]['StaffBehaviour']['institution_site_id']!=$this->institutionSiteId){
					$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
 					$this->redirect(array('action' => 'staffBehaviourView', $staffBehaviourId));
				}
				$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
				$name = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
				$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
				$this->Navigation->addCrumb('Edit Behaviour Details');
				
				$categoryOptions = array();
				$categoryOptions = $this->StaffBehaviourCategory->getCategory();
				$institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
			 	$this->set('institution_site_id', $this->institutionSiteId);
			 	$this->set('institutionSiteOptions', $institutionSiteOptions);
				$this->set('categoryOptions', $categoryOptions);
				$this->set('staffBehaviourObj', $staffBehaviourObj);
			} else {
				//$this->redirect(array('action' => 'studentsBehaviour'));
			}
		 } else {
			$staffBehaviourData = $this->data['InstitutionSiteStaffBehaviour'];
			$staffBehaviourData['institution_site_id'] = $this->institutionSiteId;
			
            $this->StaffBehaviour->create();
			if(!$this->StaffBehaviour->save($staffBehaviourData)){
			} else {
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
			}
            
            $this->redirect(array('action' => 'staffBehaviourView', $staffBehaviourData['id']));
		 }
	}
	
	public function staffBehaviourDelete() {
		if($this->Session->check('InstitutionSiteStaffId') && $this->Session->check('StaffBehaviourId')) {
			$id = $this->Session->read('StaffBehaviourId');
			$staffId = $this->Session->read('InstitutionSiteStaffId');
			$name = $this->StaffBehaviour->field('title', array('StaffBehaviour.id' => $id));
			$institution_site_id = $this->StaffBehaviour->field('institution_site_id', array('StaffBehaviour.id' => $id));
			if($institution_site_id!=$this->institutionSiteId){
				$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
				$this->redirect(array('action' => 'staffsBehaviourView', $id));
			}
			$this->StaffBehaviour->delete($id);
			$this->Utility->alert($name . ' have been deleted successfully.');
			$this->redirect(array('action' => 'staffBehaviour', $staffId));
		}
	}
	
	public function staffBehaviourCheckName() {
		$this->autoRender = false;
		$title = trim($this->params->query['title']);
		
		if(strlen($title) == 0) {
			return $this->Utility->getMessage('SITE_STUDENT_BEHAVIOUR_EMPTY_TITLE');
		} 
		
		return 'true';
	}
    // END STAFF BEHAVIOUR PART
	
	private function getAvailableYearId($yearList) {
		$yearId = 0;
		if(isset($this->params['pass'][0])) {
			$yearId = $this->params['pass'][0];
			if(!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		} else {
			$yearId = key($yearList);
		}
		return $yearId;
	}
        
        public function getSiteProfile(){
            
        }
        
        
        public function siteProfile($id){
            
            $levels = $this->AreaLevel->find('list',array('recursive'=>0));
            $adminarealevels = $this->AreaEducationLevel->find('list',array('recursive'=>0));
            $data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));

            $areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
            $areaLevel = array_reverse($areaLevel);

            $adminarea = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_education_id'],array('AreaEducation','AreaEducationLevel'));
            $adminarea = array_reverse($adminarea);
            
            $this->set('data', $data);
            $this->set('levels',$levels);
            $this->set('adminarealevel',$adminarealevels);

            $this->set('arealevel',$areaLevel);
            $this->set('adminarea',$adminarea);
        }
    
}