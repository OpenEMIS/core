<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class NavigationComponent extends Component
{
    public $controller;
    public $action;
    public $breadcrumbs = [];

    public $components = ['AccessControl'];

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->action = $this->request->params['action'];
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.initialize'] = ['callable' => 'beforeFilter', 'priority' => '11'];
        return $events;
    }

    public function addCrumb($title, $options = [])
    {
        $item = array(
            'title' => __($title),
            'link' => ['url' => $options],
            'selected' => sizeof($options)==0
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
                    'selected' => sizeof($options)==0
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
        $navigations = $this->buildNavigation();
        $this->checkSelectedLink($navigations);
        $this->checkPermissions($navigations);
        $controller->set('_navigations', $navigations);
    }

    private function getLink($controllerActionModelLink, $params = [])
    {
        $url = ['plugin' => null, 'controller' => null, 'action' => null];
        if (isset($params['plugin'])) {
            $url['plugin'] = $params['plugin'];
            unset($params['plugin']);
        }
        $link = explode('.', $controllerActionModelLink);
        if (isset($link[0])) {
            $url['controller'] = $link[0];
        }
        if (isset($link[1])) {
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
        $linkOnly = [];

        $ignoredAction = ['Profiles']; // action that will be excluded from checking

        $roles = [];
        $restrictedTo = [];
        $event = $this->controller->dispatchEvent('Controller.Navigation.onUpdateRoles', null, $this);
        if ($event->result) {
            $roles = $event->result['roles'];
            $restrictedTo = $event->result['restrictedTo'];
        }

        // Unset the children
        foreach ($navigations as $key => $value) {
            $rolesRestrictedTo = $roles;
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
                        $rolesRestrictedTo = $roles;
                        break;
                    } else {
                        $rolesRestrictedTo = [];
                    }
                }

                // $ignoredAction will be excluded from permission checking
                if (array_key_exists('controller', $url) && !in_array($url['action'], $ignoredAction)) {
                    if (!$this->AccessControl->check($url, $rolesRestrictedTo)) {
                        unset($navigations[$key]);
                    }
                }
            }
        }
        // unset the parents if there is no children
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
        $linkName = $controller.'.'.$action;
        $controllerActionLink = $linkName;
        if (!empty($pass[0])) {
            $linkName .= '.'.$pass[0];
        }
        if (!in_array($linkName, $navigations)) {
            $selectedArray = $this->array_column($navigations, 'selected');
            foreach ($selectedArray as $k => $selected) {
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
                        'Institution.Competencies'
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
            }, $array);
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

        $institutionStudentActions = ['Students', 'StudentUser', 'StudentAccount', 'StudentSurveys', 'Students'];
        $institutionStaffActions = ['Staff', 'StaffUser', 'StaffAccount'];
        $institutionActions = array_merge($institutionStudentActions, $institutionStaffActions);

        if ($controller->name == 'Institutions' && $action != 'index' && (!in_array($action, $institutionActions))) {
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
        } elseif ($controller->name == 'Directories' && $action != 'index') {
            $navigations = $this->appendNavigation('Directories.Directories.index', $navigations, $this->getDirectoryNavigation());

            $session = $this->request->session();
            $isStudent = $session->read('Directory.Directories.is_student');
            $isStaff = $session->read('Directory.Directories.is_staff');

            if ($isStaff) {
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStaffNavigation());
                $session->write('Directory.Directories.reload', true);
            }

            if ($isStudent) {
                $navigations = $this->appendNavigation('Directories.Directories.view', $navigations, $this->getDirectoryStudentNavigation());
                $session->write('Directory.Directories.reload', true);
            }
        } else if ($controller->name == 'Profiles' && $action != 'index') {
            $navigations = $this->appendNavigation('Profiles.Profiles.index', $navigations, $this->getProfileNavigation());

            $session = $this->request->session();
            $isStudent = $session->read('Auth.User.is_student');
            $isStaff = $session->read('Auth.User.is_staff');

            if ($isStaff) {
                $navigations = $this->appendNavigation('Profiles.Profiles.view', $navigations, $this->getProfileStaffNavigation());
                $session->write('Profile.Profiles.reload', true);
            }

            if ($isStudent) {
                $navigations = $this->appendNavigation('Profiles.Profiles.view', $navigations, $this->getProfileStudentNavigation());
                $session->write('Profile.Profiles.reload', true);
            }
        }

        $navigations = $this->appendNavigation('Reports', $navigations, $this->getReportNavigation());
        $navigations = $this->appendNavigation('Administration', $navigations, $this->getAdministrationNavigation());

        return $navigations;
    }

    private function appendNavigation($key, $originalNavigation, $navigationToAppend)
    {
        $count = 0;
        foreach ($originalNavigation as $navigationKey => $navigationValue) {
            $count++;
            if ($navigationKey == $key) {
                break;
            }
        }
        $result = [];
        if ($count < count($originalNavigation)) {
            $result = array_slice($originalNavigation, 0, $count, true) + $navigationToAppend + array_slice($originalNavigation, $count, count($originalNavigation) - 1, true) ;
        } elseif ($count == count($originalNavigation)) {
            $result = $originalNavigation + $navigationToAppend;
        } else {
            $result = $originalNavigation;
        }
        return $result;
    }

    public function getMainNavigation()
    {
        $navigation = [
            'Profiles.Profiles.index' => [
                'title' => 'Profile',
                'icon' => '<span><i class="fa kd-role"></i></span>',
                'params' => ['plugin' => 'Profile'],
            ],

            'Institutions.Institutions.index' => [
                'title' => 'Institutions',
                'icon' => '<span><i class="fa kd-institutions"></i></span>',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.add', 'Institutions.ImportInstitutions.add', 'Institutions.ImportInstitutions.results']
            ],

            'Directories.Directories.index' => [
                'title' => 'Directory',
                'icon' => '<span><i class="fa kd-guardian"></i></span>',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.Directories.add', 'Directories.ImportUsers.add', 'Directories.ImportUsers.results']
            ],

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

        return $navigation;
    }

    public function getInstitutionNavigation()
    {
        $session = $this->request->session();
        $id = $this->controller->ControllerAction->paramsEncode(['id' => $session->read('Institution.Institutions.id')]);
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
                    'selected' => ['Institutions.Institutions.edit'],
                    'params' => ['plugin' => 'Institution', 0 => $institutionId]
                ],

                'Institutions.Contacts.view' => [
                    'title' => 'Contacts',
                    'parent' => 'Institution.General',
                    'selected' => ['Institutions.Contacts.view', 'Institutions.Contacts.edit'],
                    'params' => ['plugin' => 'Institution', 0 => $institutionId]
                ],

                'Institutions.Attachments.index' => [
                    'title' => 'Attachments',
                    'parent' => 'Institution.General',
                    'selected' => ['Institutions.Attachments'],
                    'params' => ['plugin' => 'Institution']
                ],

                'Institutions.History.index' => [
                    'title' => 'History',
                    'parent' => 'Institution.General',
                    'selected' => ['Institutions.History'],
                    'params' => ['plugin' => 'Institution']
                ],

            'Institution.Academic' => [
                'title' => 'Academic',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

                'Institutions.Shifts' => [
                    'title' => 'Shifts',
                    'parent' => 'Institution.Academic',
                    'selected' => ['Institutions.Shifts'],
                    'params' => ['plugin' => 'Institution']
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

                'Institutions.Textbooks' => [
                    'title' => 'Textbooks',
                    'parent' => 'Institution.Academic',
                    'selected' => ['Institutions.Textbooks', 'Institutions.ImportTextbooks'],
                    'params' => ['plugin' => 'Institution']
                ],

            'Institutions.Students.index' => [
                'title' => 'Students',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Students.add', 'Institutions.Students.addExisting', 'Institutions.TransferRequests', 'Institutions.Promotion', 'Institutions.Transfer', 'Institutions.Undo',
                    'Institutions.StudentAdmission', 'Institutions.TransferApprovals', 'Institutions.StudentWithdraw', 'Institutions.WithdrawRequests', 'Institutions.StudentUser.add',
                    'Institutions.ImportStudents', 'Institutions.Students'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Staff.index' => [
                'title' => 'Staff',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Staff.add', 'Institutions.StaffUser.add', 'Institutions.StaffUser.pull', 'Institutions.ImportStaff', 'Institutions.Staff', 'Institutions.StaffTransferRequests']
            ],

            'Institution.Attendance' => [
                'title' => 'Attendance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

                'Institutions.StudentAttendances.index' => [
                    'title' => 'Students',
                    'parent' => 'Institution.Attendance',
                    'selected' => ['Institutions.StudentAttendances', 'Institutions.StudentAbsences', 'Institutions.ImportStudentAttendances'],
                    'params' => ['plugin' => 'Institution']
                ],

                'Institutions.StaffAttendances.index' => [
                    'title' => 'Staff',
                    'parent' => 'Institution.Attendance',
                    'selected' => ['Institutions.StaffAttendances', 'Institutions.StaffAbsences', 'Institutions.ImportStaffAttendances'],
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
                    'selected' => ['Institutions.StudentBehaviours'],
                    'params' => ['plugin' => 'Institution']
                ],

                'Institutions.StaffBehaviours.index' => [
                    'title' => 'Staff',
                    'parent' => 'Institution.Behaviour',
                    'selected' => ['Institutions.StaffBehaviours'],
                    'params' => ['plugin' => 'Institution']
                ],

            'Institutions.StudentCompetencies' => [
                'title' => 'Competencies',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.StudentCompetencies', 'Institutions.StudentCompetencyResults'],
                'params' => ['plugin' => 'Institution']
            ],

            'Institutions.Assessments.index' => [
                'title' => 'Assessments',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Assessments', 'Institutions.Results'],
                'params' => ['plugin' => 'Institution'],
            ],

            'Institutions.Indexes.index' => [
                'title' => 'Indexes',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Indexes','Institutions.InstitutionStudentIndexes'],
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
					'selected' => ['Institutions.ReportCardComments','Institutions.Comments'],
				],

				'Institutions.ReportCardStatuses' => [
					'title' => 'Statuses',
					'parent' => 'Institutions.ReportCards',
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.ReportCardStatuses'],
				],

            'Institutions.Positions' => [
                'title' => 'Positions',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.Positions'],
            ],

            'Institution.Finance' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

                'Institutions.BankAccounts' => [
                    'title' => 'Bank Accounts',
                    'parent' => 'Institution.Finance',
                    'params' => ['plugin' => 'Institution'],
                    'selected' => ['Institutions.BankAccounts'],
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

            'Institutions.InstitutionLands' => [
                'title' => 'Infrastructures',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.InstitutionLands', 'Institutions.InstitutionBuildings', 'Institutions.InstitutionFloors', 'Institutions.InstitutionRooms']
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
                    'selected' => ['Institutions.Surveys', 'Institutions.ImportInstitutionSurveys'],
                ],

                'Institutions.Rubrics' => [
                    'title' => 'Rubrics',
                    'parent' => 'Survey',
                    'params' => ['plugin' => 'Institution'],
                    'selected' => ['Institutions.Rubrics', 'Institutions.RubricAnswers'],
                ],

            'Institutions.VisitRequests' => [
                'title' => 'Visits',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.VisitRequests', 'Institutions.Visits']
            ],

            'Institutions.Cases' => [
                'title' => 'Cases',
                'parent' => 'Institutions.Institutions.index',
                'params' => ['plugin' => 'Institution']
            ]
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
        $id = !empty($this->controller->ControllerAction->getQueryString('institution_student_id')) ? $this->controller->ControllerAction->getQueryString('institution_student_id') :$session->read('Institution.Students.id');
        $studentId = $session->read('Student.Students.id');
        $institutionIdSession = $this->controller->ControllerAction->paramsEncode(['id' => $session->read('Institution.Institutions.id')]);
        $institutionId = isset($this->request->params['institutionId']) ? $this->request->params['institutionId'] : $institutionIdSession;
        $queryString = $this->controller->ControllerAction->paramsEncode(['institution_id' => $this->controller->ControllerAction->paramsDecode($institutionId)['id'], 'institution_student_id' => $id]);
        $navigation = [
            'Institutions.StudentUser.view' => [
                'title' => 'General',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Institution', '1' => $this->controller->ControllerAction->paramsEncode(['id' => $studentId]), 'queryString' => $queryString],
                'selected' => ['Institutions.StudentUser.edit', 'Institutions.StudentAccount.view', 'Institutions.StudentAccount.edit', 'Institutions.StudentSurveys', 'Institutions.StudentSurveys.edit', 'Institutions.IndividualPromotion',
                    'Students.Identities', 'Students.Nationalities', 'Students.Contacts', 'Students.Guardians', 'Students.Languages', 'Students.SpecialNeeds', 'Students.Attachments', 'Students.Comments',
                    'Students.History', 'Students.GuardianUser', 'Institutions.StudentUser.pull', 'Students.StudentSurveys']],
            'Institutions.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Students.Classes', 'Students.Subjects', 'Students.Absences', 'Students.Behaviours', 'Students.Results', 'Students.ExaminationResults', 'Students.ReportCards', 'Students.Awards',
                    'Students.Extracurriculars', 'Institutions.StudentTextbooks', 'Institutions.Students.view', 'Institutions.Students.edit', 'Institutions.StudentIndexes']],
            'Students.BankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.StudentFees']],
            'Students.Healths' => [
                'title' => 'Health',
                'parent' => 'Institutions.Students.index',
                'params' => ['plugin' => 'Student'],
                'selected' => ['Students.Healths', 'Students.HealthAllergies', 'Students.HealthConsultations', 'Students.HealthFamilies', 'Students.HealthHistories', 'Students.HealthImmunizations', 'Students.HealthMedications', 'Students.HealthTests']],
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
        $institutionIdSession = $this->controller->ControllerAction->paramsEncode(['id' => $session->read('Institution.Institutions.id')]);
        $institutionId = isset($this->request->params['institutionId']) ? $this->request->params['institutionId'] : $institutionIdSession;
        $id = $session->read('Staff.Staff.id');
        $navigation = [
            'Institutions.StaffUser.view' => [
                'title' => 'General',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Institution', '1' => $this->controller->ControllerAction->paramsEncode(['id' => $id])],
                'selected' => ['Institutions.StaffUser.edit', 'Institutions.StaffAccount', 'Staff.Identities', 'Staff.Nationalities',
                    'Staff.Contacts', 'Staff.Guardians', 'Staff.Languages', 'Staff.SpecialNeeds', 'Staff.Attachments', 'Staff.Comments', 'Staff.History']
            ],
            'Staff.Employments' => [
                'title' => 'Career',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.Employments', 'Staff.Positions', 'Staff.Classes', 'Staff.Subjects', 'Staff.Absences',
                    'Institutions.StaffLeave', 'Staff.Behaviours', 'Staff.Awards', 'Institutions.Staff.edit', 'Institutions.Staff.view', 'Institutions.StaffPositionProfiles.add'],
            ],
            'Staff.Qualifications' => [
                'title' => 'Professional Development',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.Qualifications', 'Staff.Extracurriculars', 'Staff.Memberships', 'Staff.Licenses', 'Institutions.StaffAppraisals'],
            ],
            'Staff.BankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.BankAccounts', 'Staff.Salaries', 'Staff.ImportSalaries']
            ],
            'Institutions.StaffTrainingNeeds' => [
                'title' => 'Training',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Institution'],
                'selected' => ['Institutions.StaffTrainingNeeds', 'Institutions.StaffTrainingApplications', 'Institutions.StaffTrainingResults', 'Institutions.CourseCatalogue', 'Staff.Courses',],
            ],
            'Staff.Healths' => [
                'title' => 'Health',
                'parent' => 'Institutions.Staff.index',
                'params' => ['plugin' => 'Staff'],
                'selected' => ['Staff.Healths', 'Staff.HealthAllergies', 'Staff.HealthConsultations', 'Staff.HealthFamilies', 'Staff.HealthHistories', 'Staff.HealthImmunizations', 'Staff.HealthMedications', 'Staff.HealthTests']
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
        $navigation = [
            'Profiles.Profiles.view' => [
                'title' => 'General',
                'parent' => 'Profiles.Profiles.index',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.Profiles.view', 'Profiles.Profiles.edit', 'Profiles.Profiles.pull', 'Profiles.Accounts', 'Profiles.Identities', 'Profiles.Nationalities', 'Profiles.Languages', 'Profiles.Comments', 'Profiles.Attachments',
                    'Profiles.History', 'Profiles.SpecialNeeds', 'Profiles.Contacts']
            ],
            'Profiles.Healths' => [
                'title' => 'Health',
                'parent' => 'Profiles.Profiles.index',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.Healths', 'Profiles.HealthAllergies', 'Profiles.HealthConsultations', 'Profiles.HealthFamilies', 'Profiles.HealthHistories', 'Profiles.HealthImmunizations', 'Profiles.HealthMedications', 'Profiles.HealthTests']
            ]
        ];
        return $navigation;
    }

    public function getDirectoryNavigation()
    {
        $navigation = [
            'Directories.Directories.view' => [
                'title' => 'General',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.Directories.view', 'Directories.Directories.edit', 'Directories.Directories.pull', 'Directories.Accounts', 'Directories.Identities', 'Directories.Nationalities', 'Directories.Languages', 'Directories.Comments', 'Directories.Attachments',
                    'Directories.History', 'Directories.SpecialNeeds', 'Directories.Contacts']
            ],
            'Directories.Healths' => [
                'title' => 'Health',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.Healths', 'Directories.HealthAllergies', 'Directories.HealthConsultations', 'Directories.HealthFamilies', 'Directories.HealthHistories', 'Directories.HealthImmunizations', 'Directories.HealthMedications', 'Directories.HealthTests']
            ]
        ];
        return $navigation;
    }

    public function getProfileStaffNavigation()
    {
        $navigation = [
            'Profiles.Staff' => [
                'title' => 'Staff',
                'parent' => 'Profiles.Profiles.index',
                'link' => false,
            ],
                'Profiles.StaffEmployments' => [
                    'title' => 'Career',
                    'parent' => 'Profiles.Staff',
                    'params' => ['plugin' => 'Profile'],
                    'selected' => ['Profiles.StaffEmployments', 'Profiles.StaffPositions', 'Profiles.StaffClasses', 'Profiles.StaffSubjects', 'Profiles.StaffAbsences', 'Profiles.StaffLeave', 'Profiles.StaffBehaviours', 'Profiles.StaffAwards']
                ],
                'Profiles.StaffQualifications' => [
                    'title' => 'Professional Development',
                    'parent' => 'Profiles.Staff',
                    'params' => ['plugin' => 'Profile'],
                    'selected' => ['Profiles.StaffQualifications', 'Profiles.StaffExtracurriculars', 'Profiles.StaffMemberships', 'Profiles.StaffLicenses', 'Profiles.StaffAppraisals']
                ],
                'Profiles.StaffBankAccounts' => [
                    'title' => 'Finance',
                    'parent' => 'Profiles.Staff',
                    'params' => ['plugin' => 'Profile', 'type' => 'staff'],
                    'selected' => ['Profiles.StaffBankAccounts', 'Profiles.StaffSalaries', 'Profiles.ImportSalaries']
                ],
                'Profiles.TrainingNeeds' => [
                    'title' => 'Training',
                    'parent' => 'Profiles.Staff',
                    'params' => ['plugin' => 'Profile'],
                    'selected' => ['Profiles.TrainingNeeds', 'Profiles.TrainingResults', 'Profiles.Courses']
                ],
        ];
        return $navigation;
    }

    public function getProfileStudentNavigation()
    {
        $navigation = [
            'Profiles.Student' => [
                'title' => 'Student',
                'parent' => 'Profiles.Profiles.index',
                'link' => false,
            ],
                'Profiles.ProfileGuardians' => [
                    'title' => 'Guardians',
                    'parent' => 'Profiles.Student',
                    'params' => ['plugin' => 'Profile'],
                    'selected' => ['Profiles.ProfileGuardians', 'Profiles.ProfileGuardianUser']
                ],
                'Profiles.StudentProgrammes.index' => [
                    'title' => 'Academic',
                    'parent' => 'Profiles.Student',
                    'params' => ['plugin' => 'Profile'],
                    'selected' => ['Profiles.StudentProgrammes.index', 'Profiles.StudentSubjects', 'Profiles.StudentClasses', 'Profiles.StudentAbsences', 'Profiles.StudentBehaviours',
                        'Profiles.StudentResults', 'Profiles.StudentExaminationResults', 'Profiles.StudentReportCards', 'Profiles.StudentAwards', 'Profiles.StudentExtracurriculars', 'Profiles.StudentTextbooks']
                ],
                'Profiles.StudentBankAccounts' => [
                    'title' => 'Finance',
                    'parent' => 'Profiles.Student',
                    'params' => ['plugin' => 'Profile', 'type' => 'student'],
                    'selected' => ['Profiles.StudentBankAccounts', 'Profiles.StudentFees']
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
                'Directories.StaffEmployments' => [
                    'title' => 'Career',
                    'parent' => 'Directories.Staff',
                    'params' => ['plugin' => 'Directory'],
                    'selected' => ['Directories.StaffEmployments', 'Directories.StaffPositions', 'Directories.StaffClasses', 'Directories.StaffSubjects', 'Directories.StaffAbsences', 'Directories.StaffLeave', 'Directories.StaffBehaviours', 'Directories.StaffAwards']
                ],
                'Directories.StaffQualifications' => [
                    'title' => 'Professional Development',
                    'parent' => 'Directories.Staff',
                    'params' => ['plugin' => 'Directory'],
                    'selected' => ['Directories.StaffQualifications', 'Directories.StaffExtracurriculars', 'Directories.StaffMemberships', 'Directories.StaffLicenses', 'Directories.StaffAppraisals']
                ],
                'Directories.StaffBankAccounts' => [
                    'title' => 'Finance',
                    'parent' => 'Directories.Staff',
                    'params' => ['plugin' => 'Directory', 'type' => 'staff'],
                    'selected' => ['Directories.StaffBankAccounts', 'Directories.StaffSalaries', 'Directories.ImportSalaries']
                ],
                'Directories.TrainingNeeds' => [
                    'title' => 'Training',
                    'parent' => 'Directories.Staff',
                    'params' => ['plugin' => 'Directory'],
                    'selected' => ['Directories.TrainingNeeds', 'Directories.TrainingResults', 'Directories.Courses']
                ],
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
                    'selected' => ['Directories.StudentGuardians', 'Directories.StudentGuardianUser']
                ],
                'Directories.StudentProgrammes.index' => [
                    'title' => 'Academic',
                    'parent' => 'Directories.Student',
                    'params' => ['plugin' => 'Directory'],
                    'selected' => ['Directories.StudentProgrammes.index', 'Directories.StudentSubjects', 'Directories.StudentClasses', 'Directories.StudentAbsences', 'Directories.StudentBehaviours',
                        'Directories.StudentResults', 'Directories.StudentExaminationResults', 'Directories.StudentReportCards', 'Directories.StudentAwards', 'Directories.StudentExtracurriculars', 'Directories.StudentTextbooks']
                ],
                'Directories.StudentBankAccounts' => [
                    'title' => 'Finance',
                    'parent' => 'Directories.Student',
                    'params' => ['plugin' => 'Directory', 'type' => 'student'],
                    'selected' => ['Directories.StudentBankAccounts', 'Directories.StudentFees']
                ],
        ];
        return $navigation;
    }

    public function getReportNavigation()
    {
        $navigation = [
            'Reports.Institutions' => [
                'title' => 'Institutions',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
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
            'Reports.Surveys' => [
                'title' => 'Surveys',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.InstitutionRubrics' => [
                'title' => 'Quality',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Reports.DataQuality' => [
                'title' => 'Data Quality',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report']
            ],
            'Reports.Audit' => [
                'title' => 'Audit',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Report'],
            ],
            'Map.index' => [
                'title' => 'Map',
                'parent' => 'Reports',
                'params' => ['plugin' => 'Map'],
            ],
        ];
        return $navigation;
    }

    public function getAdministrationNavigation()
    {
        $queryString = $this->request->query('queryString');
        $navigation = [
            'SystemSetup' => [
                'title' => 'System Setup',
                'parent' => 'Administration',
                'link' => false,
            ],
                'Areas.Areas' => [
                    'title' => 'Administrative Boundaries',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Area'],
                    'selected' => ['Areas.Areas', 'Areas.Levels', 'Areas.AdministrativeLevels', 'Areas.Administratives']
                ],
                'AcademicPeriods.Periods' => [
                    'title' => 'Academic Periods',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'AcademicPeriod'],
                    'selected' => ['AcademicPeriods.Periods', 'AcademicPeriods.Levels']
                ],
                'Educations.Systems' => [
                    'title' => 'Education Structure',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Education'],
                    'selected' => ['Educations.Systems', 'Educations.Levels', 'Educations.Cycles', 'Educations.Programmes', 'Educations.Grades', 'Educations.Stages', 'Educations.Subjects', 'Educations.GradeSubjects', 'Educations.Certifications',
                            'Educations.FieldOfStudies', 'Educations.ProgrammeOrientations']
                ],
                'FieldOptions.index' => [
                    'title' => 'Field Options',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'FieldOption'],
                    'selected' => ['FieldOptions.index', 'FieldOptions.add', 'FieldOptions.view', 'FieldOptions.edit', 'FieldOptions.remove']
                ],
                'SystemSetup.CustomField' => [
                    'title' => 'Custom Field',
                    'parent' => 'SystemSetup',
                    'link' => false,
                ],
                    'InstitutionCustomFields.Fields' => [
                        'title' => 'Institution',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'InstitutionCustomField'],
                        'selected' => ['InstitutionCustomFields.Fields', 'InstitutionCustomFields.Pages']
                    ],
                    'StudentCustomFields.Fields' => [
                        'title' => 'Student',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'StudentCustomField'],
                        'selected' => ['StudentCustomFields.Fields', 'StudentCustomFields.Pages']
                    ],
                    'StaffCustomFields.Fields' => [
                        'title' => 'Staff',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'StaffCustomField'],
                        'selected' => ['StaffCustomFields.Fields', 'StaffCustomFields.Pages']
                    ],
                    'Infrastructures.Fields' => [
                        'title' => 'Infrastructure',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'Infrastructure'],
                        'selected' => ['Infrastructures.Fields', 'Infrastructures.LandPages', 'Infrastructures.BuildingPages', 'Infrastructures.FloorPages', 'Infrastructures.RoomPages']
                    ],
                'Labels.index' => [
                    'title' => 'Labels',
                    'parent' => 'SystemSetup',
                    'selected' => ['Labels.index', 'Labels.view', 'Labels.edit']
                ],

                'Translations.index' => [
                    'title' => 'Translations',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Localization'],
                    'selected' => ['Translations.add', 'Translations.view', 'Translations.edit']
                ],
                'Configurations.index' => [
                    'title' => 'System Configurations',
                    'parent' => 'SystemSetup',
                    'selected' => ['Configurations.index', 'Configurations.add', 'Configurations.view', 'Configurations.edit']
                ],
                'Notices.index' => [
                    'title' => 'Notices',
                    'parent' => 'SystemSetup',
                    'selected' => ['Notices.index', 'Notices.add', 'Notices.view', 'Notices.edit']
                ],
                'Indexes.Indexes' => [
                    'title' => 'Indexes',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Indexes'],
                    'selected' => ['Indexes.Indexes']
                ],
            'Security' => [
                'title' => 'Security',
                'parent' => 'Administration',
                'link' => false,
            ],

                'Securities.Users' => [
                    'title' => 'Users',
                    'parent' => 'Security',
                    'params' => ['plugin' => 'Security'],
                    'selected' => ['Securities.Users', 'Securities.Accounts']
                ],

                'Securities.UserGroups' => [
                    'title' => 'Groups',
                    'parent' => 'Security',
                    'params' => ['plugin' => 'Security'],
                    'selected' => ['Securities.UserGroups', 'Securities.SystemGroups']
                ],

                'Securities.Roles' => [
                    'title' => 'Roles',
                    'parent' => 'Security',
                    'params' => ['plugin' => 'Security'],
                    'selected' => ['Securities.Roles', 'Securities.Permissions']
                ],

            'Administration.Survey' => [
                'title' => 'Survey',
                'parent' => 'Administration',
                'link' => false,
            ],

                'Surveys.Questions' => [
                    'title' => 'Forms',
                    'parent' => 'Administration.Survey',
                    'params' => ['plugin' => 'Survey'],
                    'selected' => ['Surveys.Questions', 'Surveys.Forms', 'Surveys.Rules', 'Surveys.Status']
                ],

                'Rubrics.Templates' => [
                    'title' => 'Rubrics',
                    'parent' => 'Administration.Survey',
                    'params' => ['plugin' => 'Rubric'],
                    'selected' => ['Rubrics.Sections', 'Rubrics.Criterias', 'Rubrics.Options', 'Rubrics.Status']
                ],

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
                    'selected' => ['Trainings.Sessions', 'Trainings.Applications', 'Trainings.ImportTrainees']
                ],

                'Trainings.Results' => [
                    'title' => 'Results',
                    'parent' => 'Administration.Training',
                    'params' => ['plugin' => 'Training'],
                    'selected' => ['Trainings.Results']
                ],
            'Assessments.Assessments' => [
                'title' => 'Assessments',
                'parent' => 'Administration',
                'params' => ['plugin' => 'Assessment'],
                'selected' => ['Assessments.Assessments', 'Assessments.AssessmentPeriods', 'Assessments.GradingTypes']
            ],
            'Administration.Competencies' => [
                'title' => 'Competencies',
                'parent' => 'Administration',
                'link' => false,
                'params' => ['plugin' => 'Competency'],
            ],
                'Competencies.Templates' => [
                    'title' => 'Templates',
                    'parent' => 'Administration.Competencies',
                    'params' => ['plugin' => 'Competency'],
                    'selected' => ['Competencies.Templates', 'Competencies.Items', 'Competencies.Criterias']
                ],
                'Competencies.Periods' => [
                    'title' => 'Periods',
                    'parent' => 'Administration.Competencies',
                    'params' => ['plugin' => 'Competency'],
                    'selected' => ['Competencies.Periods']
                ],
                'Competencies.GradingTypes' => [
                    'title' => 'Grading Types',
                    'parent' => 'Administration.Competencies',
                    'params' => ['plugin' => 'Competency'],
                    'selected' => ['Competencies.GradingTypes']
                ],

            'Administration.Examinations' => [
                    'title' => 'Examinations',
                    'parent' => 'Administration',
                    'link' => false,
                ],
                    'Examinations.Exams' => [
                        'title' => 'Exams',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.Exams', 'Examinations.GradingTypes']
                    ],
                    'Examinations.ExamCentres'  => [
                        'title' => 'Centres',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.ExamCentres', 'Examinations.ExamCentreRooms', 'Examinations.ExamCentreExams', 'Examinations.ExamCentreSubjects', 'Examinations.ExamCentreStudents', 'Examinations.ExamCentreInvigilators', 'Examinations.ExamCentreLinkedInstitutions', 'Examinations.ImportExaminationCentreRooms']
                    ],
                    'Examinations.RegisteredStudents' => [
                        'title' => 'Students',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.RegisteredStudents', 'Examinations.RegistrationDirectory', 'Examinations.NotRegisteredStudents']
                    ],
                    'Examinations.ExamResults' => [
                        'title' => 'Results',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => ['Examinations.ExamResults', 'Examinations.Results', 'Examinations.ImportResults']
                    ],
            'Textbooks.Textbooks' => [
                'title' => 'Textbooks',
                'parent' => 'Administration',
                'params' => ['plugin' => 'Textbook'],
                'selected' => ['Textbooks.Textbooks']
            ],
            'ReportCards.Templates' => [
                'title' => 'Report Cards',
                'parent' => 'Administration',
                'params' => ['plugin' => 'ReportCard'],
                'selected' => ['ReportCards.Templates']
            ],
            'Workflows.Workflows' => [
                'title' => 'Workflow',
                'parent' => 'Administration',
                'params' => ['plugin' => 'Workflow'],
                'selected' => ['Workflows.Workflows', 'Workflows.Steps', 'Workflows.Actions', 'Workflows.Rules', 'Workflows.Statuses']
            ],
            'Systems.Updates' => [
                'title' => 'Updates',
                'parent' => 'Administration',
                'params' => ['plugin' => 'System']
            ]
        ];
        return $navigation;
    }
}
