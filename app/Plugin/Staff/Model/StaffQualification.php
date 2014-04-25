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

App::uses('UtilityComponent', 'Component');

class StaffQualification extends StaffAppModel {
    public $useTable = "staff_qualifications";
	public $actsAs = array('ControllerAction');
    public $belongsTo = array(
		'QualificationLevel',
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
    );

  /*  public function getData($id) {
        $utility = new UtilityComponent(new ComponentCollection);
        $options['joins'] = array(
            array('table' => 'qualification_institutions',
                'alias' => 'QualificationInstitution',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualificationInstitution.id = StaffQualification.qualification_institution_id'
                )
            ),
            array('table' => 'qualification_specialisations',
                'alias' => 'QualificationSpecialisation',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualificationSpecialisation.id = StaffQualification.qualification_specialisation_id'
                )
            ),
            array('table' => 'qualification_levels',
                'alias' => 'QualificationLevel',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualificationLevel.id = StaffQualification.qualification_level_id'
                )
            ),
        );

        $options['fields'] = array(
            'StaffQualification.id',
            'StaffQualification.document_no',
            'StaffQualification.graduate_year',
            'StaffQualification.gpa',
            'StaffQualification.qualification_title',
            'StaffQualification.qualification_institution_country',
            'StaffQualification.qualification_institution_id as institute_id',
            'QualificationInstitution.name as institute',
            'StaffQualification.qualification_level_id as level_id',
            'QualificationLevel.name as level',
            'StaffQualification.qualification_specialisation_id as specialisation_id',
            'QualificationSpecialisation.name as specialisation'
        );

        $options['conditions'] = array(
            'StaffQualification.staff_id' => $id,
        );

        $options['order'] = array('StaffQualification.graduate_year DESC');

        $list = $this->find('all', $options);
        $list = $utility->formatResult($list);

        return $list;
    }*/

	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
		$controller->FileUploader->fileModel = 'StaffQualification';
		$controller->FileUploader->additionalFileType();
    }
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'name', 'model' => 'QualificationLevel'),
				array('field' => 'name', 'model' => 'QualificationInstitution'),
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
		$data = $this->findAllByStaffId($controller->staffId, $fields);
		
        $controller->UserSession->readStatusSession($controller->request->action);
        $controller->set(compact('header', 'data'));
    }

    public function qualificationsAdd($controller, $params) {
		$controller->Navigation->addCrumb('Edit Qualification');
		$controller->set('header', __('Edit Qualification'));
		$this->setup_add_edit_form($controller, $params);
       /* if ($this->request->is('post')) {
            $this->StaffQualification->create();
            $this->request->data['StaffQualification']['staff_id'] = $this->staffId;

            $staffQualificationData = $this->data['StaffQualification'];

            $this->StaffQualification->set($staffQualificationData);

            if ($this->StaffQualification->validates()) {
                if (empty($staffQualificationData['qualification_institution_id'])) {
                    $data = array(
                        'QualificationInstitution' =>
                        array(
                            'name' => $staffQualificationData['qualification_institution'],
                            'order' => 0,
                            'visible' => 1,
                            'created_user_id' => $this->Auth->user('id'),
                            'created' => date('Y-m-d h:i:s')
                        )
                    );
                    $this->QualificationInstitution->save($data);
                    $qualificationInstitutionId = $this->QualificationInstitution->getInsertID();
                    $staffQualificationData['qualification_institution_id'] = $qualificationInstitutionId;
                }

                $this->StaffQualification->save($staffQualificationData);

                $staffQualificationId = $this->StaffQualification->getInsertID();

                $arrMap = array('model' => 'Staff.StaffQualification');
                $Q = $this->Components->load('FileAttachment', $arrMap);
                $staffQualificationData['id'] = $staffQualificationId;
                $errors = $Q->save($staffQualificationData, $_FILES);

                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'qualifications'));
            }
        }

        $levels = $this->QualificationLevel->getOptions();
        $specializations = $this->QualificationSpecialisation->getOptions();
        $institutes = $this->QualificationInstitution->getOptions();

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('specializations', $specializations);
        $this->set('levels', $levels);
        $this->set('institutes', $institutes);*/
    }

	public function qualificationsView($controller, $params){
		$controller->Navigation->addCrumb('Qualification Details');
		$header = __('Qualification Details');
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->findById($id);//('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action'=>'membership'));
		}
		
		$controller->Session->write('StaffQualificationId', $id);
		$fields = $this->getDisplayFields($controller);
        $controller->set(compact('data', 'header', 'fields', 'id'));
	}
	
    public function qualificationsEdit($controller, $params)  {
		$controller->Navigation->addCrumb('Edit Qualification');
		$controller->set('header',__('Edit Qualification'));
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
        if ($controller->request->is('get')) {
            
            $staffQualificationObj = $this->findById($id);//('first', array('conditions' => array('StaffQualification.id' => $staffQualificationId)));

            if (!empty($staffQualificationObj)) {
                //$staffQualificationObj['StaffQualification']['qualification_institution'] = $institutes[$staffQualificationObj['StaffQualification']['qualification_institution_id']];
                $controller->request->data = $staffQualificationObj;
               // $this->set('id', $staffQualificationId);
            } else {
                //$this->redirect(array('action' => 'studentsBehaviour'));
            }
        } else {
            $staffQualificationData = $controller->request->data['StaffQualification'];
            $staffQualificationData['staff_id'] = $controller->staffId;
			unset($staffQualificationData['file']);
			
			$postFileData = $controller->request->data[$this->alias]['file'];
            $this->set($staffQualificationData);

            if ($this->validates()) {
                if (empty($staffQualificationData['qualification_institution_id'])) {
                    $data = array(
                        'QualificationInstitution' =>
                        array(
                            'name' => $staffQualificationData['qualification_institution'],
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
                if($this->save($staffQualificationData)){
					if(!empty($postFileData['tmp_name'])){ 
						$controller->FileUploader->uploadFile();
						if ($controller->FileUploader->success) {
							$controller->Message->alert('general.add.success');
						}
					}
					else{
						$updateFileData = array('id' => $id, 'file_name' => null, 'file_content' => null);
						$this->id = $id;
						$this->saveField('file_name', NULL);
						$this->saveField('file_content', NULL);
						$controller->Message->alert('general.add.success');
					}
					return $controller->redirect(array('action' => 'qualificationsView', $id));
				}
            }
        }
		
		$levelOptions = $this->QualificationLevel->getOptions();
        $specializationOptions = $this->QualificationSpecialisation->getOptions();

		$controller->set(compact('levelOptions', 'specializationOptions', 'id'));
    }

    public function qualificationsDelete($controller, $params) {
        if ($controller->Session->check('StaffId') && $controller->Session->check('StaffQualificationId')) {
            $id = $controller->Session->read('StaffQualificationId');
            if($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
			$controller->Session->delete('StaffQualificationId');
            return $controller->redirect(array('action' => 'qualifications'));
        }
    }
/*
    public function qualificationAttachmentsDelete($id) {
        $this->autoRender = false;

        $result = array('alertOpt' => array());
        $this->Utility->setAjaxResult('alert', $result);

        $staffQualification = $this->StaffQualification->findById($id);
        $name = $staffQualification['StaffQualification']['qualification_title'];
        $staffQualification['StaffQualification']['file_name'] = null;
        $staffQualification['StaffQualification']['file_content'] = null;

        if ($this->StaffQualification->save($staffQualification)) {
            //$this->Utility->alert($name . ' have been deleted successfully.');
        } else {
            //$this->Utility->alert('Error occurred while deleting file.');
        }

        $this->redirect(array('action' => 'qualificationsEdit', $id));
    }
*/
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
