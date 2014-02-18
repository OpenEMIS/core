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
    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        //'Student',
        //'RubricsTemplateHeader',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    //public $hasMany = array('RubricsTemplateColumnInfo');

    public $validate = array(
        'education_grade_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Grade.'
            )
        ),
        'institution_site_classes_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Class.'
            )
        ),
        'institution_site_teacher_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //   'required' => true,
                'message' => 'Please select a valid Teacher.'
            )
        ),
        'quality_type_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                // 'required' => true,
                'message' => 'Please select a valid Type.'
            )
        )
    );

//    public $statusOptions = array('Disabled', 'Enabled');
    public function checkDropdownData($check) {
        $value = array_values($check);
        $value = $value[0];

        return !empty($value);
    }

    public function qualityVisit($controller, $params) {
        $institutionSiteId = $controller->Session->read('InstitutionSiteId');
        $controller->Navigation->addCrumb('Quality - Visit');
        $controller->set('subheader', 'Quality - Visit');
        $controller->set('modelName', $this->name);

        $this->recursive = -1;
        $data = $this->find('all', array('conditions' => array('institution_site_id' => $institutionSiteId)));

        $controller->set('data', $data);

        $InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
        $gradeOptions = $InstitutionSiteClassGrade->getGradesByInstitutionSiteId($institutionSiteId);

        $InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
        $classOptions = $InstitutionSiteClass->getClassListByInstitution($institutionSiteId);

        $InstitutionSiteClassTeacher = ClassRegistry::init('InstitutionSiteClassTeacher');
        $teacherOptions = $InstitutionSiteClassTeacher->getTeachersByInstitutionSiteId($institutionSiteId);

        $controller->set('classOptions', $classOptions);
        $controller->set('teacherOptions', $teacherOptions);
        $controller->set('gradeOptions', $gradeOptions);
    }

    public function qualityVisitView($controller, $params) {
        $controller->Navigation->addCrumb('Quality - Visit');
        $controller->set('subheader', 'Quality - Visit');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));

        if (empty($data)) {
            $controller->redirect(array('action' => 'qualityVisit'));
        }

        $controller->Session->write('QualityVisit.id', $id);
        $controller->set('data', $data);
        
        $SchoolYear = ClassRegistry::init('SchoolYear');
        $year = $SchoolYear->findById($data[$this->name]['school_year_id']);
        
        $EducationGrade = ClassRegistry::init('EducationGrade');
        $grade = $EducationGrade->find('first', array('recursive' => -1, 'contidions' => array('EducationGrade.id' => $data[$this->name]['education_grade_id'])));

        $InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
        $class = $InstitutionSiteClass->getClass($data[$this->name]['institution_site_classes_id']);

        $InstitutionSiteClassTeacher = ClassRegistry::init('InstitutionSiteClassTeacher');
        $teacher = $InstitutionSiteClassTeacher->getTeacher($data[$this->name]['institution_site_teacher_id']);

        $QualityVisitType = ClassRegistry::init('QualityVisitType');
        $visitType = $QualityVisitType->find('first', array('conditions' => array('id' => $data[$this->name]['quality_type_id'])));

        $controller->set('schoolYear', $year['SchoolYear']['name']);
        $controller->set('grade', $grade['EducationGrade']['name']);
        $controller->set('class', $class['InstitutionSiteClass']['name']);
        $controller->set('teacher', $teacher['Teacher']['first_name'] . " " . $teacher['Teacher']['last_name']);
        $controller->set('visitType', $visitType['QualityVisitType']['name']);
    }

    public function qualityVisitAdd($controller, $params) {
        $controller->Navigation->addCrumb('Quality - Add Visit');
        $controller->set('subheader', 'Quality - Add Visit');
        
        $this->_setupStatusForm($controller, $params, 'add');
    }

    public function qualityVisitEdit($controller, $params) {
        $controller->Navigation->addCrumb('Quality - Edit Visit');
        $controller->set('subheader', 'Quality - Edit Visit');
        
        $this->_setupStatusForm($controller, $params, 'edit');
        $this->render = 'add';
    }

    private function _setupStatusForm($controller, $params, $type) {
        $institutionSiteId = $controller->Session->read('InstitutionSiteId');

        if ($type == 'add') {
            $userData = $controller->Session->read('Auth.User');
            $supervisorName = $userData['first_name'] . ' ' . $userData['last_name'];
            $paramsLocateCounter = 0;
        } else {
            $paramsLocateCounter = 1;
        }

        if ($controller->request->is('get')) {
            if ($type == 'edit') {
                if (!empty($params['pass'][0])) {
                    $selectedId = $params['pass'][0];

                    $data = $this->find('first', array('conditions' => array('QualityInstitutionVisit.id' => $selectedId)));
                    if(!empty($data)){
                        $controller->request->data = $data;
                        $selectedTeacherId = $data[$this->name]['institution_site_teacher_id'];
                        $selectedYearId = $data[$this->name]['school_year_id'];
                        $selectedGradeId = $data[$this->name]['education_grade_id'];
                        $selectedClassId = $data[$this->name]['institution_site_classes_id'];
                        $selectedVisitTypeId = $data[$this->name]['quality_type_id'];
                        $institutionSiteId = $data[$this->name]['institution_site_id'];
                        $selectedDate = $data[$this->name]['date'];
                        $supervisorName = trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']);
                    }
                    
                    
                } else {
                  //  return $controller->redirect(array('action' => 'index'));
                }
            }
        } else {
            $postData = $controller->request->data;
            unset($postData[$this->name]['file']);
            
            $this->set($postData);
            if ($this->validates()) {
                if ($this->save($postData)) {
                    $fileData = $controller->request->data[$this->name]['file'];

                    $uploadComplete = false;
                    if ($fileData['error'] != 4) {
                        $controller->FileUploader->fileSizeLimit = 2 * 1024 * 1024;
                        $controller->FileUploader->fileModel = $this->name;
                        $controller->FileUploader->dbPrefix = 'file';
                        $controller->FileUploader->additionalFileType();
                        $controller->FileUploader->uploadFile($this->id);

                        if ($controller->FileUploader->success) {
                            if (empty($controller->request->data[$this->name]['id'])) {
                                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                            } else {
                                $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                            }
                            return $controller->redirect(array('action' => 'qualityVisitView', $this->id));
                        }
                    } else {
                        if (empty($controller->request->data[$this->name]['id'])) {
                            $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                        } else {
                            $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                        }
                        return $controller->redirect(array('action' => 'qualityVisitView', $this->id));
                    }
                }
            }
        }
        $selectedDate = !empty($selectedDate)? $selectedDate : '';
        $selectedDate = !empty($params['pass'][0 + $paramsLocateCounter]) ? $params['pass'][0 + $paramsLocateCounter] : $selectedDate;
     
        $SchoolYear = ClassRegistry::init('SchoolYear');
        $schoolYearOptions = $SchoolYear->getYearList();

        if (empty($schoolYearOptions)) {
            $controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'));
            return $controller->redirect(array('action' => 'qualityVisit'));
        }
        $selectedYearId = !empty($selectedYearId)? $selectedYearId : key($schoolYearOptions);
        $selectedYearId = !empty($params['pass'][1 + $paramsLocateCounter]) ? $params['pass'][1 + $paramsLocateCounter] : $selectedYearId;

        $gradesOptions = array();
        $InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
        $institutionProgramData = $InstitutionSiteProgramme->getProgrammeList($institutionSiteId, $selectedYearId);
        
        foreach ($institutionProgramData as $itemData) {
            if (array_key_exists('education_grades', $itemData)) {
               // $gradesOptions = $itemData['education_grades'];
                $gradesOptions = array_merge($gradesOptions, $itemData['education_grades']);
            }
        }

        $classOptions = array();
        if (!empty($gradesOptions)) {
            $selectedGradeId = !empty($selectedGradeId)? $selectedGradeId : key($gradesOptions);
            $selectedGradeId = !empty($params['pass'][2 + $paramsLocateCounter]) ? $params['pass'][2 + $paramsLocateCounter] : $selectedGradeId;
            $InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
            $classOptions = $InstitutionSiteClass->getClassOptions($selectedYearId, $institutionSiteId, $selectedGradeId);
        }
        $selectedClassId = !empty($params['pass'][3 + $paramsLocateCounter]) ? $params['pass'][3 + $paramsLocateCounter] : key($classOptions);
       
        $teacherOptions = array();
        if (!empty($classOptions)) {
            $InstitutionSiteClassTeacher = ClassRegistry::init('InstitutionSiteClassTeacher');
            $teacherOptions = $InstitutionSiteClassTeacher->getTeachers($selectedClassId, 'list');
            $selectedTeacherId = !empty($selectedTeacherId)? $selectedTeacherId : key($teacherOptions);
            $selectedTeacherId = !empty($params['pass'][4 + $paramsLocateCounter]) ? $params['pass'][4 + $paramsLocateCounter] : $selectedTeacherId;
        }

        $QualityVisitType = ClassRegistry::init('QualityVisitType');
        $visitOptions = $QualityVisitType->find('list');
        $selectedVisitTypeId = !empty($selectedVisitTypeId)? $selectedVisitTypeId : key($visitOptions);
        $selectedVisitTypeId = !empty($params['pass'][5 + $paramsLocateCounter]) ? $params['pass'][5 + $paramsLocateCounter] : $selectedVisitTypeId;

        $controller->set('schoolYearOptions', $schoolYearOptions);
        $controller->set('gradesOptions', $this->checkArrayEmpty($gradesOptions));
        $controller->set('classOptions', $this->checkArrayEmpty($classOptions));
        $controller->set('teacherOptions', $this->checkArrayEmpty($teacherOptions));
        $controller->set('visitOptions', $this->checkArrayEmpty($visitOptions));
        $controller->set('type', $type);
        $controller->set('modelName', $this->name);
        
        if (!empty($selectedDate)) {
            $controller->request->data[$this->name]['date'] = $selectedDate;
        }

        $controller->request->data[$this->name]['school_year_id'] = $selectedYearId;
        $controller->request->data[$this->name]['institution_site_id'] = empty($controller->request->data[$this->name]['institution_site_id']) ? $institutionSiteId : $controller->request->data[$this->name]['institution_site_id'];
        $controller->request->data[$this->name]['education_grade_id'] = empty($selectedGradeId) ? 0 : $selectedGradeId;
        $controller->request->data[$this->name]['institution_site_classes_id'] = empty($selectedClassId) ? 0 : $selectedClassId;
        $controller->request->data[$this->name]['institution_site_teacher_id'] = empty($selectedTeacherId) ? 0 : $selectedTeacherId;
        $controller->request->data[$this->name]['quality_type_id'] = empty($selectedVisitTypeId) ? 0 : $selectedVisitTypeId;
        $controller->request->data[$this->name]['supervisor'] = $supervisorName;
    }

    public function qualityVisitAttachmentDownload($controller, $params) {
        $this->render = false;
        $id = empty($params['pass'][0]) ? NULL : $params['pass'][0];

        if (!empty($id)) {
            $controller->FileUplHoader->fileModel = $this->name;
            $controller->FileUploader->dbPrefix = 'file';
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

}
