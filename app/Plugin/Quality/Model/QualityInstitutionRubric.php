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

class QualityInstitutionRubric extends QualityAppModel {

    //  public $useTable = false;
    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        //'Student',
        /* 'RubricsTemplate' => array(
          'foreignKey' => 'rubric_template_id'
          ), */
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
        'institution_site_class_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Class.'
            )
        ),
        'institution_site_class_grade_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Grade.'
            )
        ),
        'teacher_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //   'required' => true,
                'message' => 'Please select a valid Teacher.'
            )
        ),
        'rubric_template_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                // 'required' => true,
                'message' => 'Please select a valid Rubric.'
            )
        ),
        'comment' => array(
            'ruleRequired' => array(
                'rule' => 'checkCommentLength', //array('maxLength', 1),
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

    public function checkCommentLength($data) {
        if (str_word_count($data['comment']) > 2) {
            return false;
        }

        return true;
    }

    public function beforeAction($controller, $action) {
        if ($action != 'qualityRubric') {
            // $controller->Navigation->addCrumb('Rubrics', array('controller' => 'Quality', 'action' => 'qualityRubric', 'plugin' => 'Quality'));
        }
    }

    public function qualityRubric($controller, $params) {
        $institutionSiteId = $controller->Session->read('InstitutionSiteId');
        $institutionId = $controller->Session->read('InstitutionId');
        $controller->Navigation->addCrumb('Rubrics');
        $controller->set('subheader', 'Rubrics');
        $controller->set('modelName', $this->name);

        $this->recursive = -1;
        $data = $this->find('all', array('conditions' => array('institution_site_id' => $institutionSiteId)));
        
        $controller->set('data', $data);

        $SchoolYear = ClassRegistry::init('SchoolYear');
        $schoolYearOptions = $SchoolYear->getYearList();

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricOptions = $RubricsTemplate->getRubricOptions();

        $InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
        $classOptions = $InstitutionSiteClass->getClassListByInstitution($institutionSiteId);

        $InstitutionSiteClassTeacher = ClassRegistry::init('InstitutionSiteClassTeacher');
        $teacherOptions = $InstitutionSiteClassTeacher->getTeachersByInstitutionSiteId($institutionSiteId);

        $controller->set('classOptions', $classOptions);
        $controller->set('teacherOptions', $teacherOptions);
        $controller->set('schoolYearOptions', $schoolYearOptions);
        $controller->set('rubricOptions', $rubricOptions);
    }

    public function qualityRubricAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Rubrics');
        $controller->set('subheader', 'Add Rubrics');
        $controller->set('type', 'add');
        $this->_setupRubricForm($controller, $params, 'add');
    }

    public function qualityRubricEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Rubrics');
        $controller->set('subheader', 'Edit Rubrics');
        $controller->set('type', 'edit');
        $this->_setupRubricForm($controller, $params, 'edit');

        $this->render = 'add';
    }

    private function _setupRubricForm($controller, $params, $type) {
        $institutionId = $controller->Session->read('InstitutionId');
        $institutionSiteId = $controller->Session->read('InstitutionSiteId');

        if ($type == 'add') {
            $userData = $controller->Session->read('Auth.User');
            $evaluatorName = $userData['first_name'] . ' ' . $userData['last_name'];

            $paramsLocateCounter = 0;
        } else {
            if (!empty($params['pass'][0])) {
                $selectedId = $params['pass'][0];
                $data = $this->find('first', array('conditions' => array('QualityInstitutionRubric.id' => $selectedId)));
                if (!empty($data)) {
                    $evaluatorName = trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']);
                }
            }
            $paramsLocateCounter = 1;
        }
        
        if ($controller->request->is('get')) {
            if ($type == 'edit') {
                if (!empty($data)) {
                    $controller->request->data = $data;
                    $selectedTeacherId = $data[$this->name]['teacher_id'];
                    $selectedRubricId = $data[$this->name]['rubric_template_id'];
                    $selectedYearId = $data[$this->name]['school_year_id'];
                    $selectedClassId = $data[$this->name]['institution_site_class_id'];
                    $selectedGradeId = $data[$this->name]['institution_site_class_grade_id'];
                    $institutionSiteId = $data[$this->name]['institution_site_id'];
                    
                }
            }
        } else {
          //  pr($controller->request->data); // die;

            $proceedToSave = true;
            if ($type == 'add') {
                $conditions = array(
                    'QualityInstitutionRubric.institution_site_id' => $controller->request->data['QualityInstitutionRubric']['institution_site_id'],
                    'QualityInstitutionRubric.rubric_template_id' => $controller->request->data['QualityInstitutionRubric']['rubric_template_id'],
                    'QualityInstitutionRubric.school_year_id' => $controller->request->data['QualityInstitutionRubric']['school_year_id'],
                    'QualityInstitutionRubric.institution_site_class_grade_id' => $controller->request->data['QualityInstitutionRubric']['institution_site_class_grade_id'],
                    'QualityInstitutionRubric.institution_site_class_id' => $controller->request->data['QualityInstitutionRubric']['institution_site_class_id'],
                    'QualityInstitutionRubric.teacher_id' => $controller->request->data['QualityInstitutionRubric']['teacher_id']
                );

                if ($this->hasAny($conditions)) {
                    $proceedToSave = false;
                    $controller->Utility->alert($controller->Utility->getMessage('DATA_EXIST'), array('type' => 'error'));
                }
            }
            if ($proceedToSave) {
                if ($this->saveAll($controller->request->data)) {
                    // pr('save');
                    $id = $this->id;
                    if ($type == 'add') {
                        $controller->Session->write('QualityRubric.editable', 'true');
                        $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                        return $controller->redirect(array('action' => 'qualityRubricHeader', $id, $controller->request->data['QualityInstitutionRubric']['rubric_template_id']));
                    } else {
                        $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                        return $controller->redirect(array('action' => 'qualityRubricView', $id));
                    }
                }
            }
        }
        $SchoolYear = ClassRegistry::init('SchoolYear');
        $schoolYearOptions = $SchoolYear->getYearList();

        if (empty($schoolYearOptions)) {
            $controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'));
            return $controller->redirect(array('action' => 'qualityVisit'));
        }

        $selectedYearId = !empty($selectedYearId) ? $selectedYearId : key($schoolYearOptions);
        $selectedYearId = !empty($params['pass'][0 + $paramsLocateCounter]) ? $params['pass'][0 + $paramsLocateCounter] : $selectedYearId;

        //Process Grade
        $InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
        $gradeOptions = $InstitutionSiteClassGrade->getGradesByInstitutionSiteId($institutionSiteId);
        $selectedGradeId = !empty($selectedGradeId) ? $selectedGradeId : key($gradeOptions);
        $selectedGradeId = !empty($params['pass'][1 + $paramsLocateCounter]) ? $params['pass'][1 + $paramsLocateCounter] : $selectedGradeId;
        $selectedGradeId = empty($selectedGradeId) ? 0 : $selectedGradeId;
        
        //Process Class
        $InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
        $classOptions = $InstitutionSiteClass->getClassOptions($selectedYearId, $institutionSiteId, $selectedGradeId);
        $selectedClassId = !empty($selectedClassId) ? $selectedClassId : key($classOptions);
        $selectedClassId = !empty($params['pass'][2 + $paramsLocateCounter]) ? $params['pass'][2 + $paramsLocateCounter] : $selectedClassId;
        
        //Process Rubric
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricOptions = $RubricsTemplate->getEnabledRubricsOptions($schoolYearOptions[$selectedYearId],$selectedGradeId);
        //   pr($schoolYearOptions[$selectedYearId]); die;
        $selectedRubricId = !empty($selectedRubricId) ? $selectedRubricId : key($rubricOptions);
        $selectedRubricId = !empty($params['pass'][3 + $paramsLocateCounter]) ? $params['pass'][3 + $paramsLocateCounter] : $selectedRubricId;
        
        //Process Teacher
        $InstitutionSiteClassTeacher = ClassRegistry::init('InstitutionSiteClassTeacher');
        $teacherOptions = $InstitutionSiteClassTeacher->getTeachers($selectedClassId, 'list');
        $selectedTeacherId = !empty($selectedTeacherId) ? $selectedTeacherId : key($teacherOptions);
        $selectedTeacherId = !empty($params['pass'][4 + $paramsLocateCounter]) ? $params['pass'][4 + $paramsLocateCounter] : $selectedTeacherId;

        $controller->set('schoolYearOptions', $this->checkArrayEmpty($schoolYearOptions));
        $controller->set('rubricOptions', $this->checkArrayEmpty($rubricOptions));
        $controller->set('classOptions', $this->checkArrayEmpty($classOptions));
        $controller->set('gradeOptions', $this->checkArrayEmpty($gradeOptions));
        $controller->set('teacherOptions', $this->checkArrayEmpty($teacherOptions));
        $controller->set('type', $type);
        $controller->set('modelName', $this->name);

        $controller->request->data[$this->name]['evaluator'] = $evaluatorName;
        $controller->request->data[$this->name]['school_year_id'] = $selectedYearId;
        $controller->request->data[$this->name]['institution_site_id'] = empty($controller->request->data[$this->name]['institution_site_id']) ? $institutionSiteId : $controller->request->data[$this->name]['institution_site_id'];
        $controller->request->data[$this->name]['rubric_template_id'] = empty($selectedRubricId) ? 0 : $selectedRubricId;
        $controller->request->data[$this->name]['institution_site_class_id'] = empty($selectedClassId) ? 0 : $selectedClassId;
        $controller->request->data[$this->name]['institution_site_class_grade_id'] = $selectedGradeId;
        $controller->request->data[$this->name]['teacher_id'] = empty($selectedTeacherId) ? 0 : $selectedTeacherId;
    }

    public function qualityRubricView($controller, $params) {
        $controller->Navigation->addCrumb('Details');
        $controller->set('subheader', 'Details');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));

        if (empty($data)) {
            $controller->redirect(array('action' => 'qualityVisit'));
        }
        
        $controller->Session->write('QualityRubric.id', $id);
        $controller->set('data', $data);

        $SchoolYear = ClassRegistry::init('SchoolYear');
        $year = $SchoolYear->findById($data[$this->name]['school_year_id']);

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubric = $RubricsTemplate->findById($data[$this->name]['rubric_template_id']);

        $InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
        $class = $InstitutionSiteClass->getClass($data[$this->name]['institution_site_class_id']);

        $InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
        $grade = $InstitutionSiteClassGrade->getGrade($data[$this->name]['institution_site_class_grade_id']);
        
        $InstitutionSiteClassTeacher = ClassRegistry::init('InstitutionSiteClassTeacher');
        $teacher = $InstitutionSiteClassTeacher->getTeacher($data[$this->name]['teacher_id']);

        $QualityStatus = ClassRegistry::init('Quality.QualityStatus');
        $editable = $QualityStatus->getRubricStatus($year['SchoolYear']['name'], $data[$this->name]['rubric_template_id']);

        $disableDelete = false;
        $QualityInstitutionRubricsAnswer = ClassRegistry::init('Quality.QualityInstitutionRubricsAnswer');
        $answerCountData = $QualityInstitutionRubricsAnswer->getTotalCount($data[$this->name]['institution_site_id'], $data[$this->name]['rubric_template_id'], $data[$this->name]['id']);

        if (!empty($answerCountData)) {
            $disableDelete = true;
        };
        $controller->set('disableDelete', $disableDelete);

        // pr($editable);
        $controller->Session->write('QualityRubric.editable', $editable);

        $controller->set('rubric_template_id', $data[$this->name]['rubric_template_id']);
        $controller->set('schoolYear', $year['SchoolYear']['name']);
        $controller->set('rubric', $rubric['RubricsTemplate']['name']);
        $controller->set('class', $class['InstitutionSiteClass']['name']);
        $controller->set('grade', $grade['InstitutionSiteClassGrade']['grade_name']);
        $controller->set('teacher', $teacher['Teacher']['first_name'] . " " . $teacher['Teacher']['last_name']);
    }

    public function qualityRubricDelete($controller, $params) {
        if ($controller->Session->check('QualityRubric.id')) {
            $id = $controller->Session->read('QualityRubric.id');

            $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));

            $SchoolYear = ClassRegistry::init('SchoolYear');
            $year = $SchoolYear->findById($data[$this->name]['school_year_id']);

            $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
            $rubric = $RubricsTemplate->findById($data[$this->name]['rubric_template_id']);

            $name = $rubric['RubricsTemplate']['name'] . " (" . $year['SchoolYear']['name'] . ")";

            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->Session->delete('QualityRubric.id');
            $controller->redirect(array('action' => 'qualityRubric'));
        }
    }

    public function getAssignedInstitutionRubricCount($yearid, $rubricId) {
        $options['conditions'] = array('school_year_id' => $yearid, 'rubric_template_id' => $rubricId);
        $options['fields'] = array('COUNT(id) as Total');
        $options['recursive'] = -1;
        $data = $this->find('first', $options);
        return $data[0]['Total'];
    }

}
