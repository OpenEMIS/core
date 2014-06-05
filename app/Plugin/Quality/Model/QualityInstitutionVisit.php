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

class QualityInstitutionVisit extends QualityAppModel {

    //public $useTable = 'rubrics';
    public $actsAs = array('ControllerAction', 'DatePicker' => array('date'));
    public $belongsTo = array(
        //'Student',
        //'RubricsTemplateHeader',
		'Staff.Staff',
		'SchoolYear',
		'EducationGrade',
		'InstitutionSiteClass',
		'QualityVisitType' => array(
            'foreignKey' => 'quality_type_id'
		),
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
  //  public $hasMany = array('QualityInstitutionVisitAttachment');
    public $validate = array(
       /* 'education_grade_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Grade.'
            )
        ),
        'institution_site_class_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Class.'
            )
        ),
        'staff_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //   'required' => true,
                'message' => 'Please select a valid staff.'
            )
        ),*/
        'quality_type_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                // 'required' => true,
                'message' => 'Please select a valid Type.'
            )
        ),
        'comment' => array(
            'ruleRequired' => array(
                'rule' => 'checkCommentLength',//array('maxLength', 1),
                'message' => 'Maximum 150 words per comment.'
            )
        )
    );

//    public $statusOptions = array('Disabled', 'Enabled');
    public function checkDropdownData($check) {
        $value = array_values($check);
        $value = $value[0];

        return !empty($value);
    }
    
    public function checkCommentLength($data){
        if(str_word_count($data['comment']) > 150) {
            return false;
        }
        
        return true;
    }

	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'date'),
                array('field' => 'name', 'model' => 'SchoolYear'),
				array('field' => 'name', 'model' => 'EducationGrade', 'labelKey' => 'general.grade'),
                array('field' => 'name', 'model' => 'InstitutionSiteClass', 'labelKey' => 'general.class'),
                array('field' => 'staff', 'model' => 'Staff', 'format'=>'name'),
                array('field' => 'evaluator', 'model' => 'CreatedUser', 'format'=>'name'),
				array('field' => 'name', 'model' => 'QualityVisitType', 'labelKey' => 'general.type'),
				array('field' => 'comment'),
				array('field' => 'file_name', 'model' => 'QualityInstitutionVisitAttachment', 'labelKey' => 'general.attachments', 'multi_records' => true, 'type' => 'files', 'url' => array('action' => 'qualityVisitAttachmentDownload')),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
    public function qualityVisit($controller, $params) {
        $institutionSiteId = $controller->Session->read('InstitutionSiteId');
        $controller->Navigation->addCrumb('Visit');
        $controller->set('subheader', 'Visit');
      //  $controller->set('modelName', $this->name);

		$this->unbindModel(array('belongsTo' => array('ModifiedUser','CreatedUser', 'SchoolYear', 'QualityVisitType')));
		$options['fields'] = array(
			'QualityInstitutionVisit.id',
			'QualityInstitutionVisit.date',
			'Staff.first_name',
			'Staff.middle_name',
			'Staff.last_name',
			'EducationGrade.name',
			'InstitutionSiteClass.name',
			
		);
		$options['conditions'] = array('InstitutionSiteClass.institution_site_id' => $institutionSiteId);
		$options['order'] = array('QualityInstitutionVisit.date');
        $data = $this->find('all', $options);

        $controller->set('data', $data);
    }

    public function qualityVisitView($controller, $params) {
        $controller->Navigation->addCrumb('Visit');
		$header = __('Visit');
      //  $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));

        if (empty($data)) {
            $controller->redirect(array('action' => 'qualityVisit'));
        }
		$attachments = $controller->FileUploader->getList(array('conditions' => array('QualityInstitutionVisitAttachment.quality_institution_visit_id'=>$id)));
		
		$data['multi_records'] = $attachments;
		
        $controller->Session->write('QualityVisit.id', $id);
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('data', 'header', 'fields', 'id'));
    }

    public function qualityVisitAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Visit');
        $controller->set('subheader', 'Add Visit');

        $this->_setupStatusForm($controller, $params, 'add');
    }

    public function qualityVisitEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Visit');
        $controller->set('subheader', 'Edit Visit');

        $this->_setupStatusForm($controller, $params, 'edit');
        $this->render = 'add';
    }

    private function _setupStatusForm($controller, $params, $type) {
        $institutionSiteId = $controller->Session->read('InstitutionSiteId');
        $userData = $controller->Session->read('Auth.User');

        $evaluatorName = $userData['first_name'] . ' ' . $userData['last_name'];
        if ($type == 'add') {
            $paramsLocateCounter = 0;
        } else {
            $paramsLocateCounter = 1;
            $selectedId = $params['pass'][0];

            $data = $this->find('first', array('conditions' => array('QualityInstitutionVisit.id' => $selectedId)));
			$attachments = $controller->FileUploader->getList(array('conditions' => array('QualityInstitutionVisitAttachment.quality_institution_visit_id'=>$selectedId)));
            $controller->set('attachments',$attachments);
        }


        if ($controller->request->is('get')) {
            if ($type == 'edit') {
                if (!empty($params['pass'][0])) {
                    //  $selectedId = $params['pass'][0];
                    //   $data = $this->find('first', array('conditions' => array('QualityInstitutionVisit.id' => $selectedId)));

                    if (!empty($data)) {//pr($data);
                        $controller->request->data = $data;
                        $selectedstaffId = $data[$this->name]['staff_id'];
                        $selectedYearId = $data[$this->name]['school_year_id'];
                        $selectedGradeId = $data[$this->name]['education_grade_id'];
                        $selectedClassId = $data[$this->name]['institution_site_class_id'];
                        $selectedVisitTypeId = $data[$this->name]['quality_type_id'];
                        $institutionSiteId = $data[$this->name]['institution_site_id'];
                        $selectedDate = $data[$this->name]['date'];

                        $evaluatorName = trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']);
                       

                    }
                } else {
                    //  return $controller->redirect(array('action' => 'index'));
                }
            }
        } else {
            $postData = $controller->request->data; //pr($postData);

            if (!empty($postData)) {
                $this->set($postData['QualityInstitutionVisit']);
                if ($this->validates()) {
                    if ($this->save($postData['QualityInstitutionVisit'])) {
						//$id = $this->getInsertID();
						$postFileData = $controller->request->data[$this->alias]['files'];
						
						//$controller->FileUploader->additionData = array('staff_leave_id' => $id);
						$controller->FileUploader->additionData = array('quality_institution_visit_id' => $this->id);
						$controller->FileUploader->uploadFile(NULL, $postFileData);

                        if ($controller->FileUploader->success) {
                            $controller->Message->alert('general.add.success');
                            return $controller->redirect(array('action' => 'qualityVisitView', $this->id));
                        }
                    } else {
                        if ($type == 'add') {
                            $controller->Utility->alert($controller->Utility->getMessage('ADD_ERROR'), array('type' => 'error'));
                        } else {
                            $controller->Utility->alert($controller->Utility->getMessage('UPDATE_ERROR'), array('type' => 'error'));
                        }
                    }
                }
            } else {
                if ($type == 'add') {
                    $controller->Utility->alert($controller->Utility->getMessage('ADD_ERROR'), array('type' => 'error'));
                } else {
                    $controller->Utility->alert($controller->Utility->getMessage('UPDATE_ERROR'), array('type' => 'error'));
                }
                return $controller->redirect(array('action' => 'qualityVisitView', $this->id));
            }

            //  pr($postData);
        }
        $selectedDate = !empty($selectedDate) ? $selectedDate : '';
        $selectedDate = !empty($params['pass'][0 + $paramsLocateCounter]) ? $params['pass'][0 + $paramsLocateCounter] : $selectedDate;

        $SchoolYear = ClassRegistry::init('SchoolYear');
        $schoolYearOptions = $SchoolYear->getYearList();

        if (empty($schoolYearOptions)) {
            $controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'));
            return $controller->redirect(array('action' => 'qualityVisit'));
        }
        $selectedYearId = !empty($selectedYearId) ? $selectedYearId : key($schoolYearOptions);
        $selectedYearId = !empty($params['pass'][1 + $paramsLocateCounter]) ? $params['pass'][1 + $paramsLocateCounter] : $selectedYearId;

        $gradesOptions = array();
        $InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
        $institutionProgramData = $InstitutionSiteProgramme->getProgrammeList($institutionSiteId, $selectedYearId);

        foreach ($institutionProgramData as $itemData) {
            if (array_key_exists('education_grades', $itemData)) {
                // $gradesOptions = $itemData['education_grades'];
                //pr($itemData['education_grades']);
                foreach ($itemData['education_grades'] as $key => $gradeName) {
                    $gradesOptions[$key] = $gradeName;
                }

                //$gradesOptions = array_merge($gradesOptions, $itemData['education_grades']);
            }
        }
        $classOptions = array();
        if (!empty($gradesOptions)) {
            $selectedGradeId = !empty($selectedGradeId) ? $selectedGradeId : key($gradesOptions);
            $selectedGradeId = !empty($params['pass'][2 + $paramsLocateCounter]) ? $params['pass'][2 + $paramsLocateCounter] : $selectedGradeId;
            $InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
            $classOptions = $InstitutionSiteClass->getClassOptions($selectedYearId, $institutionSiteId, $selectedGradeId);
        }
        $selectedClassId = !empty($selectedClassId) ? $selectedClassId : key($classOptions);
        $selectedClassId = !empty($params['pass'][3 + $paramsLocateCounter]) ? $params['pass'][3 + $paramsLocateCounter] : $selectedClassId;

        $staffOptions = array();
        if (!empty($classOptions)) {
            $InstitutionSiteClassStaff = ClassRegistry::init('InstitutionSiteClassStaff');
            $staffOptions = $InstitutionSiteClassStaff->getstaffs($selectedClassId, 'list');
            $selectedstaffId = !empty($selectedstaffId) ? $selectedstaffId : key($staffOptions);
            $selectedstaffId = !empty($params['pass'][4 + $paramsLocateCounter]) ? $params['pass'][4 + $paramsLocateCounter] : $selectedstaffId;
        }

        $QualityVisitType = ClassRegistry::init('QualityVisitType');
        $visitOptions = $QualityVisitType->find('list');
        $selectedVisitTypeId = !empty($selectedVisitTypeId) ? $selectedVisitTypeId : key($visitOptions);
        $selectedVisitTypeId = !empty($params['pass'][5 + $paramsLocateCounter]) ? $params['pass'][5 + $paramsLocateCounter] : $selectedVisitTypeId;

        $controller->set('schoolYearOptions', $schoolYearOptions);
        $controller->set('gradesOptions', $this->checkArrayEmpty($gradesOptions));
        $controller->set('classOptions', $this->checkArrayEmpty($classOptions));
        $controller->set('staffOptions', $this->checkArrayEmpty($staffOptions));
        $controller->set('visitOptions', $this->checkArrayEmpty($visitOptions));
        $controller->set('type', $type);
        $controller->set('modelName', $this->name);

        if (!empty($selectedDate)) {
            $controller->request->data[$this->name]['date'] = $selectedDate;
        }

        $controller->request->data[$this->name]['school_year_id'] = $selectedYearId;
        $controller->request->data[$this->name]['institution_site_id'] = empty($controller->request->data[$this->name]['institution_site_id']) ? $institutionSiteId : $controller->request->data[$this->name]['institution_site_id'];
        $controller->request->data[$this->name]['education_grade_id'] = empty($selectedGradeId) ? 0 : $selectedGradeId;
        $controller->request->data[$this->name]['institution_site_class_id'] = empty($selectedClassId) ? 0 : $selectedClassId;
        $controller->request->data[$this->name]['staff_id'] = empty($selectedstaffId) ? 0 : $selectedstaffId;
        $controller->request->data[$this->name]['quality_type_id'] = empty($selectedVisitTypeId) ? 0 : $selectedVisitTypeId;
        $controller->request->data[$this->name]['evaluator'] = $evaluatorName;
    }

    public function qualityVisitAttachmentDownload($controller, $params) {
        $this->render = false;
        $id = empty($params['pass'][0]) ? NULL : $params['pass'][0];

        if (!empty($id)) {
            $_modelName = 'QualityInstitutionVisitAttachment';
            $controller->FileUploader->fileModel = $this->name;
            $controller->FileUploader->dbPrefix = 'file';
            $controller->FileUploader->fileModel = $_modelName; //$this->name;
            $controller->FileUploader->downloadFile($id);
        }
    }

    public function qualityVisitDelete($controller, $params) {
        if ($controller->Session->check('QualityVisit.id')) {
            $id = $controller->Session->read('QualityVisit.id');

            $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));


            $name = 'Entry'; //$data[$this->name]['name'];

            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->Session->delete('QualityVisit.id');
            $controller->redirect(array('action' => 'qualityVisit'));
        }
    }

    private function _checkMultiAttachmentsExist($filesData) {
        $attachmentExisit = false;

        foreach ($filesData as $file) {
            pr($file);
            if (!empty($file['tmp_name'])) {
                $attachmentExisit = true;
                break;
            }
        }
        die;
        return $attachmentExisit;
    }

    public function qualityVisitAjaxAddAttachment($controller, $params) {
        if ($controller->request->is('ajax')) {
            
        }
    }
	

    public function qualityVisitAjaxRemoveAttachment($controller, $params) {
		$this->render = false;
		if ($controller->request->is('post')) {
			$result = array('alertOpt' => array());
			$controller->Utility->setAjaxResult('alert', $result);
			$id = $params->data['id'];
			$QualityInstitutionVisitAttachment = ClassRegistry::init('QualityInstitutionVisitAttachment');

			if ($QualityInstitutionVisitAttachment->delete($id)) {
				$msgData = $controller->Message->get('FileUplaod.success.delete');
				$result['alertOpt']['text'] = $msgData['msg']; // __('File is deleted successfully.');
			} else {
				$msgData = $controller->Message->get('FileUplaod.error.delete');
				$result['alertType'] = $controller->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = $msgData['msg']; //__('Error occurred while deleting file.');
			}

			return json_encode($result);
		}
	}

	public function qualityVisitAjaxAddField($controller, $params) {
		$this->render =false;
		
		$fileId = $controller->request->data['size'];
		$multiple = true;
		$controller->set(compact('fileId', 'multiple'));
		$controller->render('/Elements/templates/file_upload_field');
	}
	
	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
		$controller->FileUploader->fileVar = 'files';
		$controller->FileUploader->fileModel = 'QualityInstitutionVisitAttachment';
		$controller->FileUploader->allowEmptyUpload = true;
		$controller->FileUploader->additionalFileType();
    }

}
