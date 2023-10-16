<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;

class NavigationComponent extends Component
{
    public $controller;
    public $action;
    public $breadcrumbs = [];

    public $components = ['AccessControl'];

    /**
     * @return array
     */
    private static function getFullPerformanceNavigation()
    {
        $fullPerformanceNavigation = [
            'Administration.Performance' => [
                'title' => 'Performance',
                'parent' => 'Administration',
                'link' => false
            ],
            'Competencies.Templates' => [
                'title' => 'Competencies',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'Competency'],
                'selected' => ['Competencies.Templates',
                    'Competencies.Items',
                    'Competencies.Criterias',
                    'Competencies.Periods',
                    'Competencies.GradingTypes']
            ],

            'Outcomes.Templates' => [
                'title' => 'Outcomes',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'Outcome'],
                'selected' => ['Outcomes.Templates',
                    'Outcomes.Criterias',
                    'Outcomes.Periods',
                    'Outcomes.GradingTypes',
                    'Outcomes.ImportOutcomeTemplates']
            ],

            'Assessments.Assessments' => [
                'title' => 'Assessments',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'Assessment'],
                'selected' => ['Assessments.Assessments',
                    'Assessments.AssessmentPeriods',
                    'Assessments.GradingTypes']
            ],

            'ReportCards.Templates' => [
                'title' => 'Report Cards',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'ReportCard'],
                'selected' => ['ReportCards.Templates',
                    'ReportCards.ReportCardEmail',
                    'ReportCards.Processes']
            ],

        ];
        return $fullPerformanceNavigation;
    }

    /**
     * @return array
     */
    private static function getTrainingNavigationFull()
    {
        $trainingNavigation = [
            'Administration.Training' => [
                'title' => 'Training',
                'parent' => 'Administration',
                'link' => false,
            ],

            'Trainings.Courses' => [
                'title' => 'Courses',
                'parent' => 'Administration.Training',
                'params' => ['plugin' => 'Training'],
                'selected' => ['Trainings.Courses']
            ],

            'Trainings.Sessions' => [
                'title' => 'Sessions',
                'parent' => 'Administration.Training',
                'params' => ['plugin' => 'Training'],
                'selected' => ['Trainings.Sessions',
                    'Trainings.Applications',
                    'Trainings.ImportTrainees']
            ],

            'Trainings.Results' => [
                'title' => 'Results',
                'parent' => 'Administration.Training',
                'params' => ['plugin' => 'Training'],
                'selected' => ['Trainings.Results',
                    'Trainings.ImportTrainingSessionTraineeResults']//5695
            ],
        ];
        return $trainingNavigation;
    }

    /**
     * @param $user_id
     * @return mixed
     */
    private static function isSuperUser($user_id)
    {
        $users = TableRegistry::get('security_users');
        $is_super_user = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $user_id])->first();
        return $is_super_user;
    }

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->action = $this->request->params['action'];
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.initialize'] = ['callable' => 'beforeFilter',
            'priority' => '11'];
        return $events;
    }

    public function addCrumb($title, $options = [])
    {
        $item = array(
            'title' => __($title),
            'link' => ['url' => $options],
            'selected' => sizeof($options) == 0
        );
        $this->breadcrumbs[] = $item;
        $this->controller->set('_breadcrumbs', $this->breadcrumbs);
    }

    public function substituteCrumb($oldTitle, $title, $options = [])
    {
        foreach ($this->breadcrumbs as $key => $value) {
            if ($value['title'] == __($oldTitle)) {
                $this->breadcrumbs[$key] = $item = array(
                    'title' => __($title),
                    'link' => ['url' => $options],
                    'selected' => sizeof($options) == 0
                );
                $this->controller->set('_breadcrumbs', $this->breadcrumbs);
                break;
            }
        }
    }

    public function removeCrumb($title)
    {
        $key = array_search(__($title), $this->array_column($this->breadcrumbs, 'title'));
        if ($key) {
            unset($this->breadcrumbs[$key]);
            $this->controller->set('_breadcrumbs', $this->breadcrumbs);
        }
    }

    public function beforeFilter(Event $event)
    {
        $controller = $this->controller;
        try {
            $navigations = $this->buildNavigation();
            $this->checkSelectedLink($navigations);
            $this->checkPermissions($navigations);
            $controller->set('_navigations', $navigations);
        } catch (SecurityException $ex) {
            // echo "<pre>";print_r($ex);die(); POCOR-6705
            return $ex;
        }
    }

    private function getLink($controllerActionModelLink, $params = [])
    {
        $url = ['plugin' => null, 'controller' => null, 'action' => null];
        if (isset($params['plugin'])) {
            $url['plugin'] = $params['plugin'];
            unset($params['plugin']);
        }

        $link = explode('.', $controllerActionModelLink);

        if (isset($params['controller'])) {
            $url['controller'] = $params['controller'];
            unset($params['controller']);
        } else if (isset($link[0])) {
            $url['controller'] = $link[0];
        }

        if (isset($params['action'])) {
            $url['action'] = $params['action'];
            unset($params['action']);
        } else if (isset($link[1])) {
            $url['action'] = $link[1];
        }

        if (isset($link[2])) {
            $url['0'] = $link[2];
        }

        if (!empty($params)) {
            $url = array_merge($url, $params);
        }
        return $url;
    }

    public function checkPermissions(array &$navigations)
    {

        $session = $this->request->session();
        $superAdmin = $session->read('Auth.User.super_admin');
        if ($superAdmin) {
            return;
        }

        $roles = [];
        $restrictedTo = [];
        $event = $this->controller->dispatchEvent('Controller.Navigation.onUpdateRoles', null, $this);
        if ($event->result) {
            $roles = $event->result['roles'];
            $restrictedTo = $event->result['restrictedTo'];
        }

        // Unset the children
        $linkOnly = [];
        foreach ($navigations as $key => $value) {
            $rolesRestrictedTo = $roles;
            //print_r($roles);die;
            if (isset($value['link']) && !$value['link']) {
                $linkOnly[] = $key;
            } else {
                $params = [];
                if (isset($value['params'])) {
                    $params = $value['params'];
                }
                $url = $this->getLink($key, $params);

                // Check if the role is only restricted to a certain page
                foreach ($restrictedTo as $restrictedURL) {
                    if (count(array_intersect($restrictedURL, $url)) > 0) {
                        break;
                    } else {
                        $rolesRestrictedTo = [];
                    }
                }

                // $ignoredAction will be excluded from permission checking
                if (array_key_exists('controller', $url) && !in_array($url['plugin'])) {
                    //   print_r($url);die();
                    if (!$this->AccessControl->check($url, $rolesRestrictedTo)) {
                        unset($navigations[$key]);
                    }
                }
            }
        }
        // unset empty links in reverse order
        $linkOnly = array_reverse($linkOnly);
        foreach ($linkOnly as $link) {
            if (!array_search($link, $this->array_column($navigations, 'parent'))) {
                unset($navigations[$link]);
            }
        }
    }

    public function checkSelectedLink(array &$navigations)
    {
        // Set the pass variable
        if (!empty($this->request->pass)) {
            $pass = $this->request->pass;
        } else {
            $pass[0] = '';
        }

        // The URL name "Controller.Action.Model or Controller.Action"
        $controller = $this->controller->name;
        $action = $this->action;
        $linkName = $controller . '.' . $action;
        $controllerActionLink = $linkName;
        if (!empty($pass[0])) {
            $linkName .= '.' . $pass[0];
        }

        if (!in_array($linkName, $navigations)) {
            $selectedArray = $this->array_column($navigations, 'selected');
            foreach ($selectedArray as $k => $selected) {
                //echo '<pre>'.$linkName.'#####'; print_r($selected);
                if (is_array($selected) && (in_array($linkName, $selected) || in_array($controllerActionLink, $selected))) {
                    $linkName = $k;
                    break;
                }
            }
        }
        $children = $this->array_column($navigations, 'parent');
        foreach ($children as $key => $child) {
            if ($child == $linkName) {
                unset($navigations[$key]);
            }
        }
    }

    public function checkClassification(array &$navigations)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        if (!empty($institutionId)) {
            $Institutions = TableRegistry::get('Institution.Institutions');

            if ($Institutions->exists([$Institutions->primaryKey() => $institutionId])) {
                $currentInstitution = $Institutions->get($institutionId);
                $classification = $currentInstitution->classification;

                if ($classification == $Institutions::NON_ACADEMIC) {
                    // navigation items to exclude from non-academic institutions
                    $academicArray = [
                        'Institution.Academic',
                        'Institutions.Students.index',
                        'Institutions.StudentAttendances.index',
                        'Institutions.StudentBehaviours.index',
                        'Institutions.Assessments.index',
                        'Institutions.Examinations',
                        'Institutions.Fees',
                        'Institutions.StudentFees',
                        'Institutions.Rubrics',
                        'Institutions.VisitRequests',
                        'Institutions.StudentCompetencies',
                        'Institutions.Indexes.index',
                        'Institutions.ReportCards'
                    ];

                    $navigationParentList = $this->array_column($navigations, 'parent');
                    foreach ($navigationParentList as $navigationKey => $parent) {
                        // unset navigation item and all children if in academicArray
                        if (in_array($parent, $academicArray) || in_array($navigationKey, $academicArray)) {
                            unset($navigations[$navigationKey]);
                        }
                    }
                }
            }
        }
    }

    // PHP 5.5 array_column alternative
    public function array_column($array, $column_name)
    {
        return array_map(
            function ($element) use ($column_name) {
                if (isset($element[$column_name])) {
                    return $element[$column_name];
                }
            },
            $array
        );
    }

    public function buildNavigation()
    {
        // $navigations = $this->getNavigation();
        $navigations = $this->getMainNavigation();

        $controller = $this->controller;
        $action = $this->action;
        $pass = [];
        if (!empty($this->request->pass)) {
            $pass = $this->request->pass;
        } else {
            $pass[0] = '';
        }

        $institutionStudentActions = ['Students',
            'StudentUser',
            'StudentAccount',
            'StudentSurveys',
            'Students'];
        $institutionStaffActions = ['Staff',
            'StaffUser',
            'StaffAccount'];
        $institutionActions = array_merge($institutionStudentActions, $institutionStaffActions);
        $institutionControllers = [
            'Counsellings',
            'StudentBodyMasses',
            'StaffBodyMasses',
            'StudentComments',
            'StaffComments',
            'InfrastructureNeeds',
            'InfrastructureProjects',
            'InfrastructureWashWaters',
            'InfrastructureWashSanitations',
            'InfrastructureWashHygienes',
            'InfrastructureWashWastes',
            'InfrastructureWashSewages',
            'InfrastructureUtilityElectricities',
            'InfrastructureUtilityInternets',
            'InfrastructureUtilityTelephones',
            'InstitutionTransportProviders',
            'InstitutionBuses',
            'InstitutionTrips',
            'InstitutionStaffDuties',
            'StudentHistories',
            'StaffHistories',
            'InstitutionCalendars',
            'InstitutionContactPersons',
            'StudentInsurances',
            'StaffInsurances',
            'InstitutionCommittees',
            'InstitutionCommitteeAttachments',
            'InstitutionAssets',
            'StudentBehaviourAttachments',
            'StaffBehaviourAttachments',
            'Guardians',
            'GuardianComments'
        ];

        $profileControllers = ['ProfileBodyMasses',
            'ProfileComments',
            'ProfileInsurances',
            'ScholarshipsDirectory',
            'ProfileApplicationInstitutionChoices',
            'ProfileApplicationAttachments'];
        $directoryControllers = ['DirectoryBodyMasses',
            'DirectoryComments',
            'DirectoryInsurances'];
        $guardianNavsControllers = [];
        if (in_array($controller->name, $institutionControllers) || (
                $controller->name == 'Institutions'
                && $action != 'index'
                && (!in_array($action, $institutionActions))
            )
        ) {
            $navigations = $this->appendNavigation('Institutions.Institutions.index', $navigations, $this->getInstitutionNavigation());
            $navigations = $this->appendNavigation('Institutions.Students.index', $navigations, $this->getInstitutionStudentNavigation());
            $navigations = $this->appendNavigation('Institutions.Staff.index', $navigations, $this->getInstitutionStaffNavigation());
            $this->checkClassification($navigations);
        } elseif (($controller->name == 'Students' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStudentActions))) {
            $navigations = $this->appendNavigation('Institutions.Institutions.index', $navigations, $this->getInstitutionNavigation());
            $navigations = $this->appendNavigation('Institutions.Students.index', $navigations, $this->getInstitutionStudentNavigation());
            $this->checkClassification($navigations);
        } elseif (($controller->name == 'Staff' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStaffActions))) {
            $navigations = $this->appendNavigation('Institutions.Institutions.index', $navigations, $this->getInstitutionNavigation());
            $navigations = $this->appendNavigation('Institutions.Staff.index', $navigations, $this->getInstitutionStaffNavigation());
            $this->checkClassification($navigations);
        } elseif (($controller->name == 'Directories' && $action != 'index') || in_array($controller->name, $directoryControllers)) {
            $navigations = $this->appendNavigation('Directories.Directories.index', $navigations, $this->getDirectoryNavigation());

//  POCOR-7768 - unused code causing error
//            $encodedParam = $this->request->params['pass'][1];
//            if (!empty($encodedParam)) {
//                $securityUserId = $this->controller->paramsDecode($encodedParam)['id'];
//                /*POCOR-STARTS*/
//                if (empty($securityUserId)) {
//                    $securityUserId = $this->controller->paramsDecode($encodedParam)['security_user_id'];
//                }
//                /*POCOR-ENDS*/
//
//            }
//            if (!empty($encodedParam)) {
//                //POCOR-6202 start
//                if ($action == 'GuardianStudents') {
//                    $userInfo = TableRegistry::get('student_guardians')->get($securityUserId);
//                } else if ($action == 'StudentGuardians') {
//                    $securityUserId = $this->controller->paramsDecode($this->request->params['pass'][1]);
//                    $userInfo = TableRegistry::get('Student.StudentGuardians')->get($securityUserId);//POCOR-6453 ends
//                    $securityUserId = $userInfo->guardian_id;
//                    $userInfo = TableRegistry::get('Security.Users')->get($securityUserId);//POCOR-6453 ends
//                } else if ($action == 'Identities') {//POCOR-6453 starts
//                    $securityUserId = $this->controller->paramsDecode($this->request->query['queryString']);
//                    $userInfo = TableRegistry::get('Security.Users')->get($securityUserId);//POCOR-6453 ends
//                } /*POCOR-6286 : added condition to get selected student id */
//                elseif ($action == 'StudentProfiles') {
//                    $userId = $this->controller->paramsDecode($this->request->params['pass'][1])['student_id'];
//                    $userInfo = TableRegistry::get('Security.Users')->get($userId);
//                } //Start POCOR-7055
//                elseif ($action == 'StudentReportCards') {
//                    $userId = $this->controller->paramsDecode($this->request->params['pass'][1])['student_id'];
//                    $userInfo = TableRegistry::get('Security.Users')->get($userId);
//                }//End POCOR-7055
//                /*POCOR-6286 ends*/
//                // Start POCOR-7384
//                elseif ($this->request->params['plugin'] == 'Directory' && $this->request->params['controller'] == 'Directories' && $this->request->params['pass'][0] == 'download' && $action == 'Attachments') {
//                    $userId = $this->controller->paramsDecode($this->request->params['pass'][2])['security_user_id'];
//                    $userInfo = TableRegistry::get('Security.Users')->get($userId);
//                } // End POCOR-7384
//                else {
//                    $this->log('navigation', 'debug');
//                    $this->log($securityUserId, 'debug');
//                    try {
//                        $related = TableRegistry::get('Security.Users')->get($securityUserId);
//                        $userInfo = $related;
//                    } catch (RecordNotFoundException $e) {
//                        $userInfo = null;
//                    }
//                }
//                //POCOR-6202 end
//            }
//
//            $userType = '';
//            if (!empty($userInfo)) {
//                if ($userInfo->is_student && $userInfo->is_staff == 0 && $userInfo->is_guardian == 0) {
//                    $userType = 1;
//                } elseif ($userInfo->is_staff && $userInfo->is_student == 0 && $userInfo->is_guardian == 0) {
//                    $userType = 2;
//                } elseif ($userInfo->is_guardian && $userInfo->is_staff == 0 && $userInfo->is_student == 0) {
//                    $userType = 3;
//                } elseif ($userInfo->is_student == 1 && $userInfo->is_staff == 1 && $userInfo->is_guardian == 1) {
//                    $userType = 4; //superrole user
//                } elseif ($userInfo->is_student == 1 && $userInfo->is_staff == 1 && $userInfo->is_guardian == 0) {
//                    $userType = 5;
//                } /*POCOR-6332 starts*/ elseif ($userInfo->is_student == 1 && $userInfo->is_staff == 0 && $userInfo->is_guardian == 1) {
//                    $userType = 6;
//                } elseif ($userInfo->is_student == 0 && $userInfo->is_staff == 1 && $userInfo->is_guardian == 1) {
//                    $userType = 7;
//                }/*POCOR-6332 ends*/
//            }
//
//
//            $userType = '';
//            if (!empty($userInfo)) {
//                if ($userInfo->is_student) {
//                    $userType = 1;
//                } elseif ($userInfo->is_staff) {
//                    $userType = 2;
//                } elseif ($userInfo->is_guardian) {
//                    $userType = 3;
//                }
//            }
            $session = $this->request->session();
            $isStudent = $session->read('Directory.Directories.is_student');
            $isStaff = $session->read('Directory.Directories.is_staff');
            $isGuardian = $session->read('Directory.Directories.is_guardian');

            // POCOR-6372 (start) initially here userType was checking but it did not work for directory navigation so changed with roles
            if ($isStaff) {
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStaffNavigation());
                $session->write('Directory.Directories.reload', true);
            }

            if ($isStudent) {
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStudentNavigation());
                $session->write('Directory.Directories.reload', true);
            }

            if ($isGuardian) {
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryGuardianNavigation());
                $session->write('Directory.Directories.reload', true);
            }

            if ($isStudent && $isStaff && $isGuardian) {
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStudentNavigation());
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStaffNavigation());
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryGuardianNavigation());
                $session->write('Directory.Directories.reload', true);
            }

            if ($isStudent && $isStaff && !$isGuardian) {
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStudentNavigation());
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStaffNavigation());
                $session->write('Directory.Directories.reload', true);
            }
            /*POCOR-6332 starts*/
            if ($isStudent && !$isStaff && $isGuardian) {
                $session->write('Directory.Directories.reload', true);
            }
            if (!$isStudent && $isStaff && $isGuardian) {
                // POCOR-6372 code for showing staff section 
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStaffNavigation());
                // POCOR-6372 code for showing staff section 
                $session->write('Directory.Directories.reload', true);
            }
            // POCOR-6372 (end) initially here userType was checking but it did not work for directory navigation so changed with roles
            /*POCOR-6332 ends*/
        } elseif (($controller->name == 'Profiles' && $action != 'index') || in_array($controller->name, $profileControllers)) {
            $navigations = $this->appendNavigation('Profiles.Profiles', $navigations, $this->getProfileNavigation());
            $navigations = $this->appendNavigation('Profiles.Personal', $navigations, $this->getProfileNavigation());

            $session = $this->request->session();
            $isStudent = $session->read('Auth.User.is_student');
            $isStaff = $session->read('Auth.User.is_staff');
            $isGuardian = $session->read('Auth.User.is_guardian');

            if ($isStaff) {
                $navigations = $this->appendNavigation('Profiles.Profiles.view', $navigations, $this->getProfileStaffNavigation());
                $session->write('Profile.Profiles.reload', true);
            }

            if ($isStudent) {
                $navigations = $this->appendNavigation('Profiles.Profiles.view', $navigations, $this->getProfileStudentNavigation());
                $session->write('Profile.Profiles.reload', true);
            }
        } elseif (($controller->name == 'GuardianNavs' && $action != 'index')) {
            $navigations = $this->appendNavigation('GuardianNavs.GuardianNavs.index', $navigations, $this->getGuardianNavNavigation());
            $this->checkClassification($navigations);
        }

        $navigations = $this->appendNavigation('Reports', $navigations, $this->getReportNavigation());
        $navigations = $this->appendNavigation('Administration', $navigations, $this->getAdministrationNavigation());
        return $navigations;
    }

    private function appendNavigation($key, $originalNavigation, $navigationToAppend)
    {
        $count = 0;
        $columns = $this->array_column($navigationToAppend, 'title');
        $excluded = array_intersect($columns, (array)Configure::read('School.excludedPlugins'));
        $navigationToAppend = array_diff_key($navigationToAppend, $excluded);
        foreach ($originalNavigation as $navigationKey => $navigationValue) {
            $count++;
            if ($navigationKey == $key) {
                break;
            }
        }
        $result = [];
        if ($count < count($originalNavigation)) {
            $result = array_slice($originalNavigation, 0, $count, true) + $navigationToAppend + array_slice($originalNavigation, $count, count($originalNavigation) - 1, true);
        } elseif ($count == count($originalNavigation)) {
            $result = $originalNavigation + $navigationToAppend;
        } else {
            $result = $originalNavigation;
        }
        return $result;
    }

    public function getMainNavigation()
    {
        /*POCOR-6267 Starts*/
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];

        if (isset($uId)) {
            $userInfo = TableRegistry::get('security_users')->get($uId);
            if (!empty($userInfo) && $userInfo->is_guardian == 1) {
                $newNavigation = [
                    'GuardianNavs.GuardianNavs.index' => [
                        'title' => 'Guardian',
                        'icon' => '<span><i class="fa fa-users"></i></span>',
                        'params' => ['plugin' => 'GuardianNav']
                    ],
                ];
            }
        }

        $PersonalNavigation = [
            'Profiles.Personal' => [
                'title' => 'Personal',
                'icon' => '<span><i class="fa kd-role"></i></span>',
                'params' => ['plugin' => 'Profile',
                    'action' => 'Personal', 0 => 'view', $userId]
            ]
        ];

        $navigation = [
            'Institutions.Institutions.index' => [
                'title' => 'Institutions',
                'icon' => '<span><i class="fa kd-institutions"></i></span>',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.add',
                    'Institutions.ImportInstitutions.add',
                    'Institutions.ImportInstitutions.results']
            ],

            'Directories.Directories.index' => [
                'title' => 'Directory',
                'icon' => '<span><i class="fa kd-guardian"></i></span>',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.Directories.add',
                    'Directories.ImportUsers.add',
                    'Directories.ImportUsers.results',
                    'DirectoryHistories.index']
            ],


        ];

        $navigationToAppends = $this->getReportAdminstrationNavigation($uId); //POCOR-7527
        /*POCOR-6267 Starts*/
        if (isset($newNavigation)) {
            $navigation = array_merge($PersonalNavigation, $newNavigation, $navigation, $navigationToAppends);
        } else {
            $navigation = array_merge($PersonalNavigation, $navigation, $navigationToAppends);
        }
        /*POCOR-6267 Ends*/
        return $navigation;
    }

    public function getInstitutionNavigation()
    {
        $session = $this->request->session();
        $id = $this->controller->paramsEncode(['id' => $session->read('Institution.Institutions.id')]);
        $institutionId = isset($this->request->params['institutionId']) ? $this->request->params['institutionId'] : $id;
        $navigation = [
            'Institutions.dashboard' => [
                'title' => 'Dashboard',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.dashboard'],
                'params' => ['plugin' => 'Institution', 0 => $institutionId]
            ],

            'Institution.General' => [
                'title' => 'General',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.Institutions.view' => [
                'title' => 'Overview',
                'parent' => 'Institution.General',
                'selected' => ['Institutions.Institutions.edit',
                    'Institutions.InstitutionStatus.edit',
                    'Institutions.InstitutionStatus.view'],
                'params' => ['plugin' => 'Institution', 0 => $institutionId]
            ],
            'Institutions.InstitutionMaps.view' => [
                'title' => 'Map',
                'parent' => 'Institution.General',
                'selected' => ['Institutions.InstitutionMaps.view',
                    'Institutions.InstitutionMaps.edit'],
                'params' => ['plugin' => 'Institution', 0 => $institutionId]
            ],

            'Institutions.InstitutionCalendars.index' => [
                'title' => 'Calendar',
                'parent' => 'Institution.General',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InstitutionCalendars.view',
                    'Institutions.InstitutionCalendars.add',
                    'Institutions.InstitutionCalendars.edit',
                    'Institutions.InstitutionCalendars.delete']
            ],
            // POCOR-6122

            'Contacts' => [
                'title' => 'Contacts',
                'parent' => 'Institution.General',
                'link' => false
            ],

            'Institutions.Contacts.view' => [
                'title' => 'Institution',
                'parent' => 'Contacts',
                'selected' => ['Institutions.Contacts.view',
                    'Institutions.Contacts.edit'],
                'params' => ['plugin' => 'Institution', 0 => $institutionId]
            ],

            'Institutions.InstitutionContactPersons.index' => [
                'title' => 'People',
                'parent' => 'Contacts',
                'selected' => ['Institutions.InstitutionContactPersons',
                    'Institutions.InstitutionContactPersons.view',
                    'Institutions.InstitutionContactPersons.add',
                    'Institutions.InstitutionContactPersons.edit',
                    'Institutions.InstitutionContactPersons.delete'],
                'params' => ['plugin' => 'Institution', 0 => $institutionId]
            ],

            'Institutions.Attachments.index' => [
                'title' => 'Attachments',
                'parent' => 'Institution.General',
                'selected' => ['Institutions.Attachments'],
                'params' => ['plugin' => 'Institution']
            ],
            /*POCOR-6286 starts*/
            'Profile' => [
                'title' => 'Profiles',
                'parent' => 'Institution.General',
                'link' => false
            ],
            //POCOR-6653 - updated Institutions selected function to get correct page
            'Institutions.InstitutionProfiles' => [
                'title' => 'Institutions',
                'parent' => 'Profile',
                'selected' => ['Institutions.InstitutionProfiles'],
                'params' => ['plugin' => 'Institution'],
            ],
            /*POCOR-6966 starts*/
            'Institutions.ClassesProfiles' => [
                'title' => 'Classes',
                'parent' => 'Profile',
                'selected' => ['Institutions.ClassesProfiles'],
                'params' => ['plugin' => 'Institution'],
            ],/*POCOR-6966 ends*/
            //POCOR-6654 modified staff menu
            'Institutions.StaffProfiles' => [
                'title' => 'Staff',
                'parent' => 'Profile',
                'selected' => ['Institutions.StaffProfiles'],
                'params' => ['plugin' => 'Institution'],
            ],
            //POCOR-6655 modified Studentes nav
            'Institutions.StudentProfiles' => [
                'title' => 'Students',
                'parent' => 'Profile',
                'selected' => ['Institutions.StudentProfiles'],
                'params' => ['plugin' => 'Institution'],
            ],
            /*POCOR-6286 ends*/
            'Institutions.Shifts' => [
                'title' => 'Shifts',
                'parent' => 'Institution.General',
                'selected' => ['Institutions.Shifts'],
                'params' => ['plugin' => 'Institution']

            ],
            'Institution.Academic' => [
                'title' => 'Academic',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.Programmes' => [
                'title' => 'Programmes',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Programmes'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Classes' => [
                'title' => 'Classes',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Classes'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Subjects' => [
                'title' => 'Subjects',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Subjects'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Schedules' => [
                'title' => 'Schedules',
                'parent' => 'Institution.Academic',
                'link' => false
            ],

            'Institutions.ScheduleTimetableOverview' => [
                'title' => 'Timetables',
                'parent' => 'Institutions.Schedules',
                'selected' => ['Institutions.ScheduleTimetableOverview',
                    'Institutions.ScheduleTimetable'],
                'params' => ['plugin' => 'Institution']
            ],
            'Institutions.ScheduleIntervals' => [
                'title' => 'Intervals',
                'parent' => 'Institutions.Schedules',
                'selected' => ['Institutions.ScheduleIntervals'],
                'params' => ['plugin' => 'Institution']
            ],
            'Institutions.ScheduleTerms' => [
                'title' => 'Terms',
                'parent' => 'Institutions.Schedules',
                'selected' => ['Institutions.ScheduleTerms'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Textbooks' => [
                'title' => 'Textbooks',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Textbooks',
                    'Institutions.ImportInstitutionTextbooks'],
                'params' => ['plugin' => 'Institution']
            ],
            'Institutions.Associations' => [
                'title' => 'Houses',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Associations'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.InstitutionCurriculars' => [ //POCOR-6673
                'title' => 'Curriculars',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.InstitutionCurriculars', 'Institutions.InstitutionCurricularStudents'],
                'params' => ['plugin' => 'Institution'],
                'action' => 'index',
            ],

            'Institution.Feeders' => [
                'title' => 'Feeders',
                'parent' => 'Institution.Academic',
                'link' => false
            ],

            'Institutions.FeederOutgoingInstitutions' => [
                'title' => 'Outgoing',
                'parent' => 'Institution.Feeders',
                'selected' => ['Institutions.FeederOutgoingInstitutions'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.FeederIncomingInstitutions' => [
                'title' => 'Incoming',
                'parent' => 'Institution.Feeders',
                'selected' => ['Institutions.FeederIncomingInstitutions'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Students.index' => [
                'title' => 'Students',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Students.add',
                    'Institutions.Students.addExisting',
                    'Institutions.Promotion',
                    'Institutions.Transfer',
                    'Institutions.Undo',
                    'Institutions.StudentAdmission',
                    'Institutions.StudentTransferIn',
                    'Institutions.StudentTransferOut',
                    'Institutions.StudentWithdraw',
                    'Institutions.WithdrawRequests',
                    'Institutions.StudentUser.add',
                    'Institutions.ImportStudentAdmission',
                    'Institutions.Students', 'StudentHistories.index',
                    'Institutions.BulkStudentAdmission',
                    'Institutions.ImportStudentBodyMasses',
                    'Institutions.ImportStudentGuardians',
                    'Institutions.StudentStatusUpdates', 'Institutions.ImportStudentExtracurriculars',
                    'Institutions.BulkStudentTransferIn',
                    'Institutions.BulkStudentTransferOut'], // POCOR-7555
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Staff.index' => [
                'title' => 'Staff',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Staff.add',
                    'Institutions.StaffUser.add',
                    'Institutions.StaffUser.pull',
                    'Institutions.ImportStaff',
                    'Institutions.ImportStaffSalaries',
                    'Institutions.Staff',
                    'Institutions.StaffTransferIn',
                    'Institutions.StaffTransferOut',
                    'StaffHistories.index', 'Staff.StaffCurriculars',]
            ],

            'Institution.Attendance' => [
                'title' => 'Attendance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.StudentAttendances.index' => [
                'title' => 'Students',
                'parent' => 'Institution.Attendance',
                'selected' => ['Institutions.StudentAttendances',
                    'Institutions.StudentAbsences',
                    'Institutions.ImportStudentAttendances',
                    'Institutions.StudentArchive',
                    'Institutions.InstitutionStudentAbsencesArchived'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.InstitutionStaffAttendances.index' => [
                'title' => 'Staff',
                'parent' => 'Institution.Attendance',
                'selected' => ['Institutions.InstitutionStaffAttendances',
                    'Institutions.ImportStaffAttendances',
                    'Institutions.StaffAttendancesArchived'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institution.Behaviour' => [
                'title' => 'Behaviour',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.StudentBehaviours.index' => [
                'title' => 'Students',
                'parent' => 'Institution.Behaviour',
                'selected' => ['Institutions.StudentBehaviours',
                    'StudentBehaviourAttachments.index',
                    'StudentBehaviourAttachments.view',
                    'StudentBehaviourAttachments.add',
                    'StudentBehaviourAttachments.edit',
                    'StudentBehaviourAttachments.delete'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.StaffBehaviours.index' => [
                'title' => 'Staff',
                'parent' => 'Institution.Behaviour',
                'selected' => ['Institutions.StaffBehaviours',
                    'StaffBehaviourAttachments.index',
                    'StaffBehaviourAttachments.view',
                    'StaffBehaviourAttachments.add',
                    'StaffBehaviourAttachments.edit',
                    'StaffBehaviourAttachments.delete'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institution.Performance' => [
                'title' => 'Performance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.StudentCompetencies' => [
                'title' => 'Competencies',
                'parent' => 'Institution.Performance',
                'selected' => ['Institutions.StudentCompetencies',
                    'Institutions.InstitutionCompetencyResults',
                    'Institutions.StudentCompetencyComments',
                    'Institutions.ImportCompetencyResults.add',
                    'Institutions.ImportCompetencyResults.results'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.StudentOutcomes' => [
                'title' => 'Outcomes',
                'parent' => 'Institution.Performance',
                'selected' => ['Institutions.StudentOutcomes',
                    'Institutions.ImportOutcomeResults.add',
                    'Institutions.ImportOutcomeResults.results'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Assessments.index' => [
                'title' => 'Assessments',
                'parent' => 'Institution.Performance',
                'selected' => ['Institutions.Assessments',
                    'Institutions.Results',
                    'Institutions.AssessmentArchives',
                    'Institutions.ImportAssessmentItemResults.add',
                    'Institutions.ImportAssessmentItemResults.results',
                    'Institutions.AssessmentItemResultsArchived',
                    'Institutions.reportCardGenerate'],
                'params' => ['plugin' => 'Institution'],
            ],

            'Institutions.ReportCardStatuses' => [
                'title' => 'Report Cards',
                'parent' => 'Institution.Performance',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.ReportCardStatuses',
                    'Institutions.ReportCardStatusProgress'],
            ],

            'Institutions.Risks.index' => [
                'title' => 'Risks',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Risks', 'Institutions.InstitutionStudentRisks'],
                'params' => ['plugin' => 'Institution'],
            ],

            'Institutions.Examinations' => [
                'title' => 'Examinations',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.Exams' => [
                'title' => 'Exams',
                'parent' => 'Institutions.Examinations',
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.ExaminationStudents' => [
                'title' => 'Students',
                'parent' => 'Institutions.Examinations',
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.ExaminationResults' => [
                'title' => 'Results',
                'parent' => 'Institutions.Examinations',
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.ReportCards' => [
                'title' => 'Report Cards',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.ReportCardComments' => [
                'title' => 'Comments',
                'parent' => 'Institutions.ReportCards',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.ReportCardComments', 'Institutions.Comments'],
            ],

            'Institutions.Appointment' => [
                'title' => 'Appointments',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.Positions' => [
                'title' => 'Positions',
                'parent' => 'Institutions.Appointment',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Positions',
                    'Institutions.ImportInstitutionPositions'],
            ],
            'Institutions.StaffDuties' => [
                'title' => 'Duties',
                'parent' => 'Institutions.Appointment',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.StaffDuties'],
            ],

            'Institution.Finance' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],
            //POCOR-6160 start 
            'Institutions.BankAccounts' => [
                'title' => 'Bank Accounts',
                'parent' => 'Institution.Finance',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.BankAccounts'],
            ],
            //POCOR-6160 end
            'Institutions.Budget' => [
                'title' => 'Budget',
                'parent' => 'Institution.Finance',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Budget'],
            ],

            'Institutions.Income' => [
                'title' => 'Income',
                'parent' => 'Institution.Finance',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Income'],
            ],

            'Institutions.Expenditure' => [
                'title' => 'Expenditure',
                'parent' => 'Institution.Finance',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Expenditure'],
            ],

            'Institutions.Fees' => [
                'title' => 'Institution Fees',
                'parent' => 'Institution.Finance',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Fees'],
            ],

            'Institutions.StudentFees' => [
                'title' => 'Student Fees',
                'parent' => 'Institution.Finance',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.StudentFees'],
            ],

            'Infrastructures' => [
                'title' => 'Infrastructures',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.InstitutionLands' => [
                'title' => 'Overview',
                'parent' => 'Infrastructures',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InstitutionLands',
                    'Institutions.InstitutionBuildings',
                    'Institutions.InstitutionFloors',
                    'Institutions.InstitutionRooms']
            ],

            // POCOR-6150 start
            'Institutions.InfrastructureNeeds' => [
                'title' => 'Needs',
                'parent' => 'Infrastructures',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['InfrastructureNeeds', 'Institutions.InfrastructureNeeds.view',
                    'Institutions.InfrastructureNeeds.add',
                    'Institutions.InfrastructureNeeds.edit',
                    'Institutions.InfrastructureNeeds.delete']
            ],
            // POCOR-6150 end

            // POCOR-6151
            'Institutions.InfrastructureProjects' => [
                'title' => 'Projects',
                'parent' => 'Infrastructures',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['InfrastructureProjects' . 'Institutions.InfrastructureProjects.view',
                    'Institutions.InfrastructureProjects.add',
                    'Institutions.InfrastructureProjects.edit',
                    'Institutions.InfrastructureProjects.delete']
            ],
            // POCOR-6151

            'Wash' => [
                'title' => 'WASH',
                'parent' => 'Infrastructures',
                'link' => false
            ],

            'Institutions.InfrastructureWashWaters.index' => [
                'title' => 'Water',
                'parent' => 'Wash',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InfrastructureWashWaters.view',
                    'Institutions.InfrastructureWashWaters.add',
                    'Institutions.InfrastructureWashWaters.edit',
                    'Institutions.InfrastructureWashWaters.delete']
            ],

            'Institutions.InfrastructureWashSanitations.index' => [
                'title' => 'Sanitation',
                'parent' => 'Wash',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InfrastructureWashSanitations.view',
                    'Institutions.InfrastructureWashSanitations.add',
                    'Institutions.InfrastructureWashSanitations.edit',
                    'Institutions.InfrastructureWashSanitations.delete']
            ],

            'Institutions.InfrastructureWashHygienes.index' => [
                'title' => 'Hygiene',
                'parent' => 'Wash',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InfrastructureWashHygienes.view',
                    'Institutions.InfrastructureWashHygienes.add',
                    'Institutions.InfrastructureWashHygienes.edit',
                    'Institutions.InfrastructureWashHygienes.delete']
            ],

            'Institutions.InfrastructureWashWastes.index' => [
                'title' => 'Waste',
                'parent' => 'Wash',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InfrastructureWashWastes.view',
                    'Institutions.InfrastructureWashWastes.add',
                    'Institutions.InfrastructureWashWastes.edit',
                    'Institutions.InfrastructureWashWastes.delete']
            ],

            'Institutions.InfrastructureWashSewages.index' => [
                'title' => 'Sewage',
                'parent' => 'Wash',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InfrastructureWashSewages.view',
                    'Institutions.InfrastructureWashSewages.add',
                    'Institutions.InfrastructureWashSewages.edit',
                    'Institutions.InfrastructureWashSewages.delete']
            ],

            'Utilities' => [
                'title' => 'Utilities',
                'parent' => 'Infrastructures',
                'link' => false
            ],
            'Institutions.InfrastructureUtilityElectricities.index' => [
                'title' => 'Electricity',
                'parent' => 'Utilities',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InfrastructureUtilityElectricities.view',
                    'Institutions.InfrastructureUtilityElectricities.add',
                    'Institutions.InfrastructureUtilityElectricities.edit',
                    'Institutions.InfrastructureUtilityElectricities.delete']
            ],

            'Institutions.InfrastructureUtilityInternets.index' => [
                'title' => 'Internet',
                'parent' => 'Utilities',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InfrastructureUtilityInternets.view',
                    'Institutions.InfrastructureUtilityInternets.add',
                    'Institutions.InfrastructureUtilityInternets.edit',
                    'Institutions.InfrastructureUtilityInternets.delete']
            ],

            'InfrastructureUtilityTelephones.index' => [
                'title' => 'Telephone',
                'parent' => 'Utilities',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['InfrastructureUtilityTelephones.view',
                    'InfrastructureUtilityTelephones.add',
                    'InfrastructureUtilityTelephones.edit',
                    'InfrastructureUtilityTelephones.delete']
            ],

            // POCOR-6152
            'Institutions.InstitutionAssets' => [
                'title' => 'Assets',
                'parent' => 'Infrastructures',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InstitutionAssets', 'Institutions.InstitutionAssets.view',
                    'Institutions.InstitutionAssets.add',
                    'Institutions.InstitutionAssets.edit',
                    'Institutions.InstitutionAssets.delete'],
            ],
            // POCOR-6152

            'Meals' => [
                'title' => 'Meals',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.Distributions' => [
                'title' => 'Distributions',
                'parent' => 'Meals',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Distributions']
            ],

            'Institutions.StudentMeals.index' => [
                'title' => 'Students',
                'parent' => 'Meals',
                'selected' => ['Institutions.StudentMeals', 'Institutions.ImportStudentMeals'],
                'params' => ['plugin' => 'Institution']
            ],

            'Survey' => [
                'title' => 'Survey',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],
            'Institutions.Surveys' => [
                'title' => 'Forms',
                'parent' => 'Survey',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Surveys',
                    'Institutions.ImportInstitutionSurveys'],
            ],

            'Institutions.Rubrics' => [
                'title' => 'Rubrics',
                'parent' => 'Survey',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Rubrics',
                    'Institutions.RubricAnswers'],
            ],

            'Institutions.VisitRequests' => [
                'title' => 'Visits',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.VisitRequests',
                    'Institutions.Visits']
            ],

            'Institutions.Transport' => [
                'title' => 'Transport',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.InstitutionTransportProviders.index' => [
                'title' => 'Providers',
                'parent' => 'Institutions.Transport',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InstitutionTransportProviders.add',
                    'Institutions.InstitutionTransportProviders.edit',
                    'Institutions.InstitutionTransportProviders.view',
                    'Institutions.InstitutionTransportProviders.delete']
            ],

            'Institutions.InstitutionBuses.index' => [
                'title' => 'Buses',
                'parent' => 'Institutions.Transport',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InstitutionBuses', 'Institutions.InstitutionBuses.add',
                    'Institutions.InstitutionBuses.edit',
                    'Institutions.InstitutionBuses.view',
                    'Institutions.InstitutionBuses.delete']
            ],

            // POCOR-6169
            'Institutions.InstitutionTrips.index' => [
                'title' => 'Trips',
                'parent' => 'Institutions.Transport',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InstitutionTrips', 'Institutions.InstitutionTrips.add',
                    'Institutions.InstitutionTrips.edit',
                    'Institutions.InstitutionTrips.view',
                    'Institutions.InstitutionTrips.delete']
            ],
            // POCOR-6169

            'Institutions.Cases' => [
                'title' => 'Cases',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution']
            ],
            'Institutions.Committees' => [
                'title' => 'Committees',
                'parent' => 'Institutions.Institutions.index',
                //'selected' => ['Institutions.Committees','InstitutionCommitteeAttachments.add', 'InstitutionCommitteeAttachments.edit', 'InstitutionCommitteeAttachments.view', 'InstitutionCommitteeAttachments.index','InstitutionCommitteeAttachments.delete'],
                'selected' => ['Institutions.Committees', 'Institutions.CommitteeAttachments'],
                'params' => ['plugin' => 'Institution']
            ],
            /*
            'Institutions.InstitutionStatistics' => [
                    'title' => 'Statistics',
                    'parent' => 'Institutions.Institutions.index',
                    'params' => ['plugin' => 'Institution', 0 => $institutionId],
                    'selected' => ['Institutions.InstitutionStatistics.index', 'Institutions.InstitutionStatistics.view', 'Institutions.InstitutionStatistics.edit', 'Institutions.InstitutionStatistics.remove', 'Institutions.InstitutionStatistics.download', 'Institutions.InstitutionStatistics.excel']
                ]
            */
            'Statistics' => [
                'title' => 'Statistics',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],
            'Institutions.InstitutionStandards' => [
                'title' => 'Standard',
                'parent' => 'Statistics',
                'params' => ['plugin' => 'Institution', 3 => $institutionId],
                'selected' => ['Institutions.ViewReport']
            ],
            'Institutions.InstitutionStatistics' => [
                'title' => 'Custom',
                'parent' => 'Statistics',
                'params' => ['plugin' => 'Institution', 0 => $institutionId],
                'selected' => [
                    'Institutions.InstitutionStatistics.index',
                    'Institutions.InstitutionStatistics.view',
                    'Institutions.InstitutionStatistics.edit',
                    'Institutions.InstitutionStatistics.remove',
                    'Institutions.InstitutionStatistics.download',
                    'Institutions.InstitutionStatistics.excel'
                ]
            ],
        ];

        foreach ($navigation as &$n) {
            if (isset($n['params'])) {
                $n['params']['institutionId'] = $institutionId;
            }
        }

        return $navigation;
    }

    public function getInstitutionStudentNavigation()
    {
        $session = $this->request->session();
        $id = !empty($this->controller->getQueryString('institution_student_id')) ? $this->controller->getQueryString('institution_student_id') : $session->read('Institution.Students.id');
        $studentId = $session->read('Student.Students.id');
        $institutionIdSession = $this->controller->paramsEncode(['id' => $session->read('Institution.Institutions.id')]);
        $institutionId = isset($this->request->params['institutionId']) ? $this->request->params['institutionId'] : $institutionIdSession;
        $queryString = $this->controller->paramsEncode(['institution_id' => $this->controller->paramsDecode($institutionId)['id'], 'institution_student_id' => $id]);
        $navigation = [
            'Institutions.StudentUser.view' => [
                'title' => 'General',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Institution',
                    '1' => $this->controller->paramsEncode(['id' => $studentId]), 'queryString' => $queryString],
                'selected' => [
                    'Institutions.StudentUser.edit',
                    'Institutions.StudentAccount.view',
                    'Institutions.StudentAccount.edit',
                    'Institutions.StudentSurveys',
                    'Institutions.StudentSurveys.edit',
                    'Institutions.IndividualPromotion',
                    'Students.Identities',
                    'Students.Nationalities',
                    'Students.Contacts',
                    'Students.Guardians',
                    'Students.Languages',
                    'Students.Attachments',
                    'Students.Comments',
                    'Students.History',
                    'Students.GuardianUser',
                    'Institutions.StudentUser.pull',
                    'StudentComments.index',
                    'StudentComments.view',
                    'StudentComments.add',
                    'StudentComments.edit',
                    'StudentComments.delete',
                    'Students.StudentTransport',
                    'Students.Demographic',
                    'Guardians.Accounts',
                    'Guardians.Demographic',
                    'Guardians.Identities',
                    'Guardians.Nationalities',
                    'Guardians.Contacts',
                    'Guardians.Languages',
                    'Guardians.Attachments',
                    'GuardianComments.index',
                    'GuardianComments.view',
                    'GuardianComments.add',
                    'GuardianComments.edit',
                    'GuardianComments.delete',
                    'Institutions.Addguardian',
                ]
            ],
            'Institutions.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Students.Classes',
                    'Students.Subjects',
                    'Students.Absences',
                    'Students.ArchivedAbsences',
                    'Students.Behaviours',
                    //POCOR-7474-HINDOL TYPO FIX
                    'Students.Assessments',
                    'Students.AssessmentsArchived',
                    'Students.ExaminationResults',
                    'Students.ReportCards',
                    'Students.Awards', //POCOR-5786 replace results to Assessments
                    'Students.Extracurriculars',
                    'Institutions.StudentTextbooks',
                    'Institutions.Students.view',
                    'Institutions.Students.edit',
                    'Institutions.StudentRisks',
                    'Students.Outcomes',
                    'Institutions.StudentProgrammes.view',
                    'Institutions.StudentProgrammes.edit',
                    'Students.Competencies',
                    'Students.AssessmentItemResultsArchived',
                    'Students.InstitutionStudentAbsencesArchived',
                    'Institutions.StudentTransition',
                    'Institutions.Associations', 'Institutions.StudentAssociations', 'Institutions.StudentCurriculars']
            ],
            'Students.StudentScheduleTimetable' => [
                'title' => 'Timetables',
                'parent' => 'Institutions.Students.index',
                'selected' => ['Students.StudentScheduleTimetable'],
                'params' => ['plugin' => 'Student']
            ],
            'Students.Employments' => [
                'title' => 'Professional',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.Employments',
                    'Students.Qualifications',
                    'Students.Licenses']//POCOR-7528
            ],
            'Counsellings.index' => [
                'title' => 'Counselling',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Counsellings.add',
                    'Counsellings.edit',
                    'Counsellings.view',
                    'Counsellings.delete']
            ],
            'Students.BankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.StudentFees']
            ],
            'Students.Healths' => [
                'title' => 'Health',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.Healths',
                    'Students.HealthAllergies',
                    'Students.HealthConsultations',
                    'Students.HealthFamilies',
                    'Students.HealthHistories',
                    'Students.HealthImmunizations',
                    'Students.HealthMedications',
                    'Students.HealthTests',
                    'Students.StudentBodyMasses',
                    'Students.StudentBodyMasses.add',
                    'Students.StudentBodyMasses.edit',
                    'Students.StudentBodyMasses.view',
                    'Students.StudentBodyMasses.delete',
                    'Students.StudentInsurances.add',
                    'Students.StudentInsurances.view',
                    'Students.StudentInsurances.edit',
                    'Students.StudentInsurances.delete',
                    'Students.StudentInsurances']
                // 'selected' => ['Students.Healths', 'Students.HealthAllergies', 'Students.HealthConsultations', 'Students.HealthFamilies', 'Students.HealthHistories', 'Students.HealthImmunizations', 'Students.HealthMedications', 'Students.HealthTests', 'StudentBodyMasses.index', 'StudentBodyMasses.add', 'StudentBodyMasses.edit', 'StudentBodyMasses.view', 'StudentBodyMasses.delete', 'StudentInsurances.add', 'StudentInsurances.view', 'StudentInsurances.edit', 'StudentInsurances.delete', 'StudentInsurances.index']
            ],
            'Students.SpecialNeedsReferrals' => [
                'title' => 'Special Needs',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.SpecialNeedsReferrals',
                    'Students.SpecialNeedsAssessments',
                    'Students.SpecialNeedsServices',
                    'Students.SpecialNeedsDevices',
                    'Students.SpecialNeedsPlans',
                    'Students.SpecialNeedsDiagnostics']
            ],
            'Students.StudentVisitRequests' => [
                'title' => 'Visits',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.StudentVisitRequests',
                    'Students.StudentVisits']
            ],
            'Students.Meals' => [
                'title' => 'Meals',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.Meals']
            ],
            'Students.Profiles' => [
                'title' => 'Profiles',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.Profiles']
            ],

        ];
        foreach ($navigation as &$n) {
            if (isset($n['params'])) {
                $n['params']['institutionId'] = $institutionId;
            }
        }
        return $navigation;
    }

    public function getInstitutionStaffNavigation()
    {
        $session = $this->request->session();
        $institutionIdSession = $this->controller->paramsEncode(['id' => $session->read('Institution.Institutions.id')]);
        $institutionId = isset($this->request->params['institutionId']) ? $this->request->params['institutionId'] : $institutionIdSession;
        $id = $session->read('Staff.Staff.id');
        $navigation = [
            'Institutions.StaffUser.view' => [
                'title' => 'General',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Institution',
                    '1' => $this->controller->paramsEncode(['id' => $id])],
                'selected' => ['Institutions.StaffUser.edit',
                    'Institutions.StaffAccount',
                    'Staff.Identities',
                    'Staff.Nationalities',
                    'Staff.Contacts',
                    'Staff.Guardians',
                    'Staff.Languages',
                    'Staff.Attachments',
                    'StaffComments.index',
                    'StaffComments.view',
                    'StaffComments.add',
                    'StaffComments.edit',
                    'StaffComments.delete',
                    'Staff.History',
                    'Staff.Demographic']
            ],
            'Staff.EmploymentStatuses' => [
                'title' => 'Career',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.EmploymentStatuses',
                    'Staff.Positions',
                    'Staff.HistoricalStaffPositions',
                    'Staff.Classes',
                    'Staff.Subjects',
                    'Staff.Absences',
                    'Staff.StaffAttendances',
                    'Staff.ArchivedAttendances',
                    'Staff.InstitutionStaffAttendanceActivities',
                    'Institutions.StaffLeave',
                    'Institutions.ArchivedStaffLeave',
                    'Institutions.HistoricalStaffLeave',
                    'Staff.Behaviours',
                    'Institutions.Staff.edit',
                    'Institutions.Staff.view',
                    'Institutions.StaffPositionProfiles.add',
                    'Institutions.StaffAppraisals',
                    'Institutions.ImportStaffLeave',
                    'Staff.Duties',
                    'Staff.StaffAssociations',
                    'Staff.StaffCurriculars'],
            ],
            'Staff.Employments' => [
                'title' => 'Professional',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.Employments',
                    'Staff.Qualifications',
                    'Staff.Extracurriculars',
                    'Staff.Memberships',
                    'Staff.Licenses',
                    'Staff.Awards'],
            ],
            'Staff.BankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.BankAccounts',
                    'Staff.Salaries',
                    'Staff.ImportSalaries',
                    'Staff.Payslips']
            ],
            'Institutions.StaffTrainingNeeds' => [
                'title' => 'Training',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.StaffTrainingNeeds',
                    'Institutions.StaffTrainingApplications',
                    'Institutions.StaffTrainingResults',
                    'Institutions.CourseCatalogue',
                    'Staff.Courses'],
            ],
            'Staff.ScheduleTimetable' => [
                'title' => 'Timetables',
                'parent' => 'Institutions.Staff.index',
                'selected' => ['Staff.ScheduleTimetable'],
                'params' => ['plugin' => 'Staff']
            ],
            /*POCOR-6311 Starts added StaffInsurances functions for Staff Health nav*/
            'Staff.Healths' => [
                'title' => 'Health',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.Healths',
                    'Staff.HealthAllergies',
                    'Staff.HealthConsultations',
                    'Staff.HealthFamilies',
                    'Staff.HealthHistories',
                    'Staff.HealthImmunizations',
                    'Staff.HealthMedications',
                    'Staff.HealthTests',
                    'Staff.StaffBodyMasses',
                    'Staff.StaffInsurances',
                    'StaffInsurances.add',
                    'StaffInsurances.view',
                    'StaffInsurances.edit',
                    'StaffInsurances.delete',
                    'StaffInsurances.index']
            ],
            'Staff.SpecialNeedsReferrals' => [
                'title' => 'Special Needs',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.SpecialNeedsReferrals',
                    'Staff.SpecialNeedsAssessments',
                    'Staff.SpecialNeedsServices',
                    'Staff.SpecialNeedsDevices',
                    'Staff.SpecialNeedsPlans']
            ],
            'Staff.Profiles.index' => [
                'title' => 'Profiles',
                'parent' => 'Institutions.Staff.index',
                'selected' => ['Staff.Profiles'],
                'params' => ['plugin' => 'Staff']
            ],
        ];
        foreach ($navigation as &$n) {
            if (isset($n['params'])) {
                $n['params']['institutionId'] = $institutionId;
            }
        }
        return $navigation;
    }

    public function getProfileNavigation()
    {
        //POCOR-5886 starts
        $session = $this->request->session();
        $profileUserId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        //POCOR-5886 ends
        $navigation = [
            'Profiles.Profiles.view' => [
                'title' => 'General',
                'parent' => 'Profiles.Personal',
                //POCOR-5886 starts
                'params' => ['plugin' => 'Profile',
                    'action' => 'Personal', 0 => $profileUserId],//POCOR-5886 ends
                'selected' => ['Profiles.Personal.view',
                    'Profiles.Personal.edit',
                    'Profiles.Personal.pull',
                    'Profiles.Accounts',
                    'Profiles.Demographic',
                    'Profiles.Identities',
                    'Profiles.Nationalities',
                    'Profiles.Languages',
                    'Profiles.Comments',
                    'Profiles.Attachments',
                    'Profiles.History',
                    'Profiles.Contacts'] // POCOR-6683
            ],
            'Profiles.Healths' => [
                'title' => 'Health',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.Healths',
                    'Profiles.HealthAllergies',
                    'Profiles.HealthConsultations',
                    'Profiles.HealthFamilies',
                    'Profiles.HealthHistories',
                    'Profiles.HealthImmunizations',
                    'Profiles.HealthMedications',
                    'Profiles.HealthTests',
                    'ProfileBodyMasses.index',
                    'ProfileBodyMasses.add',
                    'ProfileBodyMasses.edit',
                    'ProfileBodyMasses.view',
                    'ProfileBodyMasses.delete',
                    'ProfileInsurances.index',
                    'ProfileInsurances.add',
                    'ProfileInsurances.edit',
                    'ProfileInsurances.view',
                    'ProfileInsurances.delete']
            ],
            'Profiles.Employments' => [
                'title' => 'Professional',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.Employments',
                    'Profiles.StaffQualifications',
                    'Profiles.StaffExtracurriculars',
                    'Profiles.StaffMemberships',
                    'Profiles.StaffLicenses',
                    'Profiles.StaffAwards']
            ],
            //POCOR-7439 start
            'Profiles.Cases' => [
                'title' => 'Cases',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],

            ],
            //POCOR-7439 end
            'Profiles.SpecialNeedsReferrals' => [
                'title' => 'Special Needs',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.SpecialNeedsReferrals',
                    'Profiles.SpecialNeedsAssessments',
                    'Profiles.SpecialNeedsServices',
                    'Profiles.SpecialNeedsDevices',
                    'Profiles.SpecialNeedsPlans']
            ],
            'Profiles.ScholarshipApplications' => [
                'title' => 'Scholarships',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.ScholarshipApplications',
                    'ScholarshipsDirectory.index',
                    'ScholarshipsDirectory.view',
                    'ProfileApplicationInstitutionChoices.index',
                    'ProfileApplicationInstitutionChoices.view',
                    'ProfileApplicationInstitutionChoices.add',
                    'ProfileApplicationInstitutionChoices.edit',
                    'ProfileApplicationInstitutionChoices.delete',
                    'ProfileApplicationAttachments.index',
                    'ProfileApplicationAttachments.view',
                    'ProfileApplicationAttachments.add',
                    'ProfileApplicationAttachments.edit',
                    'ProfileApplicationAttachments.delete']
            ],
        ];
        return $navigation;
    }

    public function getDirectoryNavigation()
    {
        //POCOR-5886 starts
        $session = $this->request->session();
        $directorUserId = $this->controller->paramsEncode(['id' => $session->read('Directory.Directories.id')]);
        //POCOR-5886 ends
        $navigation = [
            'Directories.Directories.view' => [
                'title' => 'General',
                'parent' => 'Directories.Directories.index',
                //POCOR-5886 starts
                'params' => ['plugin' => 'Directory',
                    'action' => 'Directories', 0 => $directorUserId],//POCOR-5886 ends
                'selected' => ['Directories.Directories.view',
                    'Directories.Directories.edit',
                    'Directories.Directories.pull',
                    'Directories.Accounts',
                    'Directories.Identities',
                    'Directories.Nationalities',
                    'Directories.Languages',
                    'DirectoryComments.index',
                    'DirectoryComments.view',
                    'DirectoryComments.add',
                    'DirectoryComments.edit',
                    'DirectoryComments.delete',
                    'Directories.Attachments',
                    'Directories.History',
                    'Directories.Contacts',
                    'Directories.Demographic']
            ],
            'Directories.Healths' => [
                'title' => 'Health',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.Healths',
                    'Directories.HealthAllergies',
                    'Directories.HealthConsultations',
                    'Directories.HealthFamilies',
                    'Directories.HealthHistories',
                    'Directories.HealthImmunizations',
                    'Directories.HealthMedications',
                    'Directories.HealthTests',
                    'DirectoryBodyMasses.index',
                    'DirectoryBodyMasses.add',
                    'DirectoryBodyMasses.edit',
                    'DirectoryBodyMasses.view',
                    'DirectoryBodyMasses.delete',
                    'DirectoryInsurances.index',
                    'DirectoryInsurances.add',
                    'DirectoryInsurances.edit',
                    'DirectoryInsurances.delete', 'DirectoryInsurances.view']
            ],
            'Directories.Employments' => [
                'title' => 'Professional',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.Employments',
                    'Directories.StaffQualifications',
                    'Directories.StaffExtracurriculars',
                    'Directories.StaffMemberships',
                    'Directories.StaffLicenses',
                    'Directories.StudentLicenses',//POCOR-7528
                    'Directories.StaffAwards']
            ],

            'Directories.SpecialNeedsReferrals' => [
                'title' => 'Special Needs',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.SpecialNeedsReferrals',
                    'Directories.SpecialNeedsAssessments',
                    'Directories.SpecialNeedsServices',
                    'Directories.SpecialNeedsDevices',
                    'Directories.SpecialNeedsPlans']
            ]
        ];
        //POCOR-7366 start
        if ($session->read('Directory.Directories.is_student') == 1) {
            $newNavigation = ['Directories.Counsellings' => [
                'title' => 'Counsellings',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.Counsellings']
            ]];
            $i = array_search('Directories.Employments', array_keys($navigation));
            $navigation = array_merge(array_slice($navigation, 0, $i + 1), $newNavigation, array_slice($navigation, $i + 1));
        }
        //POCOR-7366 end
        $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
        $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');
        if (!empty($studentToGuardian) || !empty($guardianToStudent)) {
            $navigation['Directories.Directories.view']['selected'] = ['Directories.Directories.view',
                'Directories.Directories.edit',
                'Directories.Directories.pull', 'Directories.History'];
        }

        return $navigation;
    }

    public function getProfileStaffNavigation()
    {
        $navigation = [
            'Profiles.Staff' => [
                'title' => 'Staff',
                'parent' => 'Profiles.Personal',
                'link' => false,
            ],

            'Profiles.StaffEmploymentStatuses' => [
                'title' => 'Career',
                'parent' => 'Profiles.Staff',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.StaffEmploymentStatuses',
                    'Profiles.StaffPositions',
                    'Profiles.StaffClasses',
                    'Profiles.StaffSubjects',
                    'Profiles.StaffLeave',
                    'Profiles.ArchivedStaffLeave',
                    'Profiles.HistoricalStaffLeave',
                    'Profiles.StaffAttendances',
                    'Profiles.StaffBehaviours',
                    'Profiles.StaffAppraisals',
                    'Profiles.StaffDuties',
                    'Profiles.StaffAssociations',
                    'Profiles.StaffCurriculars']
            ],
            'Profiles.StaffBankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Profiles.Staff',
                'params' => ['plugin' => 'Profile',
                    'type' => 'staff'],
                'selected' => ['Profiles.StaffBankAccounts',
                    'Profiles.StaffSalaries',
                    'Profiles.ImportSalaries', 'Profiles.StaffPayslips']
            ],
            'Profiles.TrainingNeeds' => [
                'title' => 'Training',
                'parent' => 'Profiles.Staff',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.TrainingNeeds',
                    'Profiles.TrainingResults',
                    'Profiles.Courses']
            ],
            'Profiles.ScheduleTimetable' => [
                'title' => 'Timetables',
                'parent' => 'Profiles.Staff',
                'selected' => ['Profiles.ScheduleTimetable'],
                'params' => ['plugin' => 'Profile']
            ],/*POCOR-6286 - added profiles menu*/
            'Profiles.StaffProfiles' => [
                'title' => 'Profiles',
                'parent' => 'Profiles.Staff',
                'params' => ['plugin' => 'Profile',],
                'selected' => ['Profiles.StaffProfiles']
            ]
        ];
        return $navigation;
    }

    public function getProfileStudentNavigation()
    {
        $navigation = [
            'Profiles.Student' => [
                'title' => 'Student',
                'parent' => 'Profiles.Personal',
                'link' => false,
            ],
            'Profiles.ProfileGuardians' => [
                'title' => 'Guardians',
                'parent' => 'Profiles.Student',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.ProfileGuardians',
                    'Profiles.ProfileGuardianUser']
            ],
            'Profiles.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Profiles.Student',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.StudentProgrammes.index',
                    'Profiles.StudentSubjects',
                    'Profiles.StudentClasses',
                    'Profiles.StudentAbsences',
                    'Profiles.StudentBehaviours', 'Profiles.StudentCompetencies',
                    'Profiles.StudentResults', 'Profiles.StudentAssessments',
                    'Profiles.StudentExaminationResults',
                    'Profiles.StudentReportCards',
                    'Profiles.StudentAwards',
                    'Profiles.StudentExtracurriculars',
                    'Profiles.StudentTextbooks',
                    'Profiles.StudentOutcomes',
                    'Profiles.StudentRisks',
                    'Profiles.StudentAssociations',
                    'Profiles.Absences',
                    'Profiles.StudentCurriculars']
            ],//POCOR-6701 added Profiles.Absences becasue navigation was collapsing //POCOR-6699 adding studentAssessment
            'Profiles.StudentScheduleTimetable' => [
                'title' => 'Timetables',
                'parent' => 'Profiles.Student',
                'selected' => ['Profiles.StudentScheduleTimetable'],
                'params' => ['plugin' => 'Profile']
            ],
            'Profiles.StudentBankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Profiles.Student',
                'params' => ['plugin' => 'Profile',
                    'type' => 'student'],
                'selected' => ['Profiles.StudentBankAccounts',
                    'Profiles.StudentFees']
            ],/*POCOR-6286 - added profiles menu*/
            'Profiles.StudentProfiles' => [
                'title' => 'Profiles',
                'parent' => 'Profiles.Student',
                'params' => ['plugin' => 'Profile',
                    'type' => 'student'],
                'selected' => ['Profiles.StudentProfiles']
            ]
        ];
        return $navigation;
    }

    public function getProfileGuardianStudentNavigation()
    {
        $sID = $this->request->pass[1];
        $session = $this->request->session();
        if (!empty($sID)) {
            if ($session->read('Auth.User.is_guardian') == 1) {
                $session->write('Student.ExaminationResults.student_id', $sID);
            }
            $studentId = $session->read('Student.ExaminationResults.student_id');
        } else {
            //$studentId = $this->request->pass[1];
            $studentId = $session->read('Student.ExaminationResults.student_id');
        }
        // echo '<pre>';print_r($_SESSION);die;
        $navigation = [
            'Profiles.ProfileStudentUser' => [
                'title' => 'Overview',
                'parent' => 'Profiles.ProfileStudents.index',
                'params' => ['plugin' => 'Profile', 'controller' => 'Profiles',
                    'action' => 'ProfileStudentUser', 0 => 'view', $studentId],
                'selected' => ['Profiles.ProfileStudentUser']
            ],
            'Profiles.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Profiles.ProfileStudents.index',
                'params' => ['plugin' => 'Profile',
                    'controller' => 'Profiles', $studentId],
                'selected' => ['Profiles.StudentProgrammes.index',
                    'Profiles.StudentSubjects',
                    'Profiles.StudentClasses',
                    'Profiles.StudentAbsences',
                    'Profiles.StudentBehaviours',
                    'Profiles.StudentCompetencies', 'Profiles.StudentCompetencies.index',
                    'Profiles.StudentResults',
                    'Profiles.StudentExaminationResults',
                    'Profiles.StudentReportCards',
                    'Profiles.StudentAwards',
                    'Profiles.StudentExtracurriculars',
                    'Profiles.StudentTextbooks',
                    'Profiles.StudentOutcomes',
                    'Profiles.StudentRisks',
                    'Profiles.StudentAssociations',
                    'Profiles.Absences']
            ],
        ];

        return $navigation;
    }

    public function getDirectoryStaffNavigation()
    {
        $session = $this->request->session();
        $id = $session->read('Guardian.Guardians.id');

        $navigation = [
            'Directories.Staff' => [
                'title' => 'Staff',
                'parent' => 'Directories.Directories.index',
                'link' => false,
            ],
            'Directories.StaffEmploymentStatuses' => [
                'title' => 'Career',
                'parent' => 'Directories.Staff',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.StaffEmploymentStatuses',
                    'Directories.StaffPositions',
                    'Directories.HistoricalStaffPositions',
                    'Directories.StaffClasses',
                    'Directories.StaffSubjects',
                    'Directories.StaffLeave',
                    'Directories.ArchivedStaffLeave',
                    'Directories.HistoricalStaffLeave',
                    'Directories.StaffAttendances',
                    'Directories.StaffBehaviours',
                    'Directories.StaffAppraisals',
                    'Directories.StaffDuties',
                    'Directories.StaffAssociations']
            ],
            'Directories.StaffBankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Directories.Staff',
                'params' => ['plugin' => 'Directory',
                    'type' => 'staff'],
                'selected' => ['Directories.StaffBankAccounts',
                    'Directories.StaffSalaries',
                    'Directories.ImportSalaries', 'Directories.StaffPayslips']
            ],
            'Directories.TrainingNeeds' => [
                'title' => 'Training',
                'parent' => 'Directories.Staff',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.TrainingNeeds',
                    'Directories.TrainingResults',
                    'Directories.Courses']
            ],/*POCOR-6286 - added profiles menu*/
            'Directories.StaffProfiles' => [
                'title' => 'Profiles',
                'parent' => 'Directories.Staff',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.StaffProfiles']
            ]
        ];

        return $navigation;
    }

    public function getDirectoryStudentNavigation()
    {
        $session = $this->request->session();
        $id = $session->read('Guardian.Guardians.id');

        $navigation = [
            'Directories.Student' => [
                'title' => 'Student',
                'parent' => 'Directories.Directories.index',
                'link' => false,
            ],
            'Directories.StudentGuardians' => [
                'title' => 'Guardians',
                'parent' => 'Directories.Student',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.StudentGuardians',
                    'Directories.StudentGuardianUser',
                    'Directories.Addguardian']
            ],//POCOR-7093 Addguardian condition
            'Directories.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Directories.Student',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.StudentProgrammes.index',
                    'Directories.StudentSubjects',
                    'Directories.StudentClasses',
                    'Directories.StudentAbsences',
                    'Directories.StudentBehaviours',
                    'Directories.StudentResults',
                    'Directories.StudentExaminationResults',
                    'Directories.StudentReportCards',
                    'Directories.StudentAwards',
                    'Directories.StudentExtracurriculars',
                    'Directories.StudentTextbooks',
                    'Directories.StudentOutcomes',
                    'Directories.StudentRisks', 'Directories.StudentAssociations',
                    'Directories.Absences']
            ],
            'Directories.StudentBankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Directories.Student',
                'params' => ['plugin' => 'Directory',
                    'type' => 'student'],
                'selected' => ['Directories.StudentBankAccounts',
                    'Directories.StudentFees']
            ],/*POCOR-6286 - added profiles menu*/
            'Directories.StudentProfiles' => [
                'title' => 'Profiles',
                'parent' => 'Directories.Student',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.StudentProfile']
            ],

        ];

        $session = $this->request->session();
        $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
        if (!empty($studentToGuardian)) {
            $navigation['Directories.StudentGuardians']['selected'] = ['Directories.StudentGuardians',
                'Directories.StudentGuardianUser',
                'Directories.Accounts',
                'Directories.Identities',
                'Directories.Nationalities',
                'Directories.Languages',
                'DirectoryComments.index',
                'DirectoryComments.view',
                'DirectoryComments.add',
                'DirectoryComments.edit',
                'DirectoryComments.delete',
                'Directories.Attachments',
                'Directories.Contacts',
                'Directories.Demographic'];
        }

        return $navigation;
    }

    public function getDirectoryGuardianNavigation()
    {
        $navigation = [
            'Directories.Guardian' => [
                'title' => 'Guardian',
                'parent' => 'Directories.Directories.index',
                'link' => false,
            ],
            'Directories.GuardianStudents' => [
                'title' => 'Students',
                'parent' => 'Directories.Guardian',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.GuardianStudents']
            ],
        ];
        $session = $this->request->session();
        $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');
        if (!empty($guardianToStudent)) {
            $navigation['Directories.GuardianStudents']['selected'] = ['Directories.GuardianStudents',
                'Directories.GuardianStudentUser',
                'Directories.Accounts',
                'Directories.Identities',
                'Directories.Nationalities',
                'Directories.Languages',
                'DirectoryComments.index',
                'DirectoryComments.view',
                'DirectoryComments.add',
                'DirectoryComments.edit',
                'DirectoryComments.delete',
                'Directories.Attachments',
                'Directories.Contacts',
                'Directories.Demographic'];
        }

        return $navigation;
    }


    public function getReportNavigation()
    {
        $navigation = [
            'Reports.Directory' => [
                'title' => 'Directory',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.Institutions' => [
                'title' => 'Institutions',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
                'selected' => ['Reports.ViewReport']
            ],
            'Reports.Students' => [
                'title' => 'Students',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.Staff' => [
                'title' => 'Staff',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.Textbooks' => [
                'title' => 'Textbooks',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            /*POCOR-6513 starts - Added new report module*/
            'Reports.Performance' => [
                'title' => 'Performance',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            /*POCOR-6513 ends*/
            'Reports.Examinations' => [
                'title' => 'Examinations',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.Trainings' => [
                'title' => 'Trainings',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.Scholarships' => [
                'title' => 'Scholarships',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.Surveys' => [
                'title' => 'Surveys',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.InstitutionRubrics' => [
                'title' => 'Rubrics',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.DataQuality' => [
                'title' => 'Data Quality',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report']
            ],
            'Reports.Audits' => [
                'title' => 'Audits',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.Workflows' => [
                'title' => 'Workflows',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.UisStatistics' => [
                'title' => 'UIS Statistics',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Map.index' => [
                'title' => 'Map',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Map'],
            ],
            'Reports.CustomReports' => [
                'title' => 'Custom',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ]
        ];
        return $navigation;
    }

    public function getAdministrationNavigation()
    {
        //for POCOR-5674 requirement
        $connectionTable = TableRegistry::get('Archive.DataManagementConnections');
        $connectionData = $connectionTable->find()->select(['id'])->first()->toArray();
        $connectionId = $this->controller->paramsEncode(['id' => $connectionData['id']]);
        /*for POCOR-5674 */

        $queryString = $this->request->query('queryString');
        //POCOR-7527 start
        $firstSubMenuAdmin = $this->getAdminstrationFirstNav();
        $SecurityNav = $this->getAdminstrationSecurityNav();
        $ProfileNav = $this->getAdminstrationProfileNav();
        $SurveyNav = $this->getAdminstrationSurveyNav();
        $CommunicationsNav = $this->getAdminstrationCommunicationsNav();
        $TrainingNav = $this->getAdminstrationTrainingNav();
        $PerformanceNav = $this->getAdminstrationPerformanceNav();
        $ExaminationNav = $this->getAdminstrationExaminationNav();
        $ScholarshipNav = $this->getAdminstrationScholarshipNav();
        $MoodleNav = $this->getAdminstrationMoodleNav();
        $dataMgtNav = $this->getAdminstrationdataMgtNav();
        //POCOR-7527 end
        $navigation = [

            'StaffAppraisals.Criterias.index' => [
                'title' => 'Appraisals',
                'parent' => 'Administration',
                'params' => ['plugin' => 'StaffAppraisal'],
                'selected' => ['StaffAppraisals.Criterias',
                    'StaffAppraisals.Forms',
                    'StaffAppraisals.Types',
                    'StaffAppraisals.Periods',
                    'StaffAppraisals.Scores']
            ],

            'Textbooks.Textbooks' => [
                'title' => 'Textbooks',
                'parent' => 'Administration',
                'params' => ['plugin' => 'Textbook'],
                'selected' => ['Textbooks.Textbooks',
                    'Textbooks.ImportTextbooks']
            ],

            'Meals.programme' => [
                'title' => 'Meals',
                'parent' => 'Administration',
                'params' => ['plugin' => 'Meal'],
                'selected' => ['Meals.programme']
            ],

            'Workflows.Workflows' => [
                'title' => 'Workflow',
                'parent' => 'Administration',
                'params' => ['plugin' => 'Workflow'],
                'selected' => ['Workflows.Workflows',
                    'Workflows.Steps',
                    'Workflows.Actions',
                    'Workflows.Rules',
                    'Workflows.Statuses']
            ],
            'Systems.Updates' => [
                'title' => 'Updates',
                'parent' => 'Administration',
                'params' => ['plugin' => 'System']
            ],
            'Calendars.index' => [
                'title' => 'Calendar',
                'parent' => 'Administration',
                'selected' => ['Calendars.index',
                    'Calendars.view',
                    'Calendars.add',
                    'Calendars.edit',
                    'Calendars.delete']
            ],

        ];

        $getallNavigation = array_merge($firstSubMenuAdmin, $SecurityNav, $ProfileNav, $SurveyNav,
            $CommunicationsNav, $TrainingNav, $PerformanceNav, $ExaminationNav, $ScholarshipNav, $navigation, $MoodleNav, $dataMgtNav); //POCOR-7527
        return $getallNavigation;
    }

    public function getGuardianNavNavigation()
    {
        $session = $this->request->session();
        $studentId = $session->read('Student.Students.id');
        $queryString = $this->request->query('queryString');
        if ($queryString != '') {
            $session->write('queryString', $queryString);
        } else {
            $queryString = $session->read('queryString');
        }
        $navigation = [
            'GuardianNavs.StudentUser.view' => [
                'title' => 'General',
                'parent' => 'GuardianNavs.GuardianNavs.index',
                'params' => ['plugin' => 'GuardianNav',
                    '1' => $this->controller->paramsEncode(['id' => $studentId]), 'queryString' => $queryString],
                'selected' => ['GuardianNavs.StudentUser']
            ],
            'GuardianNavs.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'GuardianNavs.GuardianNavs.index',
                'params' => ['plugin' => 'GuardianNav'],
                'selected' => ['GuardianNavs.StudentClasses',
                    'GuardianNavs.StudentSubjects',
                    'GuardianNavs.StudentAbsences',
                    'GuardianNavs.StudentBehaviours',
                    'GuardianNavs.StudentOutcomes',
                    'GuardianNavs.StudentCompetencies',
                    'GuardianNavs.StudentResults',
                    'GuardianNavs.StudentExaminationResults',
                    'GuardianNavs.StudentReportCards',
                    'GuardianNavs.StudentAwards',
                    'GuardianNavs.StudentExtracurriculars',
                    'GuardianNavs.StudentTextbooks',
                    'GuardianNavs.StudentRisks',
                    'GuardianNavs.StudentAssociations']
            ]
        ];
        foreach ($navigation as &$n) {
            if (isset($n['params'])) {
                $n['params']['studentId'] = $this->controller->paramsEncode($studentId);
            }
        }
        return $navigation;
    }

    /**
     * POCOR-7527
     * seperate Report, Adminstration menu . creationg issue while provide permission
     * these two left menu are not having link
     */
    private function getReportAdminstrationNavigation($uId)
    {
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();
        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecurityReportFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('module') => 'Reports', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
            $SecurityAdminFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('module') => 'Administration',
                    $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }

        $navigationToAppends = [];
        if (empty($userinfo)) {
            if (!empty($SecurityAdminFunctions) && !empty($SecurityReportFunctions)) {
                $navigationToAppends = [
                    'Reports' => [
                        'title' => 'Reports',
                        'icon' => '<span><i class="fa kd-reports"></i></span>',
                        'link' => false,
                    ],

                    'Administration' => [
                        'title' => 'Administration',
                        'icon' => '<span><i class="fa fa-cogs"></i></span>',
                        'link' => false
                    ],
                ];

            } elseif (!empty($SecurityAdminFunctions)) {
                $navigationToAppends = [

                    'Administration' => [
                        'title' => 'Administration',
                        'icon' => '<span><i class="fa fa-cogs"></i></span>',
                        'link' => false
                    ],
                ];
            } elseif (!empty($SecurityReportFunctions)) {
                $navigationToAppends = [
                    'Reports' => [
                        'title' => 'Reports',
                        'icon' => '<span><i class="fa kd-reports"></i></span>',
                        'link' => false,
                    ],
                ];
            }
        } else {
            $navigationToAppends = [
                'Reports' => [
                    'title' => 'Reports',
                    'icon' => '<span><i class="fa kd-reports"></i></span>',
                    'link' => false,
                ],

                'Administration' => [
                    'title' => 'Administration',
                    'icon' => '<span><i class="fa fa-cogs"></i></span>',
                    'link' => false
                ],
            ];
        }

        return $navigationToAppends;
    }

    /**
     * POCOR-7527
     * seperate first Adminstration menu . creationg issue while provide permission
     * creation issue for dropdowin menu
     */
    private function getAdminstrationSubmenuNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }

        if (!empty($rowId)) {
            $SecurityCustomFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Custom Fields', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
            $SecuritylocalizationFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Localization', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
            $SecurityApiFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'APIs', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }

        $navigationToAppends = [];
        if (empty($userinfo)) {
            if (!empty($SecurityCustomFunctions) && !empty($SecuritylocalizationFunctions) && !empty($SecurityApiFunctions)) {
                $navigationToAppends =
                    [
                        'SystemSetup.CustomField' => [
                            'title' => 'Custom Field',
                            'parent' => 'SystemSetup',
                            'link' => false,
                        ],

                        'InstitutionCustomFields.Fields' => [
                            'title' => 'Institution',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'InstitutionCustomField'],
                            'selected' => ['InstitutionCustomFields.Fields',
                                'InstitutionCustomFields.Pages']
                        ],
                        'StudentCustomFields.Fields' => [
                            'title' => 'Student',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StudentCustomField'],
                            'selected' => ['StudentCustomFields.Fields',
                                'StudentCustomFields.Pages']
                        ],
                        'StaffCustomFields.Fields' => [
                            'title' => 'Staff',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StaffCustomField'],
                            'selected' => ['StaffCustomFields.Fields',
                                'StaffCustomFields.Pages']
                        ],
                        'Infrastructures.Fields' => [
                            'title' => 'Infrastructure',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'Infrastructure'],
                            'selected' => ['Infrastructures.Fields',
                                'Infrastructures.LandPages',
                                'Infrastructures.BuildingPages',
                                'Infrastructures.FloorPages',
                                'Infrastructures.RoomPages']
                        ],
                        'SystemSetup.Localization' => [
                            'title' => 'Localization',
                            'parent' => 'SystemSetup',
                            'link' => false,
                        ],
                        'Locales.index' => [
                            'title' => 'Languages',
                            'parent' => 'SystemSetup.Localization',
                            'selected' => ['Locales.index',
                                'Locales.view',
                                'Locales.edit',
                                'Locales.add']
                        ],
                        'LocaleContents.index' => [
                            'title' => 'Translations',
                            'parent' => 'SystemSetup.Localization',
                            'selected' => ['LocaleContents.index',
                                'LocaleContents.view',
                                'LocaleContents.edit']
                        ],

                        'API' => [
                            'title' => 'APIs',
                            'parent' => 'SystemSetup',
                            'link' => false
                        ],
                        'Credentials.index' => [
                            'title' => 'Credentials',
                            'parent' => 'API',
                            'selected' => ['Credentials.view',
                                'Credentials.add',
                                'Credentials.edit',
                                'Credentials.delete']
                        ],
                    ];
            } elseif (!empty($SecurityCustomFunctions)) {
                $navigationToAppends =
                    [
                        'SystemSetup.CustomField' => [
                            'title' => 'Custom Field',
                            'parent' => 'SystemSetup',
                            'link' => false,
                        ],
                        'InstitutionCustomFields.Fields' => [
                            'title' => 'Institution',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'InstitutionCustomField'],
                            'selected' => ['InstitutionCustomFields.Fields',
                                'InstitutionCustomFields.Pages']
                        ],
                        'StudentCustomFields.Fields' => [
                            'title' => 'Student',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StudentCustomField'],
                            'selected' => ['StudentCustomFields.Fields',
                                'StudentCustomFields.Pages']
                        ],
                        'StaffCustomFields.Fields' => [
                            'title' => 'Staff',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StaffCustomField'],
                            'selected' => ['StaffCustomFields.Fields',
                                'StaffCustomFields.Pages']
                        ],
                        'Infrastructures.Fields' => [
                            'title' => 'Infrastructure',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'Infrastructure'],
                            'selected' => ['Infrastructures.Fields',
                                'Infrastructures.LandPages',
                                'Infrastructures.BuildingPages',
                                'Infrastructures.FloorPages',
                                'Infrastructures.RoomPages']
                        ],
                    ];

            } elseif (!empty($SecuritylocalizationFunctions)) {
                $navigationToAppends =
                    [
                        'SystemSetup.Localization' => [
                            'title' => 'Localization',
                            'parent' => 'SystemSetup',
                            'link' => false,
                        ],
                        'Locales.index' => [
                            'title' => 'Languages',
                            'parent' => 'SystemSetup.Localization',
                            'selected' => ['Locales.index',
                                'Locales.view',
                                'Locales.edit',
                                'Locales.add']
                        ],
                        'LocaleContents.index' => [
                            'title' => 'Translations',
                            'parent' => 'SystemSetup.Localization',
                            'selected' => ['LocaleContents.index',
                                'LocaleContents.view',
                                'LocaleContents.edit']
                        ],
                    ];
            } elseif (!empty($SecurityApiFunctions)) {
                $navigationToAppends = [
                    'API' => [
                        'title' => 'APIs',
                        'parent' => 'SystemSetup',
                        'link' => false
                    ],
                    //POCOR-7312[START]
                    // 'ApiSecurities.index' => [
                    //     'title' => 'Securities',
                    //     'parent' => 'API',
                    //     'selected' => ['ApiSecurities.view', 'ApiSecurities.add', 'ApiSecurities.edit', 'ApiSecurities.delete']
                    // ],
                    //POCOR-7312[END]
                    'Credentials.index' => [
                        'title' => 'Credentials',
                        'parent' => 'API',
                        'selected' => ['Credentials.view',
                            'Credentials.add',
                            'Credentials.edit',
                            'Credentials.delete']
                    ],
                ];
            }

        } else {
            $navigationToAppends =
                [
                    'SystemSetup.CustomField' => [
                        'title' => 'Custom Field',
                        'parent' => 'SystemSetup',
                        'link' => false,
                    ],
                    'InstitutionCustomFields.Fields' => [
                        'title' => 'Institution',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'InstitutionCustomField'],
                        'selected' => ['InstitutionCustomFields.Fields',
                            'InstitutionCustomFields.Pages']
                    ],
                    'StudentCustomFields.Fields' => [
                        'title' => 'Student',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'StudentCustomField'],
                        'selected' => ['StudentCustomFields.Fields',
                            'StudentCustomFields.Pages']
                    ],
                    'StaffCustomFields.Fields' => [
                        'title' => 'Staff',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'StaffCustomField'],
                        'selected' => ['StaffCustomFields.Fields',
                            'StaffCustomFields.Pages']
                    ],
                    'Infrastructures.Fields' => [
                        'title' => 'Infrastructure',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'Infrastructure'],
                        'selected' => ['Infrastructures.Fields',
                            'Infrastructures.LandPages',
                            'Infrastructures.BuildingPages',
                            'Infrastructures.FloorPages',
                            'Infrastructures.RoomPages']
                    ],
                    'SystemSetup.Localization' => [
                        'title' => 'Localization',
                        'parent' => 'SystemSetup',
                        'link' => false,
                    ],
                    'Locales.index' => [
                        'title' => 'Languages',
                        'parent' => 'SystemSetup.Localization',
                        'selected' => ['Locales.index',
                            'Locales.view',
                            'Locales.edit',
                            'Locales.add']
                    ],
                    'LocaleContents.index' => [
                        'title' => 'Translations',
                        'parent' => 'SystemSetup.Localization',
                        'selected' => ['LocaleContents.index',
                            'LocaleContents.view',
                            'LocaleContents.edit']
                    ],

                    'API' => [
                        'title' => 'APIs',
                        'parent' => 'SystemSetup',
                        'link' => false
                    ],
                    //POCOR-7312[START]
                    // 'ApiSecurities.index' => [
                    //     'title' => 'Securities',
                    //     'parent' => 'API',
                    //     'selected' => ['ApiSecurities.view', 'ApiSecurities.add', 'ApiSecurities.edit', 'ApiSecurities.delete']
                    // ],
                    //POCOR-7312[END]
                    'Credentials.index' => [
                        'title' => 'Credentials',
                        'parent' => 'API',
                        'selected' => ['Credentials.view',
                            'Credentials.add',
                            'Credentials.edit',
                            'Credentials.delete']
                    ],
                ];
        }
        return $navigationToAppends;

    }

    /**
     * POCOR-7527
     * seperate first Adminstration menu . creationg issue while provide permission
     * creation issue for dropdowin menu
     */
    private function getAdminstrationFirstNav()
    {
        // Start POCOR-7542
        $getDropdownMenu = $this->getAdminstrationSubmenuNav();
        if (!empty($getDropdownMenu)) {
            $navigations = [
                'SystemSetup' => [
                    'title' => 'System Setup',
                    'parent' => 'Administration',
                    'link' => false,
                ],

                'Areas.Areas' => [
                    'title' => 'Administrative Boundaries',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Area'],
                    'selected' => ['Areas.Areas',
                        'Areas.Levels',
                        'Areas.AdministrativeLevels',
                        'Areas.Administratives']
                ],
                'AcademicPeriods.Periods' => [
                    'title' => 'Academic Periods',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'AcademicPeriod'],
                    'selected' => ['AcademicPeriods.Periods',
                        'AcademicPeriods.Levels']
                ],
                'Educations.Systems' => [
                    'title' => 'Education Structure',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Education'],
                    'selected' => ['Educations.Systems',
                        'Educations.Levels',
                        'Educations.Cycles',
                        'Educations.Programmes',
                        'Educations.Grades',
                        'Educations.Stages',
                        'Educations.Subjects',
                        'Educations.GradeSubjects',
                        'Educations.Certifications',
                        'Educations.FieldOfStudies',
                        'Educations.ProgrammeOrientations', 'Educations.CopySystems']
                ],
                'Attendances.StudentMarkTypes' => [
                    'title' => 'Attendances',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Attendance'],
                    'selected' => ['Attendances.StudentMarkTypeStatuses']
                ],
                'FieldOptions.index' => [
                    'title' => 'Field Options',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'FieldOption'],
                    'selected' => ['FieldOptions.index',
                        'FieldOptions.add',
                        'FieldOptions.view',
                        'FieldOptions.edit',
                        'FieldOptions.remove']
                ],

                'Labels.index' => [
                    'title' => 'Labels',
                    'parent' => 'SystemSetup',
                    'selected' => ['Labels.index',
                        'Labels.view',
                        'Labels.edit']
                ],

                'Configurations.index' => [
                    'title' => 'System Configurations',
                    'parent' => 'SystemSetup',
                    'selected' => ['Configurations.index',
                        'Configurations.add',
                        'Configurations.view',
                        'Configurations.edit',
                        'Themes.index',
                        'Themes.view',
                        'Themes.edit']
                ],
                // Start POCOR-5188
                'Manuals.Institutions' => [
                    'title' => 'Manuals',
                    'parent' => 'SystemSetup',
                    'selected' => ['Manuals.Institutions', 'Manuals.view',
                        'Manuals.edit',
                        'Manuals.Directory',
                        'Manuals.Reports',
                        'Manuals.Personal',
                        'Manuals.Administration',
                        'Manuals.Guardian']
                ],
                // End POCOR-5188

                'Notices.index' => [
                    'title' => 'Notices',
                    'parent' => 'SystemSetup',
                    'selected' => ['Notices.index',
                        'Notices.add',
                        'Notices.view',
                        'Notices.edit',
                        'Notices.delete']
                ],
                'Risks.Risks' => [
                    'title' => 'Risks',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Risk'],
                    'selected' => ['Risks.Risks']
                ],
            ];
            $menuNavigation = array_merge($navigations, $getDropdownMenu);
            return $menuNavigation;
        } else {
            return [];
        }
        // End POCOR-7542
    }

    //POCOR-7527
    private function getAdminstrationSecurityNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecurityFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Security', $securityFunctions->aliasField('module') => 'Administration', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navOne = [];
        if (empty($userinfo)) {
            if (!empty($SecurityFunctions)) {
                $navOne = [
                    'Security' => [
                        'title' => 'Security',
                        'parent' => 'Administration',
                        'link' => false,
                    ],

                    'Securities.Users' => [
                        'title' => 'Users',
                        'parent' => 'Security',
                        'params' => ['plugin' => 'Security'],
                        'selected' => ['Securities.Users',
                            'Securities.Accounts']
                    ],

                    'Securities.UserGroups' => [
                        'title' => 'Groups',
                        'parent' => 'Security',
                        'params' => ['plugin' => 'Security'],
                        'selected' => ['Securities.UserGroups', 'Securities.SystemGroups', 'Securities.UserGroupsList', 'Securities.SystemGroupsList']
                    ],
                    'Securities.Roles' => [
                        'title' => 'Roles',
                        'parent' => 'Security',
                        'params' => ['plugin' => 'Security'],
                        'selected' => ['Securities.Roles',
                            'Securities.Permissions']
                    ],
                ];
            }

        } else {
            $navOne = [
                'Security' => [
                    'title' => 'Security',
                    'parent' => 'Administration',
                    'link' => false,
                ],

                'Securities.Users' => [
                    'title' => 'Users',
                    'parent' => 'Security',
                    'params' => ['plugin' => 'Security'],
                    'selected' => ['Securities.Users',
                        'Securities.Accounts']
                ],

                'Securities.UserGroups' => [
                    'title' => 'Groups',
                    'parent' => 'Security',
                    'params' => ['plugin' => 'Security'],
                    'selected' => ['Securities.UserGroups', 'Securities.SystemGroups', 'Securities.UserGroupsList', 'Securities.SystemGroupsList']
                ],
                'Securities.Roles' => [
                    'title' => 'Roles',
                    'parent' => 'Security',
                    'params' => ['plugin' => 'Security'],
                    'selected' => ['Securities.Roles',
                        'Securities.Permissions']
                ],
            ];
        }
        return $navOne;
    }

    //POCOR-7527
    private function getAdminstrationProfileNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecurityProfilesFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Profiles', $securityFunctions->aliasField('module') => 'Administration', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navTwo = [];
        if (empty($userinfo)) {
            if (!empty($SecurityProfilesFunctions)) {
                $navTwo = [
                    'ProfileTemplates' => [
                        'title' => 'Profiles',
                        'parent' => 'Administration',
                        'link' => false
                    ],
                    'ProfileTemplates.Institutions' => [
                        'title' => 'Institutions',
                        'parent' => 'ProfileTemplates',
                        'selected' => ['ProfileTemplates.InstitutionProfiles',
                            'ProfileTemplates.view',
                            'ProfileTemplates.add',
                            'ProfileTemplates.edit',
                            'ProfileTemplates.delete']
                    ],//POCOR-6822 Starts Add menu classes
                    'ProfileTemplates.Classes' => [
                        'title' => 'Classes',
                        'parent' => 'ProfileTemplates',
                        'selected' => ['ProfileTemplates.ClassesProfiles',
                            'Class.view',
                            'Class.add',
                            'Class.edit',
                            'Class.delete']
                    ],//POCOR-6822 Ends
                    'ProfileTemplates.Staff' => [
                        'title' => 'Staff',
                        'parent' => 'ProfileTemplates',
                        'selected' => ['ProfileTemplates.StaffProfiles',
                            'Staff.view',
                            'Staff.add',
                            'Staff.edit',
                            'Staff.delete']
                    ],
                    'ProfileTemplates.Students' => [
                        'title' => 'Students',
                        'parent' => 'ProfileTemplates',
                        'selected' => ['ProfileTemplates.StudentProfiles',
                            'Students.view',
                            'Students.add',
                            'Students.edit',
                            'Students.delete']
                    ],
                ];
            }
        } else {
            $navTwo = [
                'ProfileTemplates' => [
                    'title' => 'Profiles',
                    'parent' => 'Administration',
                    'link' => false
                ],
                'ProfileTemplates.Institutions' => [
                    'title' => 'Institutions',
                    'parent' => 'ProfileTemplates',
                    'selected' => ['ProfileTemplates.InstitutionProfiles',
                        'ProfileTemplates.view',
                        'ProfileTemplates.add',
                        'ProfileTemplates.edit',
                        'ProfileTemplates.delete']
                ],//POCOR-6822 Starts Add menu classes
                'ProfileTemplates.Classes' => [
                    'title' => 'Classes',
                    'parent' => 'ProfileTemplates',
                    'selected' => ['ProfileTemplates.ClassesProfiles',
                        'Class.view',
                        'Class.add',
                        'Class.edit',
                        'Class.delete']
                ],//POCOR-6822 Ends
                'ProfileTemplates.Staff' => [
                    'title' => 'Staff',
                    'parent' => 'ProfileTemplates',
                    'selected' => ['ProfileTemplates.StaffProfiles',
                        'Staff.view',
                        'Staff.add',
                        'Staff.edit',
                        'Staff.delete']
                ],
                'ProfileTemplates.Students' => [
                    'title' => 'Students',
                    'parent' => 'ProfileTemplates',
                    'selected' => ['ProfileTemplates.StudentProfiles',
                        'Students.view',
                        'Students.add',
                        'Students.edit',
                        'Students.delete']
                ],
            ];
        }
        return $navTwo;
    }

    //POCOR-7527
    private function getAdminstrationSurveyNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecuritySurveyFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Survey', $securityFunctions->aliasField('module') => 'Administration', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navthree = [];
        if (empty($userinfo)) {
            if (!empty($SecuritySurveyFunctions)) {
                $navthree = [
                    'Administration.Survey' => [
                        'title' => 'Survey',
                        'parent' => 'Administration',
                        'link' => false,
                    ],

                    'Surveys.Questions' => [
                        'title' => 'Forms',
                        'parent' => 'Administration.Survey',
                        'params' => ['plugin' => 'Survey'],
                        'selected' => ['Surveys.Questions',
                            'Surveys.Forms',
                            'Surveys.Rules',
                            'Surveys.Status', 'Surveys.Filters', 'Surveys.Recipients'] //POCOR-7271
                    ],

                    'Rubrics.Templates' => [
                        'title' => 'Rubrics',
                        'parent' => 'Administration.Survey',
                        'params' => ['plugin' => 'Rubric'],
                        'selected' => ['Rubrics.Sections',
                            'Rubrics.Criterias',
                            'Rubrics.Options',
                            'Rubrics.Status']
                    ],
                ];
            }

        } else {
            $navthree = [
                'Administration.Survey' => [
                    'title' => 'Survey',
                    'parent' => 'Administration',
                    'link' => false,
                ],

                'Surveys.Questions' => [
                    'title' => 'Forms',
                    'parent' => 'Administration.Survey',
                    'params' => ['plugin' => 'Survey'],
                    'selected' => ['Surveys.Questions',
                        'Surveys.Forms',
                        'Surveys.Rules',
                        'Surveys.Status', 'Surveys.Filters', 'Surveys.Recipients'] //POCOR-7271
                ],

                'Rubrics.Templates' => [
                    'title' => 'Rubrics',
                    'parent' => 'Administration.Survey',
                    'params' => ['plugin' => 'Rubric'],
                    'selected' => ['Rubrics.Sections',
                        'Rubrics.Criterias',
                        'Rubrics.Options',
                        'Rubrics.Status']
                ],
            ];
        }
        return $navthree;
    }

    //POCOR-7527
    private function getAdminstrationCommunicationsNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecurityCommunicationsFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Communications', $securityFunctions->aliasField('module') => 'Administration', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navfour = [];
        if (empty($userinfo)) {
            if (!empty($SecurityCommunicationsFunctions)) {
                $navfour = [
                    'Administration.Communications' => [
                        'title' => 'Communications',
                        'parent' => 'Administration',
                        'link' => false,
                    ],
                    'Alerts.Alerts' => [
                        'title' => 'Alerts',
                        'parent' => 'Administration.Communications',
                        'params' => ['plugin' => 'Alert'],
                        'selected' => ['Alerts.Alerts']
                    ],

                    'Alerts.AlertRules' => [
                        'title' => 'Alert Rules',
                        'parent' => 'Administration.Communications',
                        'params' => ['plugin' => 'Alert'],
                        'selected' => ['Alerts.AlertRules']
                    ],

                    'Alerts.Logs' => [
                        'title' => 'Logs',
                        'parent' => 'Administration.Communications',
                        'params' => ['plugin' => 'Alert'],
                        'selected' => ['Alerts.Logs']
                    ],
                ];
            }
        } else {
            $navfour = [
                'Administration.Communications' => [
                    'title' => 'Communications',
                    'parent' => 'Administration',
                    'link' => false,
                ],
                'Alerts.Alerts' => [
                    'title' => 'Alerts',
                    'parent' => 'Administration.Communications',
                    'params' => ['plugin' => 'Alert'],
                    'selected' => ['Alerts.Alerts']
                ],

                'Alerts.AlertRules' => [
                    'title' => 'Alert Rules',
                    'parent' => 'Administration.Communications',
                    'params' => ['plugin' => 'Alert'],
                    'selected' => ['Alerts.AlertRules']
                ],

                'Alerts.Logs' => [
                    'title' => 'Logs',
                    'parent' => 'Administration.Communications',
                    'params' => ['plugin' => 'Alert'],
                    'selected' => ['Alerts.Logs']
                ],
            ];
        }
        return $navfour;
    }

    //POCOR-7527
    private function getAdminstrationTrainingNav()
    {
        $user_id = $this->getCurrentUserId();
        $is_super_user = self::isSuperUser($user_id);
        $emptyNavigation = [];
        $fullTrainingNavigation = self::getTrainingNavigationFull();
        if ($is_super_user) {
            return $fullTrainingNavigation;
        }
        $userRoleIdArray = $this->getUserRoleIdArray($user_id);
        $module = 'Administration';
        $category = 'Trainings';
        $function = '_view';
//        $this->log('userRoleIdArray', 'debug');
//        $this->log($userRoleIdArray, 'debug');
        $has_user_permission = self::hasUserPermission($module, $category, $function, $userRoleIdArray);
//        $this->log($has_user_permission, 'debug');
        if ($has_user_permission) {
            return $fullTrainingNavigation;
        }
        return $emptyNavigation;
    }

    //POCOR-7527
    private function getAdminstrationPerformanceNav()
    {
        $user_id = $this->getCurrentUserId();
        $is_super_user = self::isSuperUser($user_id);
        $emptyNavigation = [];
        $fullPerformanceNavigation = self::getFullPerformanceNavigation();
        if ($is_super_user) {
            return $fullPerformanceNavigation;
        }
        $userRoleIdArray = $this->getUserRoleIdArray($user_id);
        $module = 'Administration';
        $category = ['Performance', 'Competencies', 'ReportCards', 'Assessments', 'Outcomes'];
        $function = '_view';
//        $this->log('userRoleIdArray', 'debug');
//        $this->log($userRoleIdArray, 'debug');
        $has_user_permission = self::hasUserPermission($module, $category, $function, $userRoleIdArray);
//        $this->log('$has_user_permission', 'debug');
//        $this->log($has_user_permission, 'debug');
        if ($has_user_permission) {
            return $fullPerformanceNavigation;
        }
        return $emptyNavigation;
    }

    //POCOR-7527
    private function getAdminstrationExaminationNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }

        if (!empty($rowId)) {
            $SecurityExaminationsFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Examinations', $securityFunctions->aliasField('module') => 'Administration', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navseven = [];
        if (empty($userinfo)) {
            if (!empty($SecurityExaminationsFunctions)) {
                $navseven = [
                    'Administration.Examinations' => [
                        'title' => 'Examinations',
                        'parent' => 'Administration',
                        'link' => false,
                    ],
                    'Examinations.Exams' => [
                        'title' => 'Exams',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.Exams',
                            'Examinations.GradingTypes']
                    ],
                    'Examinations.ExamCentres' => [
                        'title' => 'Centres',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.ExamCentres',
                            'Examinations.ExamCentreRooms',
                            'Examinations.ExamCentreExams',
                            'Examinations.ExamCentreSubjects',
                            'Examinations.ExamCentreStudents',
                            'Examinations.ExamCentreInvigilators',
                            'Examinations.ExamCentreLinkedInstitutions',
                            'Examinations.ImportExaminationCentreRooms']
                    ],
                    'Examinations.RegisteredStudents' => [
                        'title' => 'Students',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.RegisteredStudents',
                            'Examinations.RegistrationDirectory',
                            'Examinations.NotRegisteredStudents']
                    ],
                    'Examinations.ExamResults' => [
                        'title' => 'Results',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.ExamResults',
                            'Examinations.Results',
                            'Examinations.ImportResults']
                    ],
                ];
            }
        } else {
            $navseven = [
                'Administration.Examinations' => [
                    'title' => 'Examinations',
                    'parent' => 'Administration',
                    'link' => false,
                ],
                'Examinations.Exams' => [
                    'title' => 'Exams',
                    'parent' => 'Administration.Examinations',
                    'params' => ['plugin' => 'Examination'],
                    'selected' => ['Examinations.Exams',
                        'Examinations.GradingTypes']
                ],
                'Examinations.ExamCentres' => [
                    'title' => 'Centres',
                    'parent' => 'Administration.Examinations',
                    'params' => ['plugin' => 'Examination'],
                    'selected' => ['Examinations.ExamCentres',
                        'Examinations.ExamCentreRooms',
                        'Examinations.ExamCentreExams',
                        'Examinations.ExamCentreSubjects',
                        'Examinations.ExamCentreStudents',
                        'Examinations.ExamCentreInvigilators',
                        'Examinations.ExamCentreLinkedInstitutions',
                        'Examinations.ImportExaminationCentreRooms']
                ],
                'Examinations.RegisteredStudents' => [
                    'title' => 'Students',
                    'parent' => 'Administration.Examinations',
                    'params' => ['plugin' => 'Examination'],
                    'selected' => ['Examinations.RegisteredStudents',
                        'Examinations.RegistrationDirectory',
                        'Examinations.NotRegisteredStudents']
                ],
                'Examinations.ExamResults' => [
                    'title' => 'Results',
                    'parent' => 'Administration.Examinations',
                    'params' => ['plugin' => 'Examination'],
                    'selected' => ['Examinations.ExamResults',
                        'Examinations.Results',
                        'Examinations.ImportResults']
                ],

            ];
        }
        return $navseven;
    }

    //POCOR-7527
    private function getAdminstrationScholarshipNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecurityScholarshipsFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('module') => 'Administration', $securityFunctions->aliasField('controller') => 'Scholarships', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navEight = [];
        if (empty($userinfo)) {
            if (!empty($SecurityScholarshipsFunctions)) {
                $navEight = [
                    'Administration.Scholarships' => [
                        'title' => 'Scholarships',
                        'parent' => 'Administration',
                        'link' => false,
                    ],
                    'Scholarships.Scholarships' => [
                        'title' => 'Details',
                        'parent' => 'Administration.Scholarships',
                        'params' => ['plugin' => 'Scholarship'],
                        'selected' => ['Scholarships.Scholarships',
                            'ScholarshipAttachmentTypes.index',
                            'ScholarshipAttachmentTypes.view',
                            'ScholarshipAttachmentTypes.add',
                            'ScholarshipAttachmentTypes.edit',
                            'ScholarshipAttachmentTypes.delete']
                    ],
                    'Scholarships.Applications' => [
                        'title' => 'Applications',
                        'parent' => 'Administration.Scholarships',
                        'params' => ['plugin' => 'Scholarship'],
                        'selected' => ['Scholarships.Applications',
                            'UsersDirectory.index',
                            'UsersDirectory.view',
                            'Scholarships.Identities.index',
                            'Scholarships.Identities.view',
                            'Scholarships.Nationalities.index',
                            'Scholarships.Nationalities.view',
                            'Scholarships.Contacts.index',
                            'Scholarships.Contacts.view',
                            'Scholarships.Guardians.index',
                            'Scholarships.Guardians.view',
                            'Scholarships.Histories',
                            'ScholarshipApplicationInstitutionChoices.index',
                            'ScholarshipApplicationInstitutionChoices.view',
                            'ScholarshipApplicationInstitutionChoices.add',
                            'ScholarshipApplicationInstitutionChoices.edit',
                            'ScholarshipApplicationInstitutionChoices.delete',
                            'ScholarshipApplicationAttachments.index',
                            'ScholarshipApplicationAttachments.view',
                            'ScholarshipApplicationAttachments.add',
                            'ScholarshipApplicationAttachments.edit',
                            'ScholarshipApplicationAttachments.delete']
                    ],
                    'ScholarshipRecipients.index' => [
                        'title' => 'Recipients',
                        'parent' => 'Administration.Scholarships',
                        'params' => ['plugin' => 'Scholarship'],
                        'selected' => ['ScholarshipRecipients.index',
                            'ScholarshipRecipients.view',
                            'ScholarshipRecipients.edit',
                            'ScholarshipRecipientInstitutionChoices.index',
                            'ScholarshipRecipientInstitutionChoices.view',
                            'ScholarshipRecipientInstitutionChoices.edit',
                            'Scholarships.RecipientPaymentStructures',
                            'Scholarships.RecipientPayments',
                            'ScholarshipRecipientCollections.index',
                            'ScholarshipRecipientCollections.view',
                            'ScholarshipRecipientCollections.add',
                            'ScholarshipRecipientCollections.edit',
                            'ScholarshipRecipientCollections.delete',
                            'ScholarshipRecipientAcademicStandings.index',
                            'ScholarshipRecipientAcademicStandings.view',
                            'ScholarshipRecipientAcademicStandings.add',
                            'ScholarshipRecipientAcademicStandings.edit',
                            'ScholarshipRecipientAcademicStandings.delete']
                    ],
                ];
            }
        } else {
            $navEight = [
                'Administration.Scholarships' => [
                    'title' => 'Scholarships',
                    'parent' => 'Administration',
                    'link' => false,
                ],
                'Scholarships.Scholarships' => [
                    'title' => 'Details',
                    'parent' => 'Administration.Scholarships',
                    'params' => ['plugin' => 'Scholarship'],
                    'selected' => ['Scholarships.Scholarships',
                        'ScholarshipAttachmentTypes.index',
                        'ScholarshipAttachmentTypes.view',
                        'ScholarshipAttachmentTypes.add',
                        'ScholarshipAttachmentTypes.edit',
                        'ScholarshipAttachmentTypes.delete']
                ],
                'Scholarships.Applications' => [
                    'title' => 'Applications',
                    'parent' => 'Administration.Scholarships',
                    'params' => ['plugin' => 'Scholarship'],
                    'selected' => ['Scholarships.Applications',
                        'UsersDirectory.index',
                        'UsersDirectory.view',
                        'Scholarships.Identities.index',
                        'Scholarships.Identities.view',
                        'Scholarships.Nationalities.index',
                        'Scholarships.Nationalities.view',
                        'Scholarships.Contacts.index',
                        'Scholarships.Contacts.view',
                        'Scholarships.Guardians.index',
                        'Scholarships.Guardians.view',
                        'Scholarships.Histories',
                        'ScholarshipApplicationInstitutionChoices.index',
                        'ScholarshipApplicationInstitutionChoices.view',
                        'ScholarshipApplicationInstitutionChoices.add',
                        'ScholarshipApplicationInstitutionChoices.edit',
                        'ScholarshipApplicationInstitutionChoices.delete',
                        'ScholarshipApplicationAttachments.index',
                        'ScholarshipApplicationAttachments.view',
                        'ScholarshipApplicationAttachments.add',
                        'ScholarshipApplicationAttachments.edit',
                        'ScholarshipApplicationAttachments.delete']
                ],
                'ScholarshipRecipients.index' => [
                    'title' => 'Recipients',
                    'parent' => 'Administration.Scholarships',
                    'params' => ['plugin' => 'Scholarship'],
                    'selected' => ['ScholarshipRecipients.index',
                        'ScholarshipRecipients.view',
                        'ScholarshipRecipients.edit',
                        'ScholarshipRecipientInstitutionChoices.index',
                        'ScholarshipRecipientInstitutionChoices.view',
                        'ScholarshipRecipientInstitutionChoices.edit',
                        'Scholarships.RecipientPaymentStructures',
                        'Scholarships.RecipientPayments',
                        'ScholarshipRecipientCollections.index',
                        'ScholarshipRecipientCollections.view',
                        'ScholarshipRecipientCollections.add',
                        'ScholarshipRecipientCollections.edit',
                        'ScholarshipRecipientCollections.delete',
                        'ScholarshipRecipientAcademicStandings.index',
                        'ScholarshipRecipientAcademicStandings.view',
                        'ScholarshipRecipientAcademicStandings.add',
                        'ScholarshipRecipientAcademicStandings.edit',
                        'ScholarshipRecipientAcademicStandings.delete']
                ],
            ];
        }
        return $navEight;
    }

    //POCOR-7527
    private function getAdminstrationMoodleNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecurityMoodleFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'MoodleApi', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navMoodle = [];
        if (empty($userinfo)) {
            if (!empty($SecurityMoodleFunctions)) {
                $navMoodle = [
                    'Administration.MoodleApi' => [
                        'title' => 'MoodleApi',
                        'parent' => 'Administration',
                        'link' => false,
                    ],
                    'MoodleApi.log' => [
                        'title' => 'Log',
                        'parent' => 'Administration.MoodleApi',
                        'selected' => ['MoodleApiLog.index'],
                        'params' => ['plugin' => 'MoodleApi',
                            'controller' => 'MoodleApiLog',
                            'action' => 'index']
                    ],
                ];
            }
        } else {
            $navMoodle = [
                'Administration.MoodleApi' => [
                    'title' => 'MoodleApi',
                    'parent' => 'Administration',
                    'link' => false,
                ],
                'MoodleApi.log' => [
                    'title' => 'Log',
                    'parent' => 'Administration.MoodleApi',
                    'selected' => ['MoodleApiLog.index'],
                    'params' => ['plugin' => 'MoodleApi',
                        'controller' => 'MoodleApiLog',
                        'action' => 'index']
                ],

            ];
        }
        return $navMoodle;
    }

    //POCOR-7527
    private function getAdminstrationdataMgtNav()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::get('security_users');
        $userinfo = $users->find()->where([$users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId])->first();

        $SecurityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $securityRole = TableRegistry::get('security_roles');
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $uId])
            ->group([
                $GroupUsers->aliasField('security_group_id'),
                $GroupUsers->aliasField('security_role_id')
            ])
            ->select(['id' => 'SecurityRoles.id', 'role_name' => 'SecurityRoles.name'])
            ->all();
        $rowData = [];
        $rowId = [];
        foreach ($groupUserRecords as $obj) {
            $rowData[] = $obj->role_name;
            $rowId[] = $obj->id;
        }
        if (!empty($rowId)) {
            $SecurityMoodleFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin([$securityFunctions->alias() => $securityFunctions->table()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([$SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Archive', $SecurityRoleFunctions->aliasField('_view') => 1])->toArray();
        }
        $navdataMgt = [];
        if (empty($userinfo)) {
            if (!empty($SecurityScholarshipsFunctions)) {
                $navdataMgt = [
                    'Administration.Archive' => [
                        'title' => 'Data Management',
                        'parent' => 'Administration',
                        'link' => false,
                    ],
                    'Archive.Copy' => [
                        'title' => 'Copy',
                        'parent' => 'Administration.Archive',
                        'selected' => ['Archives.CopyData'],
                        'params' => ['plugin' => 'Archive', 'controller' => 'Archives',
                            'action' => 'CopyData'],
                    ],
                    'Archive.Backup' => [
                        'title' => 'Backup',
                        'parent' => 'Administration.Archive',
                        'selected' => ['Archives.BackupLog'],
                        'params' => ['plugin' => 'Archive', 'controller' => 'Archives',
                            'action' => 'BackupLog'],
                    ],
                    'Archive.Transfer' => [
                        'title' => 'Archive',
                        'parent' => 'Administration.Archive',
                        'params' => ['plugin' => 'Archive', 'controller' => 'Archives',
                            'action' => 'Transfer'],
                        'selected' => ['Archives.Transfer'],
                    ],
                ];
            }
        } else {
            $navdataMgt = [
                'Administration.Archive' => [
                    'title' => 'Data Management',
                    'parent' => 'Administration',
                    'link' => false,
                ],
                'Archive.Copy' => [
                    'title' => 'Copy',
                    'parent' => 'Administration.Archive',
                    'selected' => ['Archives.CopyData'],
                    'params' => ['plugin' => 'Archive', 'controller' => 'Archives',
                        'action' => 'CopyData'],
                ],
                'Archive.Backup' => [
                    'title' => 'Backup',
                    'parent' => 'Administration.Archive',
                    'selected' => ['Archives.BackupLog'],
                    'params' => ['plugin' => 'Archive', 'controller' => 'Archives',
                        'action' => 'BackupLog'],
                ],
                'Archive.Transfer' => [
                    'title' => 'Archive',
                    'parent' => 'Administration.Archive',
                    'params' => ['plugin' => 'Archive', 'controller' => 'Archives',
                        'action' => 'Transfer'],
                    'selected' => ['Archives.Transfer'],
                ],
            ];
        }
        return $navdataMgt;
    }

    /**
     * @return mixed
     */
    private function getCurrentUserId()
    {
        $session = $this->request->session();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $user_id = $this->controller->paramsDecode($userId)['id'];
        return $user_id;
    }

    /**
     * @param $user_id
     * @return array
     */
    private function getUserRoleIdArray($user_id)
    {
//        $this->log('user_id', 'debug');
//        $this->log($user_id, 'debug');
        $GroupUsers = TableRegistry::get('security_group_users');
        $distinctResults = $GroupUsers->find('all')
            ->where(['security_user_id' => $user_id])
            ->select(['security_role_id'])
            ->distinct(['security_role_id'])
            ->toArray();
//        $this->log($distinctResults, 'debug');
        $distinctResultsValues = array_column($distinctResults, 'security_role_id');
//        $this->log($distinctResultsValues, 'debug');
        $uniqu_array = array_unique($distinctResultsValues);
        if (sizeof($uniqu_array) == 0) {
            $uniqu_array = [0];
        }
        return $uniqu_array;
    }

    /**
     * @param $module
     * @param $category
     * @param $function
     * @param array $userRoleIdArray
     * @return boolean
     */
    private static function hasUserPermission($module, $category, $function, array $userRoleIdArray)
    {
        if (!is_array($category)) {
            $category = [$category];
        }
        $has_user_permission = false;
        $securityRoleFunctions = TableRegistry::get('security_role_functions');
        $securityFunctions = TableRegistry::get('security_functions');
        $SecurityTrainingFunctions = $securityRoleFunctions->find()
            ->InnerJoin([$securityFunctions->alias() => $securityFunctions->table()],
                [
                    $securityFunctions->aliasField('id = ') .
                    $securityRoleFunctions->aliasField('security_function_id'),
                    $securityFunctions->aliasField('module') => $module,
                    $securityFunctions->aliasField('controller IN') => $category
                ]
            )->where(
                [$securityRoleFunctions->aliasField('security_role_id IN') => $userRoleIdArray,
                    $securityRoleFunctions->aliasField($function) => 1]
            )
            ->first();
        if ($SecurityTrainingFunctions) {
            $has_user_permission = true;
        }
        return $has_user_permission;
    }

}
