<?php

namespace Student\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;

class StudentTabBehavior extends Behavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {

//        die('<pre>'. print_r($this->_table,true));
        $events = parent::implementedEvents();
        //$events['Model.custom.getStudentID'] = ['callable' => 'getStudentID', 'priority' => 1001];
        //$events['ControllerAction.Model.getInstitutionID'] = ['callable' => 'getInstitutionID', 'priority' => 1001];
        //$events['Model.custom.getUserID'] = ['callable' => 'getUserID', 'priority' => 1001];
        //$events['Model.custom.getStudentID'] = ['callable' => 'getStudentID', 'priority' => 1001];
        return $events;
    }



    public function getUserID()
    {
        $model = $this->_table;
        $userID = $model->getQueryString('security_user_id');
        if (!$userID) {
            $userID = $model->getQueryString('user_id');
        }
        if (!$userID) {
            return null;
        }
        return $userID;
    }


    public function getInstitutionID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('institution_id');
        return $institutionID;
    }


    public function getStaffID()
    {
        $model = $this->_table;
        $staffID = $model->getQueryString('staff_id');
        return $staffID;
    }

    public function getStudentID()
    {
        $model = $this->_table;
        $studentID = $model->getQueryString('student_id');
        return $studentID;
    }

    public function getAcademicTabElements($options = [])
    {
        //$id = (isset($options['id'])) ? $options['id'] : 0;
        $model = $this->_table;
        // POCOR-8074-QueryStringProfile start
        $maincontroller = $model->controller;
        $controllerName = $maincontroller->getName();

        $studentID = $this->getStudentID();
        $institutionID = $this->getInstitutionID();
        $type = (isset($options['type'])) ? $options['type'] : null;
        $tabElements = [];
        $studentTabElements = [
            'Programmes' => ['text' => __('Programmes')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Absences' => ['text' => __('Attendance')], // POCOR-8299
            'Behaviours' => ['text' => __('Behaviours')],
            'Outcomes' => ['text' => __('Outcomes')],
            'Competencies' => ['text' => __('Competencies')],
            //POCOR-7474-HINDOL TYPO FIX
            'Assessments' => ['text' => __('Assessments')], //POCOR-5786
            'ExaminationResults' => ['text' => __('Examinations')],
            'ReportCards' => ['text' => __('Report Cards')],
            'Awards' => ['text' => __('Awards')],
            //'Extracurriculars' => ['text' => __('Extracurriculars')],//POCOR-7648
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Houses')], //POCOR-7938
            'Curriculars' => ['text' => __('Curriculars')] //POCOR-6673
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);
        $params = ['id' => $studentID,
            'student_id' => $studentID,
            'user_id' => $studentID,
            'institution_id' => $institutionID,
            'type' => $type];
        $queryString = $model->paramsEncode($params);
        // Programme & Textbooks will use institution controller, other will be still using student controller
        $institutionControllerAction = [
            'Programmes',
            'Textbooks',
            'Associations',
            'Curriculars',
            'Risks'];
        foreach ($studentTabElements as $key => $tab) {
            if (in_array($key, $institutionControllerAction)) {
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', '0' => 'index',
                '1' => $queryString];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Student' . $key, 'type' => $type]);
            } else {
                $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $urlParams = ['action' => $key, '0' => 'index','1' => $queryString];
                $tabElements[$key]['url'] = array_merge($studentUrl, $urlParams);
            }
        }

        if (Configure::read('schoolMode')) {
            if (isset($tabElements['ExaminationResults'])) {
                unset($tabElements['ExaminationResults']);
            }
            if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
                if (isset($tabElements['Risks'])) {
                    unset($tabElements['Risks']);
                }
            }
        }

        return $tabElements;
    }




}
