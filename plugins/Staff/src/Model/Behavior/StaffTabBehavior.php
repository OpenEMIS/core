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
        $type = (array_key_exists('type', $options))? $options['type']: null;
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
            'StaffEmploymentStatuses' => ['text' => __('Statuses')],
            'StaffPositions' => ['text' => __('Positions')],
            'StaffClasses' => ['text' => __('Classes')],
            'StaffSubjects' => ['text' => __('Subjects')],
            'StaffLeave' => ['text' => __('Leave')],
            'StaffAttendances' => ['text' => __('Attendances')],
            'StaffBehaviours' => ['text' => __('Behaviours')],
            'StaffAppraisals' => ['text' => __('Appraisals')],
            'StaffDuties' => ['text' => __('Duties')],
            'StaffAssociations' => ['text' => __('Houses')], //POCOR-7938
            //'Curriculars' => ['text' => __('Curriculars')] //POCOR-6673 staff career tab section
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
                $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => $key, 'index', $encodedQueryString, 'type'=>$type]);

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
        $queryString = $model->getQueryString();

        $encodedQueryString = $model->paramsEncode($queryString);

        $tabElements = [];
        $staffUrl = ['plugin' => $pluginName, 'controller' => $controllerName];
        $staffTabElements = [
            'Employments' => ['text' => __('Employments')],
            'StaffQualifications' => ['text' => __('Qualifications')],
            'StaffMemberships' => ['text' => __('Memberships')],
            'StaffLicenses' => ['text' => __('Licenses')],
            'StaffAwards' => ['text' => __('Awards')],
        ];
        if($controllerName == 'Students'){
            $staffTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
                'Licenses' => ['text' => __('Licenses')], //POCOR-7528
            ];
        }
        $tabElements = array_merge($tabElements, $staffTabElements);

        foreach ($tabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => $key, 'index', $encodedQueryString]);
        }

        if($controllerName == "Profiles"){
            $session = $model->request->getSession();
            $isStudent = $session->read('Auth.User.is_student');
            $isStaff = $session->read('Auth.User.is_staff');
            if ($isStaff) {
                $staffTabElements = [
                    'Employments' => ['text' => __('Employments')],
                    'Qualifications' => ['text' => __('Qualifications')],
                    'Memberships' => ['text' => __('Memberships')],
                    'Licenses' => ['text' => __('Licenses')],
                    'Awards' => ['text' => __('Awards')],
                ];
            } else if ($isStudent) {
                $staffTabElements = [
                    'Employments' => ['text' => __('Employments')],
                    'Qualifications' => ['text' => __('Qualifications')],
                    'Licenses' => ['text' => __('Licenses')],
                ];
            } else {
                $staffTabElements = [
                    'Employments' => ['text' => __('Employments')],
                    'Qualifications' => ['text' => __('Qualifications')],
                    'Licenses' => ['text' => __('Licenses')],
                ];
            }
            foreach ($staffTabElements as $key => $tab) {
                if ($key != 'Employments') {
                    $url = array_merge($staffUrl, ['action' => 'Staff' . $key, '0' => 'index']);

                } else {
                    $url = array_merge($staffUrl, ['action' => $key, '0' => 'index']);
                }
                $url['1'] = $encodedQueryString;
                $tabElements[$key]['url'] = $url;
            }
            return $controller->TabPermission->checkTabPermission($tabElements);
        }

        if($controllerName == "Directories"){
            $userID = $this->getUserID();
            if(!is_numeric($userID)){
                return [];
            }
            $Users = TableRegistry::getTableLocator()->get('Security.Users');
            $user = $Users->get($userID);
            $isStaff = $user->is_staff;
            $isStudent = $user->is_student;
            $tabElements = [];
            if ($isStaff) {
                $staffTabElements = [
                    'Employments' => ['text' => __('Employments')],
                    'Qualifications' => ['text' => __('Qualifications')],
                    'Memberships' => ['text' => __('Memberships')],
                    'Licenses' => ['text' => __('Licenses')],
                    'Awards' => ['text' => __('Awards')],
                ];
            } else if ($isStudent) {
                $staffTabElements = [
                    'Employments' => ['text' => __('Employments')],
                    'Qualifications' => ['text' => __('Qualifications')],
                    'StudentLicenses' => ['text' => __('Licenses')],
                ];
            } else {
                $staffTabElements = [
                    'Employments' => ['text' => __('Employments')],
                    'Qualifications' => ['text' => __('Qualifications')],
                    'Licenses' => ['text' => __('Licenses')],
                ];
            }
            $tabElements = array_merge($tabElements, $staffTabElements);
            foreach ($staffTabElements as $key => $tab) {
                if($key == 'StudentLicenses'){
                    $url = array_merge($staffUrl, ['action' => $key, '0' => 'index']);
                } else if($key != 'Employments') {
                    $url = array_merge($staffUrl, ['action' => 'Staff' . $key, '0' => 'index']);

                } else {
                    $url = array_merge($staffUrl, ['action' => $key, '0' => 'index']);
                }
                $url['1'] = $encodedQueryString;
                $tabElements[$key]['url'] = $url;
            }
            return $controller->TabPermission->checkTabPermission($tabElements);
        }
        return $tabElements;
    }

    /*
     *
     *     public function getProfessionalTabElements($options = [])
    {

        $session = $this->request->getSession();
        $isStudent = $session->read('Directory.Directories.is_student');
        $isStaff = $session->read('Directory.Directories.is_staff');

        $tabElements = [];
        $directoryUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $user=0;//POCOR-7528
        if ($isStaff) {
            $user=1;//POCOR-7528
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
                'Extracurriculars' => ['text' => __('Extracurriculars')],
                'Memberships' => ['text' => __('Memberships')],
                'Licenses' => ['text' => __('Licenses')],
                'Awards' => ['text' => __('Awards')],
            ];
        } else {
            $user=0;//POCOR-7528
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Licenses' => ['text' => __('Licenses')],
            ];
        }
        $tabElements = array_merge($tabElements, $professionalTabElements);

        foreach ($professionalTabElements as $key => $tab) {
            //POCOR-7528 start
            if($key == 'Licenses'){
                if($user==1){
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' =>'Staff'.$key, 'index']);
                }
                else if($user==0){
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' =>'Student'.$key, 'index']);
                }
            }
            //POCOR-7528 end
            else if ($key != 'Employments') {
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' => 'Staff'.$key, 'index']);
            }

            else {
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' => $key, 'index']);
            }
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

     *  public
    function getProfessionalTabElements($options = [])
    {
        $session = $this->request->getSession();
        $isStudent = $session->read('Auth.User.is_student');
        $isStaff = $session->read('Auth.User.is_staff');

        $tabElements = [];
        $profileUrl = ['plugin' => 'Profile', 'controller' => 'Profiles'];

        if ($isStaff) {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
                //'Extracurriculars' => ['text' => __('Extracurriculars')],//POCOR-7513
                'Memberships' => ['text' => __('Memberships')],
                'Licenses' => ['text' => __('Licenses')],
                'Awards' => ['text' => __('Awards')],
            ];
        } else if ($isStudent) {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
            ];
        } else {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
            ];
        }
        $tabElements = array_merge($tabElements, $professionalTabElements);
        $userID = $this->getQueryString('user_id');
        $params = ['user_id' => $userID];
        $queryString = $this->paramsEncode($params);
        foreach ($professionalTabElements as $key => $tab) {
            if ($key != 'Employments') {
                $url = array_merge($profileUrl, ['action' => 'Staff' . $key, 'index']);

            } else {
                $url = array_merge($profileUrl, ['action' => $key, 'index']);

            }
            $url[] = $queryString;
            $tabElements[$key]['url'] = $url;
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

     */
}
