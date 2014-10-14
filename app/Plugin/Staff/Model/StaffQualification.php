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

class StaffQualification extends StaffAppModel {
	public $actsAs = array('ControllerAction');
	public $belongsTo = array(
		'QualificationLevel' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'qualification_level_id'
		),
		'QualificationInstitution',
		'QualificationSpecialisation',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	public $validate = array(
		'qualification_title' => array(
			'required' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Qualification Title'
			)
		),
		'graduate_year' => array(
			'required' => array(
				'rule' => 'numeric',
				'required' => true,
				'message' => 'Please enter a valid Graduate Year'
			)
		),
		'qualification_level_id' => array(
			'required' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Qualification Level'
			)
		),
		'qualification_specialisation_id' => array(
			'required' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Major/Specialisation'
			)
		),
		'qualification_institution_name' => array(
			'validHiddenId' => array(
				'rule' => array('checkQualificationInstitutionId'),
				'message' => 'Please enter a valid Institution'
			)
		)
	);

	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->FileUploader->fileModel = 'StaffQualification';
		$controller->FileUploader->additionalFileType();
	}
	
	public function checkQualificationInstitutionId($qualificationInstitutionName){
		//if(!empty($this->data['StaffQualification']['qualification_institution_name']) || !empty($this->data['StaffQualification']['qualification_institution_id'])){
		if(!empty($this->data['StaffQualification']['qualification_institution_name']) ){
			return true;
		}else{
			return false;
		}
	}
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name', 'model' => 'QualificationLevel'),
				array('field' => 'qualification_institution_name'),
				array('field' => 'qualification_institution_country'),
				array('field' => 'qualification_title'),
				array('field' => 'name', 'model' => 'QualificationSpecialisation'),
				array('field' => 'graduate_year'),
				array('field' => 'document_no'),
				array('field' => 'gpa'),
				array('field' => 'file_name', 'type' => 'file', 'url' => array('action' => 'qualificationsAttachmentsDownloads')),
				
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function qualifications($controller, $params) {
		$controller->Navigation->addCrumb('Qualifications');
		$header = __('Qualifications');
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$fields = array(
			'StaffQualification.id',
			'StaffQualification.document_no',
			'StaffQualification.graduate_year',
			'StaffQualification.qualification_title',
			'QualificationInstitution.name',
			'QualificationLevel.name',
		);
		$data = $this->findAllByStaffId($controller->Session->read('Staff.id'), $fields);
		$controller->set(compact('header', 'data'));
	}

	public function qualificationsAdd($controller, $params) {
		$controller->Navigation->addCrumb('Edit Qualification');
		$controller->set('header', __('Edit Qualification'));
		$this->setup_add_edit_form($controller, $params, 'add');
	}

	public function qualificationsView($controller, $params){
		$controller->Navigation->addCrumb('Qualification Details');
		$header = __('Qualification Details');
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->findById($id);
		$data['StaffQualification']['qualification_institution_name'] = $data['QualificationInstitution']['name'];
		
		if(empty($data)){
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action'=>'qualifications'));
		}
		
		$controller->Session->write('StaffQualification.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'header', 'fields', 'id'));
	}
	
	public function qualificationsEdit($controller, $params)  {
		$controller->Navigation->addCrumb('Edit Qualification');
		$controller->set('header',__('Edit Qualification'));
		$this->setup_add_edit_form($controller, $params, 'edit');
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params, $type){
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$staffQualificationObj = $this->findById($id);
		
		if ($controller->request->is('get')) {

			if (!empty($staffQualificationObj)) {
				$staffQualificationObj['StaffQualification']['qualification_institution_name'] = $staffQualificationObj['QualificationInstitution']['name'];
				$controller->request->data = $staffQualificationObj;
			} else {
				//$this->redirect(array('action' => 'studentsBehaviour'));
			}
		} else {
			$staffQualificationData = $controller->request->data['StaffQualification'];
			$staffQualificationData['staff_id'] = $controller->Session->read('Staff.id');
			unset($staffQualificationData['file']);
			
			$postFileData = $controller->request->data[$this->alias]['file'];
			$this->set($staffQualificationData);

			if ($this->validates()) {
				$exixtingQuaInstName = '';
				if(!empty($staffQualificationData['qualification_institution_id'])){
					$exixtingQuaInstObj = $this->QualificationInstitution->findById($staffQualificationData['qualification_institution_id']);
					$exixtingQuaInstName = $exixtingQuaInstObj['QualificationInstitution']['name'];
				}
				
				if (empty($staffQualificationData['qualification_institution_id']) || $staffQualificationData['qualification_institution_name'] != $exixtingQuaInstName) {
					$data = array(
						'QualificationInstitution' =>
						array(
							'name' => $staffQualificationData['qualification_institution_name'],
							'order' => 0,
							'visible' => 1,
							'created_user_id' => $controller->Auth->user('id'),
							'created' => date('Y-m-d h:i:s')
						)
					);
					$this->QualificationInstitution->save($data);
					$qualificationInstitutionId = $this->QualificationInstitution->getInsertID();
					$staffQualificationData['qualification_institution_id'] = $qualificationInstitutionId;
				}
				unset($staffQualificationData['qualification_institution_name']);
				
				if(empty($postFileData['tmp_name'])){
					if($this->save($staffQualificationData)){
						$controller->Message->alert('general.' . $type . '.success');
						if(!empty($id)){
							return $controller->redirect(array('action' => 'qualificationsView', $id));
						}else{
							return $controller->redirect(array('action' => 'qualifications'));
						}
					}else{
						$controller->Message->alert('general.' . $type . '.failed');
					}
				}else{
					$controller->FileUploader->additionData = $staffQualificationData;
					$controller->FileUploader->uploadFile();
					if ($controller->FileUploader->success) {
						$controller->Message->alert('general.' . $type . '.success');
						if(!empty($id)){
							return $controller->redirect(array('action' => 'qualificationsView', $id));
						}else{
							return $controller->redirect(array('action' => 'qualifications'));
						}
					}else{
						$controller->Message->alert('general.' . $type . '.failed');
					}
				}
			}
		}
		
		$levelOptions = $this->QualificationLevel->getList();
		$specializationOptions = $this->QualificationSpecialisation->getOptions();

		$controller->set(compact('levelOptions', 'specializationOptions', 'id', 'staffQualificationObj'));
	}

	public function qualificationsDelete($controller, $params) {
		return $this->remove($controller, 'qualifications');
	}
	
	public function qualificationsAttachmentsDownloads($controller, $params) {
		$id = $params['pass'][0];
		$controller->FileUploader->downloadFile($id);
	}
	
	public function qualificationsAjaxFindInstitution($controller, $params) {
		if ($controller->request->is('ajax')) {
			$this->render = false;
			$search = $params->query['term'];
			$data = $this->QualificationInstitution->autocomplete($search);

			return json_encode($data);
		}
	}
}
