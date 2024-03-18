<?php

namespace Staff\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class StaffTabBehavior extends Behavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {
//        die('<pre>'. print_r($this->_table,true));
        $events = parent::implementedEvents();
        $events['Model.custom.getStaffID'] = ['callable' => 'getStaffID', 'priority' => 1001];
        $events['Model.custom.getInstitutionID'] = ['callable' => 'getInstitutionID', 'priority' => 1001];
        $events['Model.custom.getUserID'] = ['callable' => 'getUserID', 'priority' => 1001];
        $events['Model.custom.getStudentID'] = ['callable' => 'getStudentID', 'priority' => 1001];
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


    public function getCareerTabElements($options = [])
    {
        $model = $this->_table;
//         echo "<pre>"; print_r(strval($model->getQueryString('institution_id'))); die;
        $controller = $model->controller;
        $pluginName = $controller->getPlugin();
        $controllerName = $controller->getName();
        $institutionID = $this->getInstitutionID();
//        $staffID = $this->getStaffID();
//        if(!$staffID){
//            $staffID = $this->getUserID();
//        }

        $queryString = $model->getQueryString();

        // echo "<pre>"; print_r($queryString); die;
        $encodedQueryString = $model->paramsEncode($queryString);
        $tabElements = [];
        $staffUrl = [
            'plugin' => $pluginName,
            'controller' => $controllerName];

        $staffTabElements = [
            'EmploymentStatuses' => ['text' => __('Statuses')],
            'Positions' => ['text' => __('Positions')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'StaffLeave' => ['text' => __('Leave')],
            'StaffAttendances' => ['text' => __('Attendances')],
            'Behaviours' => ['text' => __('Behaviours')],
            'StaffAppraisals' => ['text' => __('Appraisals')],
            'Duties' => ['text' => __('Duties')],
            'StaffAssociations' => ['text' => __('Houses')], //POCOR-7938
            'StaffCurriculars' => ['text' => __('Curriculars')] //POCOR-6673 staff career tab section
        ];

        // unset classes and subjects if institution is non-academic

        if ($institutionID) {
            $InstitutionTable = TableRegistry::get('Institution.Institutions');
            $classification = $InstitutionTable->get($institutionID)->classification;
            if ($classification == $InstitutionTable::NON_ACADEMIC) {
                unset($staffTabElements['Classes']);
                unset($staffTabElements['Subjects']);
            }
        }

        $tabElements = array_merge($tabElements, $staffTabElements);
        foreach ($staffTabElements as $key => $tab) {
                $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => $key, 'index', $encodedQueryString]);

        }
        /*echo "<pre>"; print_r($tabElements);
die;*/
        return $tabElements;
    }


    public function getProfessionalTabElements($options = [])
    {
        $model = $this->_table;
        $controller = $model->controller;
        $pluginName = $controller->getPlugin();
        $controllerName = $controller->getName();
        $institutionID = $this->getInstitutionID();
        $queryString = $model->getQueryString();

        $encodedQueryString = $model->paramsEncode($queryString);

       // $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        //$userId = $this->getStaffId();
        //if ($userId) {
           // $options['user_id'] = $userId;
        //}
        //$queryString = $this->getQueryString();
        //$encodedQueryString = $this->paramsEncode($queryString);
        //POCOR-7486-HINDOL minor logical typo
        $tabElements = [];
        //$staffUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
        $staffUrl = ['plugin' => $pluginName, 'controller' => $controllerName];
        $staffTabElements = [
            'Employments' => ['text' => __('Employments')],
            'Qualifications' => ['text' => __('Qualifications')],
            //'Extracurriculars' => ['text' => __('Extracurriculars')],//POCOR-7513
            'Memberships' => ['text' => __('Memberships')],
            'Licenses' => ['text' => __('Licenses')],
            'Awards' => ['text' => __('Awards')],
            //'Curriculars' => ['text' => __('Curriculars')], //POCOR-6673
        ];

        $tabElements = array_merge($tabElements, $staffTabElements);

        foreach ($tabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => $key, 'index', $encodedQueryString]);
        }
        return $tabElements;
    }
}
