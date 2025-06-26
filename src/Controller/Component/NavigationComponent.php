<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class NavigationComponent extends Component
{
    public $controller;
    public $action;
    public $breadcrumbs = [];

    public $components = ['AccessControl'];

    public function initialize(array $config): void
    {
        $this->controller = $this->_registry->getController();
        $this->action = $this->getController()->getRequest()->getParam('action');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.initialize'] = [
            'callable' => 'beforeFilter',
            'priority' => '11'
        ];
        return $events;
    }

    public function addCrumb($title, $options = [])
    {
        if (!is_countable($options)) {
            $size_of_options = 0;
        } else {
            $size_of_options = sizeof($options);
        }
        $item = array(
            'title' => __((string)$title),
            'link' => ['url' => $options],
            'selected' => $size_of_options == 0
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

    public function beforeFilter(EventInterface $event)
    {
        $controller = $this->controller;
        $session = $this->getController()->getRequest()->getSession();
        $isUserId = $session->read('Auth.User.id');

        if (isset($isUserId)) {
            //          POCOR-8989 start
            try {
                $navigations = $this->buildNavigation();
            } catch (SecurityException $s_ex) {
                Log::debug('SecurityException: ' . $s_ex->getMessage());
                return $s_ex;
            } catch (\Exception $ex) {
                Log::debug('Exception: ' . $ex->getMessage());
                return $ex;
            }
            try {
                $this->checkSelectedLink($navigations);
            } catch (SecurityException $s_ex) {
                Log::debug('SecurityException: ' . $s_ex->getMessage());
                return $s_ex;
            } catch (\Exception $ex) {
                Log::debug('Exception: ' . $ex->getMessage());
                return $ex;
            }
            try {
                $this->checkPermissions($navigations);
            } catch (SecurityException $s_ex) {
                Log::debug('SecurityException: ' . $s_ex->getMessage());
                return $s_ex;
            } catch (\Exception $ex) {
                Log::debug('Exception: ' . $ex->getMessage());
                return $ex;
            }
            $controller->set('_navigations', $navigations);
            //          POCOR-8989 end
        }
    }

    public function buildNavigation()
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        // POCOR-8989 start
        $this->request = $request;
        $session = $request->getSession();
        $authUserId = $session->read('Auth.User.id');
        if (isset($authUserId)) {
            // POCOR-8989 end
            //$navigations = $this->getNavigation();
            $navigations = $this->getMainNavigation();

            $action = $this->action;

            $institutionStudentActions = [
                'Students',
                'StudentUser',
                'StudentAccount',
                'StudentSurveys',
                'Students'
            ];
            $institutionStaffActions = [
                'Staff',
                'StaffUser',
                'StaffAccount'
            ];
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
                'GuardianComments',
                'InstitutionStandards'
            ];

            $profileControllers = [
                'ProfileBodyMasses',
                'ProfileComments',
                'ProfileInsurances',
                'ScholarshipsDirectory',
                'ProfileApplicationInstitutionChoices',
                'ProfileApplicationAttachments'
            ];
            $directoryControllers = [
                'DirectoryBodyMasses',
                'DirectoryComments',
                'DirectoryInsurances'
            ];
            // POCOR-8989 start
            $controllerName = $controller->getName();
            if (
                in_array($controllerName, $institutionControllers) || (
                    $controllerName == 'Institutions'
                    && $action != 'index'
                    && (!in_array($action, $institutionActions))
                )
            ) {
                $navigations = $this->makeInstitutionNavigations($navigations);
            }
            if (($controllerName == 'Students' && $action != 'index')
                || ($controllerName == 'Institutions' && in_array($action, $institutionStudentActions))
            ) {
                $navigations = $this->makeStudentNavigations($navigations);
            }
            if (($controllerName == 'Staff' && $action != 'index')
                || ($controllerName == 'Institutions' && in_array($action, $institutionStaffActions))
            ) {
                $navigations = $this->makeStaffNavigations($navigations);
            }
            if (($controllerName == 'Directories' && $action != 'index') || in_array($controllerName, $directoryControllers)) {

                $navigations = $this->makeDirectoryNavigations($navigations);
            }
            if (($controllerName == 'Profiles' && $action != 'index') ||
                in_array($controllerName, $profileControllers)
            ) {
                $navigations = $this->makeProfileNavigations($navigations, $request);
            }
            if (($controllerName == 'GuardianNavs' && $action != 'index')) {
                $navigations = $this->makeGuardianNavigations($navigations);
            }
            // POCOR-8989 end
            $navigations = $this->appendNavigation('Reports', $navigations, $this->getReportNavigation());
            $navigations = $this->appendNavigation('Administration', $navigations, $this->getAdministrationNavigation());
            return $navigations;
        }
    }

    public function getMainNavigation()
    {
        /*POCOR-6267 Starts*/
        $controller = $this->getController();
        $request = $controller->getRequest();
        $session = $request->getSession();
        $user_id = $session->read('Auth.User.id');
        $encoded_user_id = $this->controller->paramsEncode([
            'id' => $user_id,
            'user_id' => $user_id
        ]);

        if (isset($user_id)) {
            $userInfo = TableRegistry::get('User.Users')->get($user_id);
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
                'params' => [
                    'plugin' => 'Profile',
                    'action' => 'Personal',
                    0 => 'view',
                    $encoded_user_id
                ]
            ]
        ];

        $navigation = [
            'Institutions.Institutions.index' => [
                'title' => 'Institutions',
                'icon' => '<span><i class="fa kd-institutions"></i></span>',
                'params' => ['plugin' => 'Institution'],
                'selected' => [
                    'Institutions.add',
                    'Institutions.ImportInstitutions.add',
                    'Institutions.ImportInstitutions.results'
                ]
            ],

            'Directories.Directories.index' => [
                'title' => 'Directory',
                'icon' => '<span><i class="fa kd-guardian"></i></span>',
                'params' => ['plugin' => 'Directory'],
                'selected' => [
                    'Directories.Directories.add',
                    'Directories.ImportUsers.add',
                    'Directories.ImportUsers.results',
                    'DirectoryHistories.index'
                ]
            ],


        ];

        $navigationToAppends = $this->getReportAdminstrationNavigation($user_id); //POCOR-7527
        /*POCOR-6267 Starts*/
        if (isset($newNavigation)) {
            $navigation = array_merge($PersonalNavigation, $newNavigation, $navigation, $navigationToAppends);
        } else {
            $navigation = array_merge($PersonalNavigation, $navigation, $navigationToAppends);
        }
        /*POCOR-6267 Ends*/
        return $navigation;
    }

    /**
     * POCOR-7527
     * seperate Report, Adminstration menu . creationg issue while provide permission
     * these two left menu are not having link
     */
    private function getReportAdminstrationNavigation($user_id)
    {
        $users = TableRegistry::get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $user_id
        ])->first();
        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $groupUserRecords = $GroupUsers->find()
            ->matching('SecurityGroups')
            ->matching('SecurityRoles')
            ->where([$GroupUsers->aliasField('security_user_id') => $user_id])
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('module') => 'Reports',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
            $SecurityAdminFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('module') => 'Administration',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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

    public function getInstitutionNavigation()
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $pass = $request->getParam('pass');
        $action = $request->getParam('action');
        $controllerName = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        if (
            $pass[0] == 'index'
            && ($action == 'Institutions')
            && ($plugin == 'Institution')
            && ($controllerName == 'Institutions')
        ) {
            return [];
        }

        //POCOR-9033 start
        $LabelTable = TableRegistry::getTableLocator()->get('Labels');
        $label = $LabelTable->find()->where(['module_name' => 'Institutions>Survey', 'field_name' => 'Survey'])->first();
        if (!empty($label) && $label->name) {
            $label = $label->name;
        } else {
            $label = 'Survey';
        }
        //POCOR-9033 end


        $navigation = [
            'Institutions.dashboard' => [
                'title' => 'Dashboard',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.dashboard'],
            ],
            'Institution.General' => [
                'title' => 'General',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.Institutions.view' => [
                'title' => 'Overview',
                'parent' => 'Institution.General',
                'selected' => [
                    'Institutions.Institutions.edit',
                    'Institutions.InstitutionStatus.edit',
                    'Institutions.InstitutionStatus.view'
                ],
            ],
            'Institutions.InstitutionMaps.view' => [
                'title' => 'Map',
                'parent' => 'Institution.General',
                'selected' => [
                    'Institutions.InstitutionMaps.view',
                    'Institutions.InstitutionMaps.edit'
                ],
            ],

            'Institutions.InstitutionCalendars.index' => [
                'title' => 'Calendar',
                'parent' => 'Institution.General',
                'selected' => [
                    'Institutions.InstitutionCalendars.view',
                    'Institutions.InstitutionCalendars.add',
                    'Institutions.InstitutionCalendars.edit',
                    'Institutions.InstitutionCalendars.delete'
                ]
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
                'selected' => [
                    'Institutions.Contacts.view',
                    'Institutions.Contacts.edit'
                ],
            ],

            'Institutions.InstitutionContactPersons.index' => [
                'title' => 'People',
                'parent' => 'Contacts',
                'selected' => [
                    'Institutions.InstitutionContactPersons',
                    'Institutions.InstitutionContactPersons.view',
                    'Institutions.InstitutionContactPersons.add',
                    'Institutions.InstitutionContactPersons.edit',
                    'Institutions.InstitutionContactPersons.delete'
                ],
            ],

            'Institutions.Attachments.index' => [
                'title' => 'Attachments',
                'parent' => 'Institution.General',
                'selected' => ['Institutions.Attachments'],
            ],
            /*POCOR-6286 starts*/
            'Profile' => [
                'title' => 'Profiles',
                'parent' => 'Institution.General',
                'link' => false
            ],
            //POCOR-6653 - updated Institutions selected function to get correct page
            'Institutions.InstitutionProfiles.index' => [
                'title' => 'Institutions',
                'parent' => 'Profile',
                'selected' => ['Institutions.InstitutionProfiles.index'],
            ],
            /*POCOR-6966 starts*/
            'Institutions.ClassesProfiles.index' => [
                'title' => 'Classes',
                'parent' => 'Profile',
                'selected' => ['Institutions.ClassesProfiles.index'],
            ],/*POCOR-6966 ends*/
            //POCOR-6654 modified staff menu
            'Institutions.StaffProfiles.index' => [
                'title' => 'Staff',
                'parent' => 'Profile',
                'selected' => ['Institutions.StaffProfiles.index'],
            ],
            //POCOR-6655 modified Studentes nav
            'Institutions.StudentProfiles.index' => [
                'title' => 'Students',
                'parent' => 'Profile',
                'selected' => ['Institutions.StudentProfiles.index'],
            ],
            /*POCOR-6286 ends*/
            'Institutions.Shifts.index' => [
                'title' => 'Shifts',
                'parent' => 'Institution.General',
                'selected' => ['Institutions.Shifts'],
            ],
            'Institution.Academic' => [
                'title' => 'Academic',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.Programmes.index' => [
                'title' => 'Programmes',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Programmes'],
            ],

            'Institutions.Classes.index' => [
                'title' => 'Classes',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Classes'],
            ],

            'Institutions.Subjects.index' => [
                'title' => 'Subjects',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Subjects'],
            ],

            'Institutions.Schedules' => [
                'title' => 'Schedules',
                'parent' => 'Institution.Academic',
                'link' => false
            ],

            'Institutions.ScheduleTimetableOverview.index' => [
                'title' => 'Timetables',
                'parent' => 'Institutions.Schedules',
                'selected' => [
                    'Institutions.ScheduleTimetableOverview',
                    'Institutions.ScheduleTimetable'
                ],
            ],

            'Institutions.ScheduleIntervals.index' => [
                'title' => 'Intervals',
                'parent' => 'Institutions.Schedules',
                'selected' => ['Institutions.ScheduleIntervals'],
            ],

            'Institutions.ScheduleTerms.index' => [
                'title' => 'Terms',
                'parent' => 'Institutions.Schedules',
                'selected' => ['Institutions.ScheduleTerms'],
            ],

            'Institutions.Textbooks.index' => [
                'title' => 'Textbooks',
                'parent' => 'Institution.Academic',
                'selected' => [
                    'Institutions.Textbooks',
                    'Institutions.ImportInstitutionTextbooks'
                ],
            ],

            'Institutions.Associations.index' => [
                'title' => 'Houses',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.Associations'],
            ],

            'Institutions.InstitutionCurriculars.index' => [ //POCOR-6673
                'title' => 'Institution Curriculars',
                'parent' => 'Institution.Academic',
                'selected' => ['Institutions.InstitutionCurriculars', 'Institutions.InstitutionCurricularStudents'],
                'action' => 'index',
            ],

            'Institution.Feeders' => [
                'title' => 'Feeders',
                'parent' => 'Institution.Academic',
                'link' => false
            ],

            'Institutions.FeederOutgoingInstitutions.index' => [
                'title' => 'Outgoing',
                'parent' => 'Institution.Feeders',
                'selected' => ['Institutions.FeederOutgoingInstitutions'],
            ],

            'Institutions.FeederIncomingInstitutions.index' => [
                'title' => 'Incoming',
                'parent' => 'Institution.Feeders',
                'selected' => ['Institutions.FeederIncomingInstitutions'],
            ],

            'Institutions.Students.index' => [
                'title' => 'Students',
                'parent' => 'Institutions.Institutions.index',
                'selected' => [
                    'Institutions.Students.add',
                    'Institutions.Students.addExisting',
                    'Institutions.Promotion.add',
                    'Institutions.Transfer',
                    'Institutions.Undo',
                    'Institutions.StudentAdmission',
                    'Institutions.StudentEnrolment', //POCOR-8434
                    'Institutions.StudentTransferIn',
                    'Institutions.StudentTransferOut',
                    'Institutions.StudentWithdraw',
                    'Institutions.WithdrawRequests',
                    'Institutions.StudentUser.add',
                    'Institutions.ImportStudentAdmission',
                    'Institutions.Students',
                    'Institutions.StudentHistories.index', //POCOR-8333
                    'Institutions.BulkStudentAdmission',
                    'Institutions.BulkStudentEnrolment', //POCOR-8434
                    'Institutions.ImportStudentBodyMasses',
                    'Institutions.ImportStudentGuardians',
                    'Institutions.StudentStatusUpdates',
                    'Institutions.ImportStudentExtracurriculars',
                    'Institutions.BulkStudentTransferIn',
                    'Institutions.BulkStudentTransferOut'
                ], // POCOR-7555
            ],

            'Institutions.Staff.index' => [
                'title' => 'Staff',
                'parent' => 'Institutions.Institutions.index',
                'selected' => [
                    'Institutions.Staff.add',
                    'Institutions.StaffUser.add',
                    'Institutions.StaffUser.pull',
                    'Institutions.ImportStaff',
                    'Institutions.ImportStaffSalaries',
                    'Institutions.Staff',
                    'Institutions.StaffTransferIn',
                    'Institutions.StaffTransferOut',
                    'StaffHistories.index'
                ]
            ],

            'Institution.Attendance' => [
                'title' => 'Attendance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.StudentAttendances.index' => [
                'title' => 'Students',
                'parent' => 'Institution.Attendance',
                'selected' => [
                    'Institutions.StudentAttendances',
                    'Institutions.StudentAbsences',
                    'Institutions.ImportStudentAttendances',
                    'Institutions.StudentArchive',
                    'Institutions.InstitutionStudentAbsencesArchived'
                ],
            ],

            'Institutions.InstitutionStaffAttendances.index' => [
                'title' => 'Staff',
                'parent' => 'Institution.Attendance',
                'selected' => [
                    'Institutions.InstitutionStaffAttendances',
                    'Institutions.ImportStaffAttendances',
                    'Institutions.StaffAttendancesArchived'
                ],
            ],

            //POCOR-8667 start
            'Institutions.Scanned.index' => [
                'title' => 'Scanned',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Scanned'],
            ], //POCOR-8667 end

            'Institution.Behaviour' => [
                'title' => 'Behaviour',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.StudentBehaviours.index' => [
                'title' => 'Students',
                'parent' => 'Institution.Behaviour',
                'selected' => [
                    'Institutions.StudentBehaviours',
                    'Institutions.StudentBehaviourAttachments'
                ],
            ],

            'Institutions.StaffBehaviours.index' => [
                'title' => 'Staff',
                'parent' => 'Institution.Behaviour',
                'selected' => [
                    'Institutions.StaffBehaviours',
                    'Institutions.StaffBehaviourAttachments'
                ],
            ],

            'Institution.Performance' => [
                'title' => 'Performance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.StudentCompetencies.index' => [
                'title' => 'Competencies',
                'parent' => 'Institution.Performance',
                'selected' => [
                    'Institutions.StudentCompetencies',
                    'Institutions.InstitutionCompetencyResults',
                    'Institutions.StudentCompetencyComments',
                    'Institutions.ImportCompetencyResults.add',
                    'Institutions.ImportCompetencyResults.results'
                ],
            ],

            'Institutions.StudentOutcomes.index' => [
                'title' => 'Outcomes',
                'parent' => 'Institution.Performance',
                'selected' => [
                    'Institutions.StudentOutcomes',
                    'Institutions.ImportOutcomeResults.add',
                    'Institutions.ImportOutcomeResults.results'
                ],
            ],

            'Institutions.Assessments.index' => [
                'title' => 'Assessments',
                'parent' => 'Institution.Performance',
                'selected' => [
                    'Institutions.Assessments',
                    'Institutions.Results',
                    'Institutions.AssessmentArchives',
                    'Institutions.ImportAssessmentItemResults.add',
                    'Institutions.ImportAssessmentItemResults.results',
                    'Institutions.AssessmentItemResultsArchived',
                    'Institutions.AssessmentItemExemptions', //POCOR-8224
                    'Institutions.reportCardGenerate'
                ],
            ],

            'Institutions.ReportCardStatuses.index' => [
                'title' => 'Report Cards',
                'parent' => 'Institution.Performance',
                'selected' => [
                    'Institutions.ReportCardStatuses',
                    'Institutions.ReportCardStatusProgress'
                ],
            ],
            'Institutions.ReportCardGpa.index' => [
                'title' => 'GPA',
                'parent' => 'Institution.Performance',
                'selected' => [
                    'Institutions.ReportCardGpa',
                    'Institutions.ReportCardCumulativeGpa'
                ],
            ], //POCOR-8222
            //POCOR-7458 start
            'Institutions.Messaging.index' => [
                'title' => 'Messaging',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Messaging', 'Institutions.MessageRecipients'],
            ],
            //POCOR-7458 end
            'Institutions.Risks.index' => [
                'title' => 'Risks',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.Risks', 'Institutions.InstitutionStudentRisks'],
            ],

            'Institutions.Examinations' => [
                'title' => 'Examinations',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.Exams.index' => [
                'title' => 'Exams',
                'parent' => 'Institutions.Examinations',
            ],

            'Institutions.ExaminationStudents.index' => [
                'title' => 'Students',
                'parent' => 'Institutions.Examinations',
                'selected' => ['Institutions.ExaminationStudents'],
            ],

            'Institutions.ExaminationResults.index' => [
                'title' => 'Results',
                'parent' => 'Institutions.Examinations',
                'selected' => ['Institutions.ExaminationResults'],
            ],

            'Institutions.ReportCards' => [
                'title' => 'Report Cards',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.ReportCardComments.index' => [
                'title' => 'Comments',
                'parent' => 'Institutions.ReportCards',
                'selected' => ['Institutions.ReportCardComments', 'Institutions.Comments'],
            ],

            'Institutions.Appointment' => [
                'title' => 'Appointments',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.Positions.index' => [
                'title' => 'Positions',
                'parent' => 'Institutions.Appointment',
                'selected' => [
                    'Institutions.Positions',
                    'Institutions.ImportInstitutionPositions'
                ],
            ],
            'Institutions.StaffDuties.index' => [
                'title' => 'Duties',
                'parent' => 'Institutions.Appointment',
                'selected' => ['Institutions.StaffDuties'],
            ],

            'Institution.Finance' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],
            //POCOR-6160 start
            'Institutions.BankAccounts.index' => [
                'title' => 'Bank Accounts',
                'parent' => 'Institution.Finance',
                'selected' => ['Institutions.BankAccounts'],
            ],
            //POCOR-6160 end
            'Institutions.Budget.index' => [
                'title' => 'Budget',
                'parent' => 'Institution.Finance',
                'selected' => ['Institutions.Budget'],
            ],

            'Institutions.Income.index' => [
                'title' => 'Income',
                'parent' => 'Institution.Finance',
                'selected' => ['Institutions.Income'],
            ],

            'Institutions.Expenditure.index' => [
                'title' => 'Expenditure',
                'parent' => 'Institution.Finance',
                'selected' => ['Institutions.Expenditure'],
            ],

            'Institutions.Fees.index' => [
                'title' => 'Institution Fees',
                'parent' => 'Institution.Finance',
                'selected' => ['Institutions.Fees'],
            ],

            'Institutions.StudentFees.index' => [
                'title' => 'Student Fees',
                'parent' => 'Institution.Finance',
                'selected' => ['Institutions.StudentFees'],
            ],

            // POCOR-8873 start
            'Institutions.Consumable.index' => [
                'title' => 'Consumables', //
                'parent' => 'Institution.Finance',
                'selected' => ['Institutions.Consumable', 'Institutions.Transactions'],
            ],
            // POCOR-8873 end

            'Infrastructures' => [
                'title' => 'Infrastructures',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.InstitutionLands.index' => [
                'title' => 'Overview',
                'parent' => 'Infrastructures',
                'selected' => [
                    'Institutions.InstitutionLands',
                    'Institutions.InstitutionBuildings',
                    'Institutions.InstitutionFloors',
                    'Institutions.InstitutionRooms'
                ]
            ],

            // POCOR-6150 start
            'Institutions.InfrastructureNeeds.index' => [
                'title' => 'Needs',
                'parent' => 'Infrastructures',
                'selected' => [
                    'InfrastructureNeeds',
                    'Institutions.InfrastructureNeeds.view',
                    'Institutions.InfrastructureNeeds.add',
                    'Institutions.InfrastructureNeeds.edit',
                    'Institutions.InfrastructureNeeds.delete'
                ]
            ],
            // POCOR-6150 end

            // POCOR-6151
            'Institutions.InfrastructureProjects.index' => [
                'title' => 'Projects',
                'parent' => 'Infrastructures',
                'selected' => [
                    'InfrastructureProjects',
                    'Institutions.InfrastructureProjects.view',
                    'Institutions.InfrastructureProjects.add',
                    'Institutions.InfrastructureProjects.edit',
                    'Institutions.InfrastructureProjects.delete'
                ]
            ],
            // POCOR-6151


            'Institutions.Infrastructures.Wash' => [
                'title' => 'WASH',
                'parent' => 'Infrastructures',
                'link' => false
            ],
            'Institutions.InfrastructureWashWaters.index' => [
                'title' => 'Water',
                'parent' => 'Institutions.Infrastructures.Wash',
                'selected' => [
                    'Institutions.InfrastructureWashWaters.view',
                    'Institutions.InfrastructureWashWaters.add',
                    'Institutions.InfrastructureWashWaters.edit',
                    'Institutions.InfrastructureWashWaters.delete'
                ]
            ],

            'Institutions.InfrastructureWashSanitations.index' => [
                'title' => 'Sanitation',
                'parent' => 'Institutions.Infrastructures.Wash',
                'selected' => [
                    'Institutions.InfrastructureWashSanitations.view',
                    'Institutions.InfrastructureWashSanitations.add',
                    'Institutions.InfrastructureWashSanitations.edit',
                    'Institutions.InfrastructureWashSanitations.delete'
                ]
            ],


            'Institutions.InfrastructureWashHygienes.index' => [
                'title' => 'Hygiene',
                'parent' => 'Institutions.Infrastructures.Wash',
                'selected' => [
                    'Institutions.InfrastructureWashHygienes.view',
                    'Institutions.InfrastructureWashHygienes.add',
                    'Institutions.InfrastructureWashHygienes.edit',
                    'Institutions.InfrastructureWashHygienes.delete'
                ]
            ],

            'Institutions.InfrastructureWashWastes.index' => [
                'title' => 'Waste',
                'parent' => 'Institutions.Infrastructures.Wash',
                'selected' => [
                    'Institutions.InfrastructureWashWastes.view',
                    'Institutions.InfrastructureWashWastes.add',
                    'Institutions.InfrastructureWashWastes.edit',
                    'Institutions.InfrastructureWashWastes.delete'
                ]
            ],

            'Institutions.InfrastructureWashSewages.index' => [
                'title' => 'Sewage',
                'parent' => 'Institutions.Infrastructures.Wash',
                'selected' => [
                    'Institutions.InfrastructureWashSewages.view',
                    'Institutions.InfrastructureWashSewages.add',
                    'Institutions.InfrastructureWashSewages.edit',
                    'Institutions.InfrastructureWashSewages.delete'
                ]
            ],

            'Institutions.Utilities' => [
                'title' => 'Utilities',
                'parent' => 'Infrastructures',
                'link' => false
            ],
            'Institutions.InfrastructureUtilityElectricities.index' => [
                'title' => 'Electricity',
                'parent' => 'Institutions.Utilities',
                'selected' => [
                    'Institutions.InfrastructureUtilityElectricities.view',
                    'Institutions.InfrastructureUtilityElectricities.add',
                    'Institutions.InfrastructureUtilityElectricities.edit',
                    'Institutions.InfrastructureUtilityElectricities.delete'
                ]
            ],


            'Institutions.InfrastructureUtilityInternets.index' => [
                'title' => 'Internet',
                'parent' => 'Institutions.Utilities',
                'selected' => [
                    'Institutions.InfrastructureUtilityInternets.view',
                    'Institutions.InfrastructureUtilityInternets.add',
                    'Institutions.InfrastructureUtilityInternets.edit',
                    'Institutions.InfrastructureUtilityInternets.delete'
                ]
            ],

            'Institutions.InfrastructureUtilityTelephones.index' => [
                'title' => 'Telephone',
                'parent' => 'Institutions.Utilities',
                'selected' => [
                    'Institutions.InfrastructureUtilityTelephones.view',
                    'Institutions.InfrastructureUtilityTelephones.add',
                    'Institutions.InfrastructureUtilityTelephones.edit',
                    'Institutions.InfrastructureUtilityTelephones.delete'
                ]
            ],
            // POCOR-6152
            'Institutions.InstitutionAssets.index' => [
                'title' => 'Assets',
                'parent' => 'Infrastructures',
                'selected' => [
                    'Institutions.InstitutionAssets',
                    'Institutions.InstitutionAssets.view',
                    'Institutions.ImportInstitutionAssets.add',
                    'Institutions.ImportInstitutionAssets.results',
                    'Institutions.InstitutionAssets.add',
                    'Institutions.InstitutionAssets.edit',
                    'Institutions.InstitutionAssets.delete'
                ],
            ],
            // POCOR-6152

            'Institutions.Meals' => [
                'title' => 'Meals',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],

            'Institutions.Distributions.index' => [
                'title' => 'Distributions',
                'parent' => 'Institutions.Meals',
                'selected' => ['Institutions.Distributions']
            ],

            'Institutions.StudentMeals.index' => [
                'title' => 'Students',
                'parent' => 'Institutions.Meals',
                'selected' => ['Institutions.StudentMeals', 'Institutions.ImportStudentMeals'],
            ],

            'Institutions.Survey' => [
                'title' => $label,//POCOR-9033
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],
            'Institutions.Surveys.index' => [
                'title' => 'Forms',
                'parent' => 'Institutions.Survey',
                'selected' => [
                    'Institutions.Surveys',
                    'Institutions.ImportInstitutionSurveys'
                ],
            ],

            'Institutions.Rubrics.index' => [
                'title' => 'Rubrics',
                'parent' => 'Institutions.Survey',
                'selected' => [
                    'Institutions.Rubrics',
                    'Institutions.RubricAnswers'
                ],
            ],
            // POCOR-9059[START]
            'Institutions.VisitRequests.index' => [
                'title' => 'Visits',
                'parent' => 'Institutions.Institutions.index',
                'selected' => ['Institutions.VisitRequests',
                    'Institutions.Visits']
            ],
            // POCOR-9059[END]
            'Institutions.Transport' => [
                'title' => 'Transport',
                'parent' => 'Institutions.Institutions.index',
                'link' => false,
            ],

            'Institutions.InstitutionTransportProviders.index' => [
                'title' => 'Providers',
                'parent' => 'Institutions.Transport',
                'selected' => [
                    'Institutions.InstitutionTransportProviders.add',
                    'Institutions.InstitutionTransportProviders.edit',
                    'Institutions.InstitutionTransportProviders.view',
                    'Institutions.InstitutionTransportProviders.delete'
                ]
            ],

            'Institutions.InstitutionBuses.index' => [
                'title' => 'Buses',
                'parent' => 'Institutions.Transport',
                'selected' => [
                    'Institutions.InstitutionBuses',
                    'Institutions.InstitutionBuses.add',
                    'Institutions.InstitutionBuses.edit',
                    'Institutions.InstitutionBuses.view',
                    'Institutions.InstitutionBuses.delete'
                ]
            ],

            // POCOR-6169
            'Institutions.InstitutionTrips.index' => [
                'title' => 'Trips',
                'parent' => 'Institutions.Transport',
                'selected' => [
                    'Institutions.InstitutionTrips',
                    'Institutions.InstitutionTrips.add',
                    'Institutions.InstitutionTrips.edit',
                    'Institutions.InstitutionTrips.view',
                    'Institutions.InstitutionTrips.delete'
                ]
            ],
            // POCOR-6169

            'Institutions.Cases.index' => [
                'title' => 'Cases',
                'parent' => 'Institutions.Institutions.index',
            ],
            'Institutions.Committees.index' => [
                'title' => 'Committees',
                'parent' => 'Institutions.Institutions.index',
                //'selected' => ['Institutions.Committees','InstitutionCommitteeAttachments.add', 'InstitutionCommitteeAttachments.edit', 'InstitutionCommitteeAttachments.view', 'InstitutionCommitteeAttachments.index','InstitutionCommitteeAttachments.delete'],
                'selected' => ['Institutions.Committees', 'Institutions.CommitteeAttachments'],
            ],
            'Institutions.Statistics' => [
                'title' => 'Statistics',
                'parent' => 'Institutions.Institutions.index',
                'link' => false
            ],
            'Institutions.InstitutionStandards.index' => [
                'title' => 'Standard',
                'parent' => 'Institutions.Statistics',
                'selected' => ['Institutions.InstitutionStandards', 'Institutions.ViewReport']
            ],
            'Institutions.InstitutionStatistics.index' => [
                'title' => 'Custom',
                'parent' => 'Institutions.Statistics',
                'selected' => [
                    'Institutions.InstitutionStatistics',
                    'Institutions.InstitutionStatistics.view',
                    'Institutions.InstitutionStatistics.edit',
                    'Institutions.InstitutionStatistics.remove',
                    'Institutions.InstitutionStatistics.download',
                    'Institutions.InstitutionStatistics.excel'
                ]
            ],
        ];

        $institutionID = $this->controller->getQueryString('institution_id');
        if (empty($institutionID) && $this->getController()->getRequest()->getParam('action') == 'ViewReport') {
            $institutionID = $this->getController()->getRequest()->getQuery('institution_id');
        } //POCOR-8485
        $encodedInstitutionID = $this->controller->paramsEncode([
            'id' => $institutionID,
            'institution_id' => $institutionID,
        ]);
        $paramsForInstitution = [
            'plugin' => 'Institution',
            0 => $encodedInstitutionID
        ];
        foreach ($navigation as &$n) {
            if (!isset($n['link']) || $n['link'] != false) {
                $n['params'] = $paramsForInstitution;
            }
        }
        return $navigation;
    }

    public function getInstitutionStudentNavigation()
    {
        $debugString = __FILE__ . ':' . __FUNCTION__ . ':' . __LINE__;
        $studentID = $this->getStudentID($debugString);
        $institutionID = $this->getInstitutionIDForStudent($debugString);
        $institutionStudentId = $this->controller->getQueryString('institution_student_id');
        //POCOR-8551
        if (empty($institutionStudentId)) {
            $InstitutionStudentsTable = TableRegistry::get('Institution.Students');
            $query = $InstitutionStudentsTable->find()
                ->select(['id']) // Specify the field you want to extract
                ->where([
                    $InstitutionStudentsTable->aliasField('student_id') => $studentID,
                    $InstitutionStudentsTable->aliasField('institution_id') => $institutionID,
                ])
                ->order([$InstitutionStudentsTable->aliasField('created') => 'DESC']);

            $results = $query->all()->extract('id')->toArray();
            $institutionStudentId = !empty($results) ? $results[0] : null;
        }


        $queryString = $this->controller->paramsEncode([
            'id' => $studentID,
            'institution_id' => $institutionID,
            'student_id' => $studentID,
            'institution_student_id' => $institutionStudentId,
            'user_id' => $studentID
        ]);
        //echo "<pre>"; print_r($queryString);die;
        $navigation = [
            // POCOR-8344 start
            'Institution.Institutions.StudentDashboard.view' => [
                'title' => 'Dashboard',
                'parent' => 'Institutions.Students.index',
                'selected' => [
                    'Institutions.StudentDashboard',
                    'Institutions.StudentDashboard.view'
                ],
            ],
            // POCOR-8344 end
            'Institution.Institutions.StudentUser.view' => [
                'title' => 'General',
                'parent' => 'Institutions.Students.index',
                'selected' => [
                    'Institutions.StudentUser',
                    'Institutions.StudentAccount',
                    'Institutions.StudentSurveys',
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
                    'StudentComments',
                    'Students.StudentTransport',
                    'Students.Demographic',
                    'Guardians.Accounts',
                    'Guardians.Demographic',
                    'Guardians.Identities',
                    'Guardians.Nationalities',
                    'Guardians.Contacts',
                    'Guardians.Languages',
                    'Guardians.Attachments',
                    'GuardianComment',
                    'Institutions.Addguardian.index',
                ]
            ],
            'Institution.Institutions.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Institutions.Students.index',
                'selected' => [
                    'Students.Classes.index',
                    'Students.Subjects',
                    'Students.Absences.index',
                    'Students.Absences.view',
                    'Students.ArchivedAbsences',
                    'Students.Behaviours.index',
                    //POCOR-7474-HINDOL TYPO FIX
                    'Students.Assessments.index',
                    'Students.StudentGpa.index',
                    'Students.AssessmentsArchived.index',
                    'Students.ExaminationResults.index',
                    'Students.ReportCards.index',
                    'Students.Awards', //POCOR-5786 replace results to Assessments
                    'Students.Extracurriculars',
                    'Institutions.StudentTextbooks',
                    'Institutions.Students',
                    'Institutions.StudentRisks',
                    'Students.Outcomes',
                    'Institutions.StudentProgrammes',
                    'Students.Competencies.index',
                    'Students.AssessmentItemResultsArchived',
                    'Students.InstitutionStudentAbsencesArchived',
                    'Institutions.StudentTransition',
                    'Institutions.Associations',
                    'Institutions.StudentAssociations',
                    'Institutions.StudentCurriculars'
                ]
            ],
            'Student.Students.StudentScheduleTimetable.index' => [
                'title' => 'Timetables',
                'parent' => 'Institutions.Students.index',
                'selected' => ['Students.StudentScheduleTimetable'],
            ],
            'Student.Students.Employments.index' => [
                'title' => 'Professional',
                'parent' => 'Institutions.Students.index',
                'selected' => [
                    'Students.Employments',
                    'Students.Qualifications',
                    'Students.Licenses'
                ] //POCOR-7528
            ],
            'Student.Students.Counsellings.index' => [
                'title' => 'Counselling',
                'parent' => 'Institutions.Students.index',
                'selected' => [
                    'Students.Counsellings',
                    /*Counsellings.add,
                    'Counsellings.edit',
                    'Counsellings.view',
                    'Counsellings.delete'*/
                ]
            ],
            'Student.Students.BankAccounts.index' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Students.index',
                'selected' => ['Students.BankAccounts', 'Students.StudentFees']
            ],
            'Student.Students.Healths.index' => [
                'title' => 'Health',
                'parent' => 'Institutions.Students.index',
                'selected' => [
                    'Students.Healths',
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
                    'Students.StudentInsurances',
                    'Students.HealthBodyMasses',
                    'Students.HealthInsurances'
                ]
                // 'selected' => ['Students.Healths', 'Students.HealthAllergies', 'Students.HealthConsultations', 'Students.HealthFamilies', 'Students.HealthHistories', 'Students.HealthImmunizations', 'Students.HealthMedications', 'Students.HealthTests', 'StudentBodyMasses.index', 'StudentBodyMasses.add', 'StudentBodyMasses.edit', 'StudentBodyMasses.view', 'StudentBodyMasses.delete', 'StudentInsurances.add', 'StudentInsurances.view', 'StudentInsurances.edit', 'StudentInsurances.delete', 'StudentInsurances.index']
            ],
            'Student.Students.SpecialNeedsReferrals.index' => [
                'title' => 'Special Needs',
                'parent' => 'Institutions.Students.index',
                'selected' => [
                    'Students.SpecialNeedsReferrals',
                    'Students.SpecialNeedsAssessments',
                    'Students.SpecialNeedsServices',
                    'Students.SpecialNeedsDevices',
                    'Students.SpecialNeedsPlans',
                    'Students.SpecialNeedsDiagnostics'
                ]
            ],
            
            // POCOR-9059[START]
            // 'Student.Students.StudentVisitRequests.index' => [
            //     'title' => 'Visits',
            //     'parent' => 'Institutions.Students.index',
            //     'selected' => ['Students.StudentVisitRequests',
            //         'Students.StudentVisits.index']
            // ],
            // POCOR-9059[END]

            'Student.Students.Meals.index' => [
                'title' => 'Meals',
                'parent' => 'Institutions.Students.index',
                'selected' => ['Students.Meals']
            ],
            'Student.Students.Profiles.index' => [
                'title' => 'Profiles',
                'parent' => 'Institutions.Students.index',
                'selected' => ['Students.Profiles']
            ],
        ];
        foreach ($navigation as &$n) {
            //            if (isset($n['params'])) {
            $n['params']['1'] = $queryString;
            //            }
        }
        return $navigation;
    }

    // PHP 5.5 array_column alternative

    private
    function getStudentID($debug = "")
    {
        // POCOR-8115;
        // student_id should always be in query string, if not, die as an error
        $student_id = $this->controller->getQueryString('student_id');
        if ($debug != "") {
            if (!$student_id) {
                $session = $this->getController()->getRequest()->getSession();
                $isUserId = $session->read('Auth.User.id');
                if ($isUserId) {
                    $student_id = intval($isUserId);
                }
                if (!$student_id) {
                    die($debug . 'For Developer: You should put student_id into query string first');
                }
            }
        }
        return $student_id;
    }

    private
    function getInstitutionIDForStudent($debug = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->getInstitutionID();
        if (is_numeric($institution_id)) {
            return $institution_id;
        }
        $student_id = $this->getStudentID();

        if ($student_id) {
            $StudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
            $Student = $StudentsTable
                ->find('all')
                ->where([$StudentsTable->aliasField('student_id') => $student_id])
                ->first();
            if (!empty($Student)) {
                $institution_id = $Student->institution_id;
            }
        }
        if ($debug != "") {
            if (!$institution_id) {
                $institution_id = -1;
            }
        }
        return $institution_id;
    }

    /**
     * common function to get institution id
     * @return string|null
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private
    function getInstitutionID($debug = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->controller->getQueryString('institution_id');
        if ($debug != "") {
            if (!$institution_id) {
                die($debug . 'For Developer: You should put institution_id into query string first');
            }
        }
        return $institution_id;
    }

    public function getInstitutionStaffNavigation()
    {
        // todo
        $debugString = __FILE__ . ':' . __FUNCTION__ . ':' . __LINE__;
        $staffID = $this->getStaffID($debugString);
        $institutionID = $this->getInstitutionIDForStaff($debugString);
        $queryStringWithID = $this->controller->paramsEncode([
            'id' => $staffID,
            'institution_id' => $institutionID,
            'staff_id' => $staffID,
            'user_id' => $staffID
        ]);
        $queryStringWithoutID = $this->controller->paramsEncode([
            'institution_id' => $institutionID,
            'staff_id' => $staffID,
            'user_id' => $staffID
        ]);

        $navigation = [
            // POCOR-8344 start
            'Institution.Institutions.StaffDashboard.view' => [
                'title' => 'Dashboard',
                'parent' => 'Institutions.Staff.index',
                'selected' => [
                    'Institutions.StaffDashboard',
                    'Institutions.StaffDashboard.view'
                ],
            ],
            // POCOR-8344 end
            'Institution.Institutions.StaffUser.view' => [
                'title' => 'General',
                'parent' => 'Institutions.Staff.index',
                'selected' => [
                    'Institutions.StaffUser',
                    'Institutions.StaffAccount',
                    'Staff.Identities',
                    'Staff.Nationalities',
                    'Staff.Contacts',
                    'Staff.Guardians',
                    'Staff.Languages',
                    'Staff.Attachments',
                    'Staff.Comments',
                    'Staff.History',
                    'Staff.Demographic'
                ]
            ],
            'Staff.Staff.EmploymentStatuses.index' => [
                'title' => 'Career',
                'parent' => 'Institutions.Staff.index',
                'selected' => [
                    'Staff.EmploymentStatuses',
                    'Staff.Positions.index',
                    'Staff.HistoricalStaffPositions.index',
                    'Staff.Classes.index',
                    'Staff.Subjects',
                    'Staff.Absences',
                    'Staff.StaffAttendances',
                    'Staff.ArchivedAttendances',
                    'Staff.InstitutionStaffAttendanceActivities',
                    //'Institutions.StaffLeave',
                    'Staff.StaffLeave',
                    'Staff.StaffEntitlement', // POCOR-8128
                    'Institutions.ArchivedStaffLeave',
                    'Institutions.HistoricalStaffLeave',
                    'Staff.Behaviours',
                    'Institutions.Staff',
                    'Institutions.StaffPositionProfiles',
                    //'Institutions.StaffAppraisals', POCOR-7485 not use becuase now StaffAppraisals's controller change
                    'Staff.StaffAppraisals',
                    'Institutions.ImportStaffLeave',
                    'Staff.Duties',
                    'Staff.StaffAssociations',
                    'Staff.StaffCurriculars',
                ],
            ],
            'Staff.Staff.Employments.index' => [
                'title' => 'Professional',
                'parent' => 'Institutions.Staff.index',
                'selected' => [
                    'Staff.Employments',
                    'Staff.Qualifications',
                    'Staff.Extracurriculars',
                    'Staff.Memberships',
                    'Staff.Licenses',
                    'Staff.Awards'
                ],
            ],
            'Staff.Staff.BankAccounts.index' => [
                'title' => 'Finance',
                'parent' => 'Institutions.Staff.index',
                'selected' => [
                    'Staff.BankAccounts',
                    'Staff.Salaries',
                    'Staff.ImportSalaries',
                    'Staff.Payslips'
                ]
            ],
            'Institution.Institutions.StaffTrainingNeeds.index' => [
                'title' => 'Training',
                'parent' => 'Institutions.Staff.index',
                'selected' => [
                    'Institutions.StaffTrainingNeeds',
                    'Institutions.StaffTrainingApplications',
                    'Institutions.StaffTrainingResults',
                    'Institutions.CourseCatalogue',
                    'Staff.Courses'
                ],
            ],
            'Staff.Staff.ScheduleTimetable.index' => [
                'title' => 'Timetables',
                'parent' => 'Institutions.Staff.index',
                'selected' => ['Staff.ScheduleTimetable'],
                //'params' => $paramsForStaff
            ],
            /*POCOR-6311 Starts added StaffInsurances functions for Staff Health nav*/
            'Staff.Staff.Healths.index' => [
                'title' => 'Health',
                'parent' => 'Institutions.Staff.index',
                'selected' => [
                    'Staff.Healths',
                    'Staff.HealthAllergies',
                    'Staff.HealthConsultations',
                    'Staff.HealthFamilies',
                    'Staff.HealthHistories',
                    'Staff.HealthImmunizations',
                    'Staff.HealthMedications',
                    'Staff.HealthTests',
                    //'Staff.StaffBodyMasses', //old code
                    //'Staff.StaffInsurances', //old code
                    'Staff.HealthBodyMasses', //POCOR-8359
                    'Staff.HealthInsurances'
                ] //POCOR-8359
            ],
            'Staff.Staff.SpecialNeedsReferrals.index' => [
                'title' => 'Special Needs',
                'parent' => 'Institutions.Staff.index',
                //                'params' => $paramsForStaff,  // POCOR-8989 removed unused
                'selected' => [
                    'Staff.SpecialNeedsReferrals',
                    'Staff.SpecialNeedsAssessments',
                    'Staff.SpecialNeedsServices',
                    'Staff.SpecialNeedsDevices',
                    'Staff.SpecialNeedsPlans',
                    'Staff.SpecialNeedsDiagnostics'
                ]
            ],
            'Staff.Staff.Profiles.index' => [
                'title' => 'Profiles',
                'parent' => 'Institutions.Staff.index',
                'selected' => ['Staff.Profiles.index'],
                //                'params' => $paramsForStaff // POCOR-8989 removed unused
            ],
        ];
        foreach ($navigation as &$n) {
            if ($n['title'] == 'General') {
                $n['params']['1'] = $queryStringWithID;
            } else {
                $n['params']['1'] = $queryStringWithoutID;
            }
        }
        return $navigation;
    }

    private
    function getStaffID($debug = "")
    {
        // POCOR-8115;
        // staff_id should always be in query string, if not, die as an error
        $staff_id = $this->controller->getQueryString('staff_id');
        if ($debug != "") {
            if (!$staff_id) {
                $session = $this->getController()->getRequest()->getSession();
                $isUserId = $session->read('Auth.User.id');
                if ($isUserId) {
                    $staff_id = intval($isUserId);
                }
                if (!$staff_id) {
                    die($debug . 'For Developer: You should put student_id into query string first');
                }
            }
        }
        return $staff_id;
    }

    private
    function getInstitutionIDForStaff($debug = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->getInstitutionID();
        if (is_numeric($institution_id)) {
            return $institution_id;
        }
        $staff_id = $this->getStaffID();
        if ($staff_id) {
            $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
            $Staff = $StaffTable
                ->find('all')
                ->where([$StaffTable->aliasField('staff_id') => $staff_id])
                ->first();
            if (!empty($Staff)) {
                $institution_id = $Staff->institution_id;
            }
        }
        if ($debug != "") {
            if (!$institution_id) {
                $institution_id = -1;
            }
        }
        return $institution_id;
    }

    public function checkClassification(array &$navigations)
    {
        $session = $this->getController()->getRequest()->getSession();
        $institutionId = $this->getInstitutionID(); // POCOR-9081
//        $institutionId = $session->read('Institution.Institutions.id');

        if (!empty($institutionId)) {
            //$Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');

            if ($Institutions->exists([$Institutions->getPrimaryKey() => $institutionId])) {
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
                        // 'Institutions.VisitRequests', //POCOR-9059
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

    public function getDirectoryNavigation()
    {
        //POCOR-5886 starts
        $session = $this->getController()->getRequest()->getSession();
        if (!empty($session->read('Directory.Directories.id'))) {
            $id = $session->read('Directory.Directories.id');
        } else {
            $id = $session->read('Directory.Directories.primaryKey.id');
        }
        $directorUserId = $this->controller->paramsEncode(['id' => $id]);
        $directorStaffId = $this->controller->paramsEncode(['staff_id' => $id, 'security_user_id' => $id]);
        //POCOR-5886 ends
        $navigation = [
            'Directories.Directories.view' => [
                'title' => 'General',
                'parent' => 'Directories.Directories.index',
                //POCOR-5886 starts
                'params' => [
                    'plugin' => 'Directory',
                    'action' => 'Directories',
                    0 => $directorUserId
                ], //POCOR-5886 ends
                'selected' => [
                    'Directories.Directories.view',
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
                    'Directories.Comments',
                    'Directories.Attachments',
                    'Directories.History',
                    'Directories.Contacts',
                    'Directories.Demographic'
                ]
            ],
            'Directories.Healths.index' => [
                'title' => 'Health',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory', 0 => $directorStaffId],
                'selected' => [
                    'Directories.Healths',
                    'Directories.HealthAllergies',
                    'Directories.HealthConsultations',
                    'Directories.HealthFamilies.index',
                    'Directories.HealthFamilies.add',
                    'Directories.HealthFamilies.view',
                    'Directories.HealthFamilies.edit',
                    'Directories.HealthHistories.index',
                    'Directories.HealthHistories.view',
                    'Directories.HealthHistories.add',
                    'Directories.HealthHistories.edit',
                    'Directories.HealthImmunizations.index',
                    'Directories.HealthImmunizations.add',
                    'Directories.HealthImmunizations.edit',
                    'Directories.HealthImmunizations.view',
                    'Directories.HealthMedications.index',
                    'Directories.HealthMedications.add',
                    'Directories.HealthMedications.edit',
                    'Directories.HealthMedications.view',
                    'Directories.HealthTests.index',
                    'Directories.HealthTests.add',
                    'Directories.HealthTests.edit',
                    'Directories.HealthTests.view',
                    'Directories.HealthBodyMasses',
                    'Directories.HealthInsurances',
                    'DirectoryInsurances.view'
                ]
            ],
            'Directories.Employments.index' => [
                'title' => 'Professional',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory', 0 => $directorStaffId],
                'selected' => [
                    'Directories.Employments',
                    'Directories.StaffQualifications',
                    'Directories.StaffExtracurriculars',
                    'Directories.StaffMemberships',
                    'Directories.StaffLicenses',
                    'Directories.StudentLicenses', //POCOR-7528
                    'Directories.StaffAwards'
                ]
            ],

            'Directories.SpecialNeedsReferrals.index' => [
                'title' => 'Special Needs',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory', 0 => $directorStaffId],
                'selected' => [
                    'Directories.SpecialNeedsReferrals',
                    'Directories.SpecialNeedsAssessments',
                    'Directories.SpecialNeedsServices',
                    'Directories.SpecialNeedsDevices',
                    'Directories.SpecialNeedsPlans',
                    'Directories.SpecialNeedsDiagnostics'
                ]
            ]
        ];
        //POCOR-7366 start

        if ($session->read('Directory.Directories.is_student') == 1) {
            $newNavigation = ['Directories.Counsellings.index' => [
                'title' => 'Counsellings',
                'parent' => 'Directories.Directories.index',
                'params' => ['plugin' => 'Directory', 0 => $directorStaffId],
                'selected' => ['Directories.Counsellings']
            ]];
            $i = array_search('Directories.Employments', array_keys($navigation));
            $navigation = array_merge(array_slice($navigation, 0, $i + 1), $newNavigation, array_slice($navigation, $i + 1));
        }
        //POCOR-7366 end
        $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
        $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');
        if (!empty($studentToGuardian) || !empty($guardianToStudent)) {
            $navigation['Directories.Directories.view']['selected'] = [
                'Directories.Directories.view',
                'Directories.Directories.edit',
                'Directories.Directories.pull',
                'Directories.History'
            ];
        }
        //echo "<pre>"; print_r($navigation);die;
        return $navigation;
    }

    public function getDirectoryStaffNavigation()
    {
        $session = $this->getController()->getRequest()->getSession();
        $id = $session->read('Guardian.Guardians.id');
        if (!empty($session->read('Directory.Directories.id'))) {
            $id = $session->read('Directory.Directories.id');
        } else {
            $id = $session->read('Directory.Directories.primaryKey.id');
        }

        if (!empty($id)) {
            $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
            $Staff = $StaffTable
                ->find('all')
                ->where([$StaffTable->aliasField('staff_id') => $id])
                ->first();
            if (!empty($Staff)) {
                $institutionID = $Staff->institution_id;
            }
        }

        $queryStringWithID = $this->controller->paramsEncode([
            'institution_id' => $institutionID,
            'staff_id' => $id,
            'user_id' => $id
        ]);
        $navigation = [
            'Directories.Staff' => [
                'title' => 'Staff',
                'parent' => 'Directories.Directories.index',
                'link' => false,
            ],
            'Directories.StaffEmploymentStatuses.index' => [
                'title' => 'Career',
                'parent' => 'Directories.Staff',
                'params' => ['plugin' => 'Directory'],
                'selected' => [
                    'Directories.StaffEmploymentStatuses',
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
                    'Directories.StaffAssociations'
                ]
            ],
            'Directories.StaffBankAccounts.index' => [
                'title' => 'Finance',
                'parent' => 'Directories.Staff',
                'params' => [
                    'plugin' => 'Directory',
                    'type' => 'staff'
                ],
                'selected' => [
                    'Directories.StaffBankAccounts',
                    'Directories.StaffSalaries',
                    'Directories.ImportSalaries',
                    'Directories.StaffPayslips',
                    'Directories.StaffPayslips',
                ]
            ],
            'Directories.TrainingNeeds.index' => [
                'title' => 'Training',
                'parent' => 'Directories.Staff',
                'params' => ['plugin' => 'Directory'],
                'selected' => [
                    'Directories.TrainingNeeds',
                    'Directories.TrainingResults',
                    'Directories.Courses'
                ]
            ],/*POCOR-6286 - added profiles menu*/
            'Directories.StaffProfiles.index' => [
                'title' => 'Profiles',
                'parent' => 'Directories.Staff',
                'params' => ['plugin' => 'Directory'],
                'selected' => ['Directories.StaffProfiles']
            ]
        ];
        foreach ($navigation as &$n) {
            if (isset($n['params'])) {
                $n['params']['1'] = $queryStringWithID;
            }
        }

        return $navigation;
    }

    public function getDirectoryStudentNavigation()
    {
        $session = $this->getController()->getRequest()->getSession();
        //$id = $session->read('Guardian.Guardians.id');

        $pass = $this->controller->getQueryString();
        $id = isset($pass['security_user_id']) ? $pass['security_user_id'] : (isset($pass['student_id']) ? $pass['student_id'] : (isset($pass['id']) ? $pass['id'] : ''));
        if ($id) {
            $StudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
            $Student = $StudentsTable
                ->find('all')
                ->where([$StudentsTable->aliasField('student_id') => $id])
                ->first();
            if (!empty($Student)) {
                $institution_id = $Student->institution_id;
            }
        }
        $directorUserId = $this->controller->paramsEncode(['id' => $id, 'security_user_id' => $id]);
        $directorStudentId = $this->controller->paramsEncode(['student_id' => $id, 'institution_id' => $institution_id, 'security_user_id' => $id]);
        $navigation = [
            'Directories.Student' => [
                'title' => 'Student',
                'parent' => 'Directories.Directories.index',
                'link' => false,
            ],
            'Directories.StudentGuardians' => [
                'title' => 'Guardians',
                'parent' => 'Directories.Student',
                'params' => ['plugin' => 'Directory', 'queryString' => $directorUserId],
                'selected' => [
                    'Directories.StudentGuardians',
                    'Directories.StudentGuardianUser',
                    'Directories.Addguardian'
                ]
            ], //POCOR-7093 Addguardian condition
            'Directories.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Directories.Student',
                'params' => ['plugin' => 'Directory', 'queryString' => $directorStudentId],
                'selected' => [
                    'Directories.StudentProgrammes.index',
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
                    'Directories.StudentRisks',
                    'Directories.StudentAssociations',
                    'Directories.Absences'
                ]
            ],
            'Directories.StudentBankAccounts.index' => [
                'title' => 'Finance',
                'parent' => 'Directories.Student',
                'params' => [
                    'plugin' => 'Directory',
                    $directorStudentId,
                    'type' => 'student'
                ],
                'selected' => [
                    'Directories.StudentBankAccounts',
                    'Directories.StudentFees'
                ]
            ],/*POCOR-6286 - added profiles menu*/
            'Directories.StudentProfiles' => [
                'title' => 'Profiles',
                'parent' => 'Directories.Student',
                'params' => ['plugin' => 'Directory', 'queryString' => $directorStudentId],
                'selected' => ['Directories.StudentProfile']
            ],

        ];

        $session = $this->getController()->getRequest()->getSession();
        $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
        if (!empty($studentToGuardian)) {
            $navigation['Directories.StudentGuardians']['selected'] = [
                'Directories.StudentGuardians',
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
                'Directories.Comments',
                'Directories.Attachments',
                'Directories.Contacts',
                'Directories.Demographic'
            ];
        }

        return $navigation;
    }

    public function getDirectoryGuardianNavigation()
    {
        $pass = $this->controller->getQueryString();
        $id = isset($pass['security_user_id']) ? $pass['security_user_id'] : (isset($pass['student_id']) ? $pass['student_id'] : (isset($pass['id']) ? $pass['id'] : ''));
        $directorUserId = $this->controller->paramsEncode(['id' => $id, 'security_user_id' => $id]);
        $navigation = [
            'Directories.Guardian' => [
                'title' => 'Guardian',
                'parent' => 'Directories.Directories.index',
                'link' => false,
            ],
            'Directories.GuardianStudents' => [
                'title' => 'Students',
                'parent' => 'Directories.Guardian',
                'params' => ['plugin' => 'Directory', 'queryString' => $directorUserId],
                'selected' => ['Directories.GuardianStudents']
            ],
        ];
        $session = $this->getController()->getRequest()->getSession();
        $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');
        if (!empty($guardianToStudent)) {
            $navigation['Directories.GuardianStudents']['selected'] = [
                'Directories.GuardianStudents',
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
                'Directories.Comments',
                'Directories.Attachments',
                'Directories.Contacts',
                'Directories.Demographic'
            ];
        }

        return $navigation;
    }

    public function getProfileNavigation()
    {
        //POCOR-5886 starts
        $session = $this->getController()->getRequest()->getSession();
        $userID = $session->read('Auth.User.id');
        $params = [
            'id' => $userID,
            'user_id' => $userID
        ];
        $profileUserId = $this->controller->paramsEncode($params);
        // echo "<pre>";print_r($profileUserId);die;

        //POCOR-5886 ends
        $navigation = [
            // POCOR-8344 start

            'Profiles.PersonalDashboard.view' => [
                'title' => 'Dashboard',
                'parent' => 'Profiles.Personal',
                'params' => [
                    'plugin' =>
                    'Profile',
                    'action' => 'PersonalDashboard',
                    'selected' => ['Profiles.PersonalDashboard.view']
                ],
            ],
            // POCOR-8344 end
            'Profiles.Profiles.view' => [
                'title' => 'General',
                'parent' => 'Profiles.Personal',
                //POCOR-5886 starts
                'params' => [
                    'plugin' => 'Profile',
                    'action' => 'Personal'
                ], //POCOR-5886 ends
                'selected' => [
                    'Profiles.Personal.view',
                    'Profiles.Personal.edit',
                    'Profiles.Personal.pull',
                    'Profiles.Accounts',
                    'Profiles.Demographic',
                    'Profiles.Identities',
                    'Profiles.Nationalities',
                    'Profiles.Languages',
                    'Profiles.Comments',
                    'Profiles.Attachments',
                    'Profiles.UserActivities',
                    'Profiles.Contacts',
                    'Profiles.History'
                ] // POCOR-6683
            ],
            'Profiles.Healths.index' => [
                'title' => 'Health',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.Healths',
                    'Profiles.HealthAllergies',
                    'Profiles.HealthConsultations',
                    'Profiles.HealthFamilies',
                    'Profiles.HealthHistories',
                    'Profiles.HealthImmunizations',
                    'Profiles.HealthMedications',
                    'Profiles.HealthTests',
                    'Profiles.HealthBodyMasses',
                    'Profiles.HealthInsurances',
                ]
            ],
            'Profiles.Employments.index' => [
                'title' => 'Professional',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.Employments',
                    'Profiles.StaffQualifications',
                    'Profiles.StaffExtracurriculars',
                    'Profiles.StaffMemberships',
                    'Profiles.StaffLicenses',
                    'Profiles.StaffAwards'
                ]
            ],
            //POCOR-7439 start
            'Profiles.Cases.index' => [
                'title' => 'Cases',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => ['Profiles.Cases']
            ],
            //POCOR-7439 end
            'Profiles.SpecialNeedsReferrals.index' => [
                'title' => 'Special Needs',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.SpecialNeedsReferrals',
                    'Profiles.SpecialNeedsAssessments',
                    'Profiles.SpecialNeedsServices',
                    'Profiles.SpecialNeedsDevices',
                    'Profiles.SpecialNeedsPlans',
                    'Profiles.SpecialNeedsDiagnostics'
                ]
            ],

            // 'ScholarshipApplications.index' => [
            //     'title' => 'ScholarshipApplications',
            //     'parent' => 'Profiles.Personal',
            //     'selected' => ['ScholarshipApplications.ScholarshipApplications']
            // ],
            'Profiles.ScholarshipApplications.index' => [
                'title' => 'Scholarships',
                'parent' => 'Profiles.Personal',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.ScholarshipApplications',
                    'Profiles.ScholarshipsDirectory',
                    'Profiles.ScholarshipApplicationInstitutionChoices',
                    'Profiles.ScholarshipApplicationAttachments'
                ]
            ],

            // 'Scholarships.Scholarships' => [
            //     'title' => 'Scholarships',
            //     'parent' => 'Profiles.Personal',
            //     'selected' => ['Scholarships.Scholarships']
            // ],
        ];
        foreach ($navigation as &$n) {
            if (isset($n['params'])) {
                $n['params'][] = $profileUserId;
            }
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
                'selected' => [
                    'Profiles.StaffEmploymentStatuses',
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
                    'Profiles.StaffCurriculars'
                ]
            ],
            'Profiles.StaffBankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Profiles.Staff',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.StaffBankAccounts',
                    'Profiles.StaffSalaries',
                    'Profiles.ImportSalaries',
                    'Profiles.StaffPayslips'
                ]
            ],
            'Profiles.TrainingNeeds' => [
                'title' => 'Training',
                'parent' => 'Profiles.Staff',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.TrainingNeeds',
                    'Profiles.TrainingResults',
                    'Profiles.Courses',
                    'Profiles.StaffTrainingApplications'
                ]
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
            'Profiles.ProfileGuardians.index' => [
                'title' => 'Guardians',
                'parent' => 'Profiles.Student',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.ProfileGuardians',
                    'Profiles.ProfileGuardianUser'
                ]
            ],
            'Profiles.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Profiles.Student',
                'params' => ['plugin' => 'Profile'],
                'selected' => [
                    'Profiles.StudentProgrammes.index',
                    'Profiles.StudentSubjects',
                    'Profiles.StudentClasses',
                    'Profiles.StudentAbsences',
                    'Profiles.StudentBehaviours',
                    'Profiles.StudentCompetencies',
                    'Profiles.StudentResults',
                    'Profiles.StudentAssessments',
                    'Profiles.StudentExaminationResults',
                    'Profiles.StudentReportCards',
                    'Profiles.StudentAwards',
                    'Profiles.StudentExtracurriculars',
                    'Profiles.StudentTextbooks',
                    'Profiles.StudentOutcomes',
                    'Profiles.StudentRisks',
                    'Profiles.StudentAssociations',
                    'Profiles.Absences',
                    'Profiles.StudentCurriculars',
                    'Profiles.StudentGpa'
                ]
            ], //POCOR-6701 added Profiles.Absences becasue navigation was collapsing //POCOR-6699 adding studentAssessment
            'Profiles.StudentScheduleTimetable' => [
                'title' => 'Timetables',
                'parent' => 'Profiles.Student',
                'selected' => ['Profiles.StudentScheduleTimetable'],
                'params' => ['plugin' => 'Profile']
            ],
            'Profiles.StudentBankAccounts' => [
                'title' => 'Finance',
                'parent' => 'Profiles.Student',
                'params' => [
                    'plugin' => 'Profile',
                    'type' => 'student'
                ],
                'selected' => [
                    'Profiles.StudentBankAccounts',
                    'Profiles.StudentFees'
                ]
            ],/*POCOR-6286 - added profiles menu*/
            'Profiles.StudentProfiles' => [
                'title' => 'Profiles',
                'parent' => 'Profiles.Student',
                'params' => [
                    'plugin' => 'Profile',
                    'type' => 'student'
                ],
                'selected' => ['Profiles.StudentProfiles']
            ]
        ];
        return $navigation;
    }

    public function getGuardianNavNavigation()
    {
        // $request = $this->getController()->getRequest();
        // $session = $this->getController()->getRequest()->getSession();
        // $studentId = $session->read('Student.Students.id');
        // if(empty($studentId) && $request->getParam('action') == 'StudentUser') {
        //     $studentId = $this->controller->paramsDecode($request->getParam('pass')[1])['id'];
        // }
        // $queryString = $this->request->getQuery['queryString']; // comment cakephp4
        // $queryString = '';
        // if ($queryString != '') {
        //     $session->write('queryString', $queryString);
        // } else {
        //     $queryString = $session->read('queryString');
        // }
        // POCOR-8415 start
        $studentId = $this->getStudentID();
        if (!$studentId) {
            $session = $this->getController()->getRequest()->getSession();
            $studentId = $session->read('Student.Students.id');
        }
        $decodedQueryString = $this->controller->getQueryString();
        $decodedQueryString['id'] = $studentId;
        $decodedQueryString['student_id'] = $studentId;
        $queryString = $this->controller->paramsEncode($decodedQueryString);
        // POCOR-8415 end
        $navigation = [
            'GuardianNavs.StudentUser.view' => [
                'title' => 'General',
                'parent' => 'GuardianNavs.GuardianNavs.index',
                'params' => [
                    'plugin' => 'GuardianNav',
                    '1' => $queryString
                ], // POCOR-8415
                'selected' => ['GuardianNavs.StudentUser']
            ],

            'GuardianNavs.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'GuardianNavs.GuardianNavs.index',
                'params' => ['plugin' => 'GuardianNav'],
                'selected' => [
                    'GuardianNavs.StudentClasses',
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
                    'GuardianNavs.StudentAssociations'
                ]
            ],
            //POCOR-8293 start
            'GuardianNavs.Healths.index' => [
                'title' => 'Student Health',
                'parent' => 'GuardianNavs.GuardianNavs.index',
                'params' => [
                    'plugin' => 'GuardianNav',
                    '1' => $this->controller->paramsEncode(['id' => $studentId, 'security_user_id' => $studentId]),
                    'queryString' => $queryString
                ],
                'selected' => [
                    'GuardianNavs.Healths',
                    'GuardianNavs.HealthAllergies',
                    'GuardianNavs.HealthConsultations',
                    'GuardianNavs.HealthFamilies',
                    'GuardianNavs.HealthHistories',
                    'GuardianNavs.HealthImmunizations',
                    'GuardianNavs.HealthMedications',
                    'GuardianNavs.HealthTests',
                    'GuardianNavs.HealthBodyMasses',
                    'GuardianNavs.HealthInsurances'
                ]
            ]
            //POCOR-8293 end
        ];
        foreach ($navigation as &$n) {
            if (isset($n['params'])) {
                $n['params']['1'] = $queryString; // POCOR-8415
            }
        }
        return $navigation;
    }

    public function getReportNavigation()
    {
        $navigation = [
            // 'Reports.Directory' => [
            //     'title' => 'Directory',
            //     'parent' => 'Reports',
            //     'params' => ['plugin' => 'Report'],
            // ],

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
        $connectionTable = TableRegistry::getTableLocator()->get('Archive.DataManagementConnections');
        $connectionData = $connectionTable->find()->select(['id'])->first()->toArray();
        $connectionId = $this->controller->paramsEncode(['id' => $connectionData['id']]);
        /*for POCOR-5674 */

        $queryString = $this->request->getQuery['queryString'];
        //POCOR-7527 start
        $firstSubMenuAdmin = $this->getAdminstrationFirstNav();
        $SecurityNav = $this->getAdminstrationSecurityNav();
        $ProfileNav = $this->getAdminstrationProfileNav();
        $SurveyNav = $this->getAdminstrationSurveyNav();
        $CommunicationsNav = $this->getAdminstrationCommunicationsNav();
        $TrainingNav = $this->getAdminstrationTrainingNav();
        $PerformanceNav = $this->getAdminstrationPerformanceNav();
        $ExaminationNav = $this->getAdminstrationExaminationNav();
        $StaffNav = $this->getAdministrationStaffNav();    // POCOR-8128
        $ScholarshipNav = $this->getAdminstrationScholarshipNav();
        $MoodleNav = $this->getAdminstrationMoodleNav();
        $dataMgtNav = $this->getAdminstrationdataMgtNav();
        //POCOR-7527 end
        $navigation = [

            'StaffAppraisals.Criterias.index' => [
                'title' => 'Appraisals',
                'parent' => 'Administration',
                'params' => ['plugin' => 'StaffAppraisal'],
                'selected' => [
                    'StaffAppraisals.Criterias',
                    'StaffAppraisals.Forms',
                    'StaffAppraisals.Types',
                    'StaffAppraisals.Periods',
                    'StaffAppraisals.Scores'
                ]
            ],

            'Textbooks.Textbooks' => [
                'title' => 'Textbooks',
                'parent' => 'Administration',
                'params' => ['plugin' => 'Textbook'],
                'selected' => [
                    'Textbooks.Textbooks',
                    'Textbooks.ImportTextbooks'
                ]
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
                'selected' => [
                    'Workflows.Workflows',
                    'Workflows.Steps',
                    'Workflows.Actions',
                    'Workflows.Rules',
                    'Workflows.Statuses',
                    'WorkflowStatuses'
                ]
            ],
            'Systems.Updates' => [
                'title' => 'Updates',
                'parent' => 'Administration',
                'params' => ['plugin' => 'System'],
                'selected' => ['Systems.Updates']
            ],
            'Calendars.Calendars' => [
                'title' => 'Calendars',
                'parent' => 'Administration',
                'selected' => ['Calendars.Calendars']
            ],

        ];

        // POCOR-8128 start
        $getallNavigation = array_merge(
            $firstSubMenuAdmin,
            $SecurityNav,
            $ProfileNav,
            $SurveyNav,
            $CommunicationsNav,
            $TrainingNav,
            $PerformanceNav,
            $ExaminationNav,
            $StaffNav,
            $ScholarshipNav,
            $navigation,
            $MoodleNav,
            $dataMgtNav
        ); //POCOR-7527
        // POCOR-8128 end
        return $getallNavigation;
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
                    'selected' => [
                        'Areas.Areas',
                        'Areas.Levels',
                        'Areas.AdministrativeLevels',
                        'Areas.Administratives'
                    ]
                ],
                'AcademicPeriods.Periods' => [
                    'title' => 'Academic Periods',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'AcademicPeriod'],
                    'selected' => [
                        'AcademicPeriods.Periods',
                        'AcademicPeriods.Levels'
                    ]
                ],
                'Educations.Systems' => [
                    'title' => 'Education Structure',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Education'],
                    'selected' => [
                        'Educations.Systems',
                        'Educations.Levels',
                        'Educations.Cycles',
                        'Educations.Programmes',
                        'Educations.Grades',
                        'Educations.Stages',
                        'Educations.Subjects',
                        'Educations.GradeSubjects',
                        'Educations.Certifications',
                        'Educations.FieldOfStudies',
                        'Educations.ProgrammeOrientations',
                        'Educations.CopySystems'
                    ]
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
                    'selected' => [
                        'FieldOptions.index',
                        'FieldOptions.add',
                        'FieldOptions.view',
                        'FieldOptions.edit',
                        'FieldOptions.remove'
                    ]
                ],

                'Labels.Labels' => [
                    'title' => 'Labels',
                    'parent' => 'SystemSetup',
                    'selected' => [
                        'Labels.index',
                        'Labels.view',
                        'Labels.edit'
                    ]
                ],

                'Configurations.index' => [
                    'title' => 'System Configurations',
                    'parent' => 'SystemSetup',
                    'selected' => [
                        'Configurations.Themes',
                        'Configurations.Webhooks',
                        'Configurations.add',
                        'Configurations.view',
                        'Configurations.edit',
                        'Configurations.Authentication',
                        'Configurations.AuthSystemAuthentications',
                        'Configurations.CustomValidation',
                        'Configurations.AdministrativeBoundaries',
                        'Configurations.Theme' => [
                            'title' => 'Themes',
                            'parent' => 'Themes',
                            'selected' => ['Notices.Notices']
                        ]
                    ]
                ],
                // Start POCOR-5188
                'Manuals.Institutions' => [
                    'title' => 'Manuals',
                    'parent' => 'SystemSetup',
                    'params' => ['plugin' => 'Manuals'], //POCOR-8732
                    'selected' => [
                        'Manuals.Institutions',
                        'Manuals.Directory',
                        'Manuals.Reports',
                        'Manuals.Personal',
                        'Manuals.Administration',
                        'Manuals.Guardian'
                    ]
                ],
                // End POCOR-5188

                'Notices.index' => [
                    'title' => 'Notices',
                    'parent' => 'SystemSetup',
                    'selected' => ['Notices.Notices']
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

    /**
     * POCOR-7527
     * seperate first Adminstration menu . creationg issue while provide permission
     * creation issue for dropdowin menu
     */
    private function getAdminstrationSubmenuNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Custom Fields',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
            $SecuritylocalizationFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Localization',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
            $SecurityApiFunctions = $SecurityRoleFunctions->find()
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'APIs',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                            'selected' => [
                                'InstitutionCustomFields.Fields',
                                'InstitutionCustomFields.Pages'
                            ]
                        ],
                        'StudentCustomFields.Fields' => [
                            'title' => 'Student',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StudentCustomField'],
                            'selected' => [
                                'StudentCustomFields.Fields',
                                'StudentCustomFields.Pages',
                                'StudentCustomFields.Filters'
                            ] //POCOR-8434 add filters
                        ],
                        'StaffCustomFields.Fields' => [
                            'title' => 'Staff',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StaffCustomField'],
                            'selected' => [
                                'StaffCustomFields.Fields',
                                'StaffCustomFields.Pages'
                            ]
                        ],
                        'Infrastructures.Fields' => [
                            'title' => 'Infrastructure',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'Infrastructure'],
                            'selected' => [
                                'Infrastructures.Fields',
                                'Infrastructures.LandPages',
                                'Infrastructures.BuildingPages',
                                'Infrastructures.FloorPages',
                                'Infrastructures.RoomPages'
                            ]
                        ],
                        'SystemSetup.Localization' => [
                            'title' => 'Localization',
                            'parent' => 'SystemSetup',
                            'link' => false,
                        ],
                        'Locales.index' => [
                            'title' => 'Languages',
                            'parent' => 'SystemSetup.Localization',
                            'selected' => [
                                'Locales.index',
                                'Locales.view',
                                'Locales.edit',
                                'Locales.add'
                            ]
                        ],
                        'LocaleContents.index' => [
                            'title' => 'Translations',
                            'parent' => 'SystemSetup.Localization',
                            'selected' => [
                                'LocaleContents.index',
                                'LocaleContents.view',
                                'LocaleContents.edit'
                            ]
                        ],

                        'API' => [
                            'title' => 'APIs',
                            'parent' => 'SystemSetup',
                            'link' => false
                        ],
                        'Credentials.index' => [
                            'title' => 'Credentials',
                            'parent' => 'API',
                            'selected' => [
                                'Credentials.view',
                                'Credentials.add',
                                'Credentials.edit',
                                'Credentials.delete'
                            ]
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
                            'selected' => [
                                'InstitutionCustomFields.Fields',
                                'InstitutionCustomFields.Pages'
                            ]
                        ],
                        'StudentCustomFields.Fields' => [
                            'title' => 'Student',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StudentCustomField'],
                            'selected' => [
                                'StudentCustomFields.Fields',
                                'StudentCustomFields.Pages',
                                'StudentCustomFields.Filters'
                            ] //POCOR-8434 add filters
                        ],
                        'StaffCustomFields.Fields' => [
                            'title' => 'Staff',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'StaffCustomField'],
                            'selected' => [
                                'StaffCustomFields.Fields',
                                'StaffCustomFields.Pages'
                            ]
                        ],
                        'Infrastructures.Fields' => [
                            'title' => 'Infrastructure',
                            'parent' => 'SystemSetup.CustomField',
                            'params' => ['plugin' => 'Infrastructure'],
                            'selected' => [
                                'Infrastructures.Fields',
                                'Infrastructures.LandPages',
                                'Infrastructures.BuildingPages',
                                'Infrastructures.FloorPages',
                                'Infrastructures.RoomPages'
                            ]
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
                            'selected' => [
                                'Locales.index',
                                'Locales.view',
                                'Locales.edit',
                                'Locales.add'
                            ]
                        ],
                        'LocaleContents.index' => [
                            'title' => 'Translations',
                            'parent' => 'SystemSetup.Localization',
                            'selected' => [
                                'LocaleContents.index',
                                'LocaleContents.view',
                                'LocaleContents.edit'
                            ]
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
                        'selected' => [
                            'Credentials.view',
                            'Credentials.add',
                            'Credentials.edit',
                            'Credentials.delete'
                        ]
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
                        'selected' => [
                            'InstitutionCustomFields.Fields',
                            'InstitutionCustomFields.Pages'
                        ]
                    ],
                    'StudentCustomFields.Fields' => [
                        'title' => 'Student',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'StudentCustomField'],
                        'selected' => [
                            'StudentCustomFields.Fields',
                            'StudentCustomFields.Pages',
                            'StudentCustomFields.Filters'
                        ] //POCOR-8434 add filters
                    ],
                    'StaffCustomFields.Fields' => [
                        'title' => 'Staff',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'StaffCustomField'],
                        'selected' => [
                            'StaffCustomFields.Fields',
                            'StaffCustomFields.Pages'
                        ]
                    ],
                    'Infrastructures.Fields' => [
                        'title' => 'Infrastructure',
                        'parent' => 'SystemSetup.CustomField',
                        'params' => ['plugin' => 'Infrastructure'],
                        'selected' => [
                            'Infrastructures.Fields',
                            'Infrastructures.LandPages',
                            'Infrastructures.BuildingPages',
                            'Infrastructures.FloorPages',
                            'Infrastructures.RoomPages'
                        ]
                    ],
                    'SystemSetup.Localization' => [
                        'title' => 'Localization',
                        'parent' => 'SystemSetup',
                        'link' => false,
                    ],
                    'Locales.Locales' => [
                        'title' => 'Languages',
                        'parent' => 'SystemSetup.Localization',
                        'selected' => [
                            'Locales.index',
                            'Locales.view',
                            'Locales.edit',
                            'Locales.add'
                        ]
                    ],
                    'LocaleContents.LocaleContents' => [
                        'title' => 'Translations',
                        'parent' => 'SystemSetup.Localization',
                        'selected' => [
                            'LocaleContents.index',
                            'LocaleContents.view',
                            'LocaleContents.edit'
                        ]
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
                    'Credentials.Credentials' => [
                        'title' => 'Credentials',
                        'parent' => 'API',
                        'selected' => [
                            'Credentials.view',
                            'Credentials.add',
                            'Credentials.edit',
                            'Credentials.delete'
                        ]
                    ],
                ];
        }
        return $navigationToAppends;
    }

    private function getAdminstrationSecurityNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Security',
                    $securityFunctions->aliasField('module') => 'Administration',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                        'selected' => [
                            'Securities.Users',
                            'Securities.Accounts'
                        ]
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
                        'selected' => [
                            'Securities.Roles',
                            'Securities.Permissions'
                        ]
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
                    'selected' => [
                        'Securities.Users',
                        'Securities.Accounts'
                    ]
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
                    'selected' => [
                        'Securities.Roles',
                        'Securities.Permissions'
                    ]
                ],
            ];
        }
        return $navOne;
    }

    private function getAdminstrationProfileNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Profiles',
                    $securityFunctions->aliasField('module') => 'Administration',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                        'selected' => [
                            'ProfileTemplates.InstitutionProfiles',
                            'ProfileTemplates.view',
                            'ProfileTemplates.add',
                            'ProfileTemplates.edit',
                            'ProfileTemplates.delete'
                        ]
                    ], //POCOR-6822 Starts Add menu classes
                    'ProfileTemplates.Classes' => [
                        'title' => 'Classes',
                        'parent' => 'ProfileTemplates',
                        'selected' => [
                            'ProfileTemplates.ClassesProfiles',
                            'Class.view',
                            'Class.add',
                            'Class.edit',
                            'Class.delete'
                        ]
                    ], //POCOR-6822 Ends
                    'ProfileTemplates.Staff' => [
                        'title' => 'Staff',
                        'parent' => 'ProfileTemplates',
                        'selected' => [
                            'ProfileTemplates.StaffProfiles',
                            'Staff.view',
                            'Staff.add',
                            'Staff.edit',
                            'Staff.delete'
                        ]
                    ],
                    'ProfileTemplates.Students' => [
                        'title' => 'Students',
                        'parent' => 'ProfileTemplates',
                        'selected' => [
                            'ProfileTemplates.StudentProfiles',
                            'Students.view',
                            'Students.add',
                            'Students.edit',
                            'Students.delete'
                        ]
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
                    'selected' => [
                        'ProfileTemplates.InstitutionProfiles',
                        'ProfileTemplates.view',
                        'ProfileTemplates.add',
                        'ProfileTemplates.edit',
                        'ProfileTemplates.delete'
                    ]
                ], //POCOR-6822 Starts Add menu classes
                'ProfileTemplates.Classes' => [
                    'title' => 'Classes',
                    'parent' => 'ProfileTemplates',
                    'selected' => [
                        'ProfileTemplates.ClassesProfiles',
                        'Class.view',
                        'Class.add',
                        'Class.edit',
                        'Class.delete'
                    ]
                ], //POCOR-6822 Ends
                'ProfileTemplates.Staff' => [
                    'title' => 'Staff',
                    'parent' => 'ProfileTemplates',
                    'selected' => [
                        'ProfileTemplates.StaffProfiles',
                        'Staff.view',
                        'Staff.add',
                        'Staff.edit',
                        'Staff.delete'
                    ]
                ],
                'ProfileTemplates.Students' => [
                    'title' => 'Students',
                    'parent' => 'ProfileTemplates',
                    'selected' => [
                        'ProfileTemplates.StudentProfiles',
                        'Students.view',
                        'Students.add',
                        'Students.edit',
                        'Students.delete'
                    ]
                ],
            ];
        }
        return $navTwo;
    }

    private function getAdminstrationSurveyNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        //$uId = '';
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Survey',
                    $securityFunctions->aliasField('module') => 'Administration',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                        'selected' => [
                            'Surveys.Questions',
                            'Surveys.Forms',
                            'Surveys.Rules',
                            'Surveys.Status',
                            'Surveys.Filters',
                            'Surveys.Recipients'
                        ] //POCOR-7271
                    ],

                    'Rubrics.Templates' => [
                        'title' => 'Rubrics',
                        'parent' => 'Administration.Survey',
                        'params' => ['plugin' => 'Rubric'],
                        'selected' => [
                            'Rubrics.Sections',
                            'Rubrics.Criterias',
                            'Rubrics.Options',
                            'Rubrics.Status'
                        ]
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
                    'selected' => [
                        'Surveys.Questions',
                        'Surveys.Forms',
                        'Surveys.Rules',
                        'Surveys.Status',
                        'Surveys.Filters',
                        'Surveys.Recipients'
                    ] //POCOR-7271
                ],

                'Rubrics.Templates' => [
                    'title' => 'Rubrics',
                    'parent' => 'Administration.Survey',
                    'params' => ['plugin' => 'Rubric'],
                    'selected' => [
                        'Rubrics.Sections',
                        'Rubrics.Criterias',
                        'Rubrics.Options',
                        'Rubrics.Status'
                    ]
                ],
            ];
        }
        return $navthree;
    }

    private function getAdminstrationCommunicationsNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Communications',
                    $securityFunctions->aliasField('module') => 'Administration',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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

    /**
     * @return mixed
     */
    private function getCurrentUserId()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $user_id = $this->controller->paramsDecode($userId)['id'];
        return $user_id;
    }

    //POCOR-7527

    /**
     * @param $user_id
     * @return mixed
     */
    private static function isSuperUser($user_id)
    {
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $is_super_user = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $user_id
        ])->first();
        return $is_super_user;
    }

    //POCOR-7527

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
                'selected' => [
                    'Trainings.Sessions',
                    'Trainings.Applications',
                    'Trainings.ImportTrainees'
                ]
            ],

            'Trainings.Results' => [
                'title' => 'Results',
                'parent' => 'Administration.Training',
                'params' => ['plugin' => 'Training'],
                'selected' => [
                    'Trainings.Results',
                    'Trainings.ImportTrainingSessionTraineeResults'
                ] //5695
            ],
        ];
        return $trainingNavigation;
    }

    //POCOR-7527

    /**
     * @param $user_id
     * @return array
     */
    private function getUserRoleIdArray($user_id)
    {
        //        $this->log('user_id', 'debug');
        //        $this->log($user_id, 'debug');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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

    //POCOR-7527

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
        $securityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $SecurityTrainingFunctions = $securityRoleFunctions->find()
            ->InnerJoin(
                [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                [
                    $securityFunctions->aliasField('id = ') .
                        $securityRoleFunctions->aliasField('security_function_id'),
                    $securityFunctions->aliasField('module') => $module,
                    $securityFunctions->aliasField('controller IN') => $category
                ]
            )->where(
                [
                    $securityRoleFunctions->aliasField('security_role_id IN') => $userRoleIdArray,
                    $securityRoleFunctions->aliasField($function) => 1
                ]
            )
            ->first();
        if ($SecurityTrainingFunctions) {
            $has_user_permission = true;
        }
        return $has_user_permission;
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
                'selected' => [
                    'Competencies.Templates',
                    'Competencies.Items',
                    'Competencies.Criterias',
                    'Competencies.Periods',
                    'Competencies.GradingTypes'
                ]
            ],

            'Outcomes.Templates' => [
                'title' => 'Outcomes',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'Outcome'],
                'selected' => [
                    'Outcomes.Templates',
                    'Outcomes.Criterias',
                    'Outcomes.Periods',
                    'Outcomes.GradingTypes',
                    'Outcomes.ImportOutcomeTemplates'
                ]
            ],

            'Assessments.Assessments' => [
                'title' => 'Assessments',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'Assessment'],
                'selected' => [
                    'Assessments.Assessments',
                    'Assessments.AssessmentPeriods',
                    'Assessments.GradingTypes'
                ]
            ],

            'ReportCards.Templates' => [
                'title' => 'Report Cards',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'ReportCard'],
                'selected' => [
                    'ReportCards.Templates',
                    'ReportCards.ReportCardEmail',
                    'ReportCards.Processes'
                ]
            ],
            'Gpa.GpaSystem' => [
                'title' => 'GPA',
                'parent' => 'Administration.Performance',
                'params' => ['plugin' => 'Gpa'],
                'selected' => [
                    'Gpa.GpaSystem',
                    'Gpa.Cumulative',
                    'Gpa.GpaGradingType'
                ]
            ], //POCOR-8222

        ];
        return $fullPerformanceNavigation;
    }

    //POCOR-7527

    private function getAdminstrationExaminationNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Examinations',
                    $securityFunctions->aliasField('module') => 'Administration',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                        'selected' => [
                            'Examinations.Exams',
                            'Examinations.GradingTypes'
                        ]
                    ],
                    'Examinations.ExamCentres' => [
                        'title' => 'Centres',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => [
                            'Examinations.ExamCentres',
                            'Examinations.ExamCentreRooms',
                            'Examinations.ExamCentreExams',
                            'Examinations.ExamCentreSubjects',
                            'Examinations.ExamCentreStudents',
                            'Examinations.ExamCentreInvigilators',
                            'Examinations.ExamCentreLinkedInstitutions',
                            'Examinations.ImportExaminationCentreRooms'
                        ]
                    ],
                    'Examinations.RegisteredStudents' => [
                        'title' => 'Students',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => [
                            'Examinations.RegisteredStudents',
                            'Examinations.RegistrationDirectory',
                            'Examinations.NotRegisteredStudents'
                        ]
                    ],
                    'Examinations.ExamResults' => [
                        'title' => 'Results',
                        'parent' => 'Administration.Examinations',
                        'params' => ['plugin' => 'Examination'],
                        'selected' => [
                            'Examinations.ExamResults',
                            'Examinations.Results',
                            'Examinations.ImportResults'
                        ]
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
                    'selected' => [
                        'Examinations.Exams',
                        'Examinations.GradingTypes'
                    ]
                ],
                'Examinations.ExamCentres' => [
                    'title' => 'Centres',
                    'parent' => 'Administration.Examinations',
                    'params' => ['plugin' => 'Examination'],
                    'selected' => [
                        'Examinations.ExamCentres',
                        'Examinations.ExamCentreRooms',
                        'Examinations.ExamCentreExams',
                        'Examinations.ExamCentreSubjects',
                        'Examinations.ExamCentreStudents',
                        'Examinations.ExamCentreInvigilators',
                        'Examinations.ExamCentreLinkedInstitutions',
                        'Examinations.ImportExaminationCentreRooms'
                    ]
                ],
                'Examinations.RegisteredStudents' => [
                    'title' => 'Students',
                    'parent' => 'Administration.Examinations',
                    'params' => ['plugin' => 'Examination'],
                    'selected' => [
                        'Examinations.RegisteredStudents',
                        'Examinations.RegistrationDirectory',
                        'Examinations.NotRegisteredStudents'
                    ]
                ],
                'Examinations.ExamResults' => [
                    'title' => 'Results',
                    'parent' => 'Administration.Examinations',
                    'params' => ['plugin' => 'Examination'],
                    'selected' => [
                        'Examinations.ExamResults',
                        'Examinations.Results',
                        'Examinations.ImportResults'
                    ]
                ],

            ];
        }
        return $navseven;
    }

    //POCOR-7527

    // POCOR-8128
    private function getAdministrationStaffNav()
    {


        $nav = [
            'Administration.Staff' => [
                'title' => 'Staff',
                'parent' => 'Administration',
                'link' => false,
            ],
            'Systems.StaffPolicies' => [
                'title' => 'Leaves',
                'parent' => 'Administration.Staff',
                'link' => true,
            ],
            'Systems.StaffEntitlements' => [
                'title' => 'Entitlements',
                'parent' => 'Administration.Staff',
                'link' => true,
            ],

        ];

        return $nav;
    }

    private function getAdminstrationScholarshipNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('module') => 'Administration',
                    $securityFunctions->aliasField('controller') => 'Scholarships',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                        'selected' => [
                            'Scholarships.Scholarships',
                            'ScholarshipAttachmentTypes.index',
                            'ScholarshipAttachmentTypes.view',
                            'ScholarshipAttachmentTypes.add',
                            'ScholarshipAttachmentTypes.edit',
                            'ScholarshipAttachmentTypes.delete'
                        ]
                    ],
                    'Scholarships.Applications' => [
                        'title' => 'Applications',
                        'parent' => 'Administration.Scholarships',
                        'params' => ['plugin' => 'Scholarship'],
                        'selected' => [
                            'Scholarships.Applications.index',
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
                            'Scholarships.ScholarshipApplicationInstitutionChoices.index',
                            'Scholarships.ScholarshipApplicationInstitutionChoices.add',
                            'Scholarships.ScholarshipApplicationAttachments',
                        ]
                    ],
                    // 'ScholarshipRecipients.index' => [
                    //     'title' => 'Recipients',
                    //     'parent' => 'Administration.Scholarships',
                    //     'params' => ['plugin' => 'Scholarship'],
                    //     'selected' => ['ScholarshipRecipients.index',
                    //         'ScholarshipRecipients.view',
                    //         'ScholarshipRecipients.edit',
                    //         'ScholarshipRecipientInstitutionChoices.index',
                    //         'ScholarshipRecipientInstitutionChoices.view',
                    //         'ScholarshipRecipientInstitutionChoices.edit',
                    //         'Scholarships.RecipientPaymentStructures',
                    //         'Scholarships.RecipientPayments',
                    //         'ScholarshipRecipientCollections.index',
                    //         'ScholarshipRecipientCollections.view',
                    //         'ScholarshipRecipientCollections.add',
                    //         'ScholarshipRecipientCollections.edit',
                    //         'ScholarshipRecipientCollections.delete',
                    //         'ScholarshipRecipientAcademicStandings.index',
                    //         'ScholarshipRecipientAcademicStandings.view',
                    //         'ScholarshipRecipientAcademicStandings.add',
                    //         'ScholarshipRecipientAcademicStandings.edit',
                    //         'ScholarshipRecipientAcademicStandings.delete']
                    // ],
                    'Scholarships.ScholarshipRecipients' => [
                        'title' => 'Recipients',
                        'parent' => 'Administration',
                        'selected' => ['Scholarships.ScholarshipRecipients']
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
                    'selected' => [
                        'Scholarships.Scholarships',
                        'ScholarshipAttachmentTypes.index',
                        'ScholarshipAttachmentTypes.view',
                        'ScholarshipAttachmentTypes.add',
                        'ScholarshipAttachmentTypes.edit',
                        'ScholarshipAttachmentTypes.delete'
                    ]
                ],
                'Scholarships.Applications' => [
                    'title' => 'Applications',
                    'parent' => 'Administration.Scholarships',
                    'params' => ['plugin' => 'Scholarship'],
                    'selected' => [
                        'Scholarships.Applications.index',
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
                        'Scholarships.ScholarshipApplicationInstitutionChoices.index',
                        'Scholarships.ScholarshipApplicationInstitutionChoices.add',
                        'Scholarships.ScholarshipApplicationInstitutionChoices.view',
                        'Scholarships.ScholarshipApplicationInstitutionChoices.edit',
                        'Scholarships.ScholarshipApplicationAttachments',
                    ]
                ],
                // 'ScholarshipRecipients.index' => [
                //     'title' => 'Recipients',
                //     'parent' => 'Administration.Scholarships',
                //     'params' => ['plugin' => 'Scholarship'],
                //     'selected' => ['ScholarshipRecipients.index',
                //         'ScholarshipRecipients.view',
                //         'ScholarshipRecipients.edit',
                //         'ScholarshipRecipientInstitutionChoices.index',
                //         'ScholarshipRecipientInstitutionChoices.view',
                //         'ScholarshipRecipientInstitutionChoices.edit',
                //         'Scholarships.RecipientPaymentStructures',
                //         'Scholarships.RecipientPayments',
                //         'ScholarshipRecipientCollections.index',
                //         'ScholarshipRecipientCollections.view',
                //         'ScholarshipRecipientCollections.add',
                //         'ScholarshipRecipientCollections.edit',
                //         'ScholarshipRecipientCollections.delete',
                //         'ScholarshipRecipientAcademicStandings.index',
                //         'ScholarshipRecipientAcademicStandings.view',
                //         'ScholarshipRecipientAcademicStandings.add',
                //         'ScholarshipRecipientAcademicStandings.edit',
                //         'ScholarshipRecipientAcademicStandings.delete']
                // ],
                'Scholarships.ScholarshipRecipients' => [
                    'title' => 'Recipients',
                    'parent' => 'Administration.Scholarships',
                    'selected' => ['Scholarships.ScholarshipRecipients']
                ],
            ];
        }
        return $navEight;
    }

    private function getAdminstrationMoodleNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'MoodleApi',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                    'MoodleApi.index' => [
                        'title' => 'Log',
                        'parent' => 'Administration.MoodleApi',
                        'selected' => ['MoodleApi.MoodleApi'],
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
                'MoodleApi.index' => [
                    'title' => 'Log',
                    'parent' => 'Administration.MoodleApi',
                    'selected' => ['MoodleApi.MoodleApi'],
                ],

            ];
        }
        return $navMoodle;
    }

    private function getAdminstrationdataMgtNav()
    {
        $session = $this->getController()->getRequest()->getSession();
        $userId = $this->controller->paramsEncode(['id' => $session->read('Auth.User.id')]);
        $uId = $this->controller->paramsDecode($userId)['id'];
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userinfo = $users->find()->where([
            $users->aliasField('super_admin') => 1,
            $users->aliasField('id') => $uId
        ])->first();

        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityRole = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
                ->LeftJoin(
                    [$securityFunctions->getAlias() => $securityFunctions->getTable()],
                    [
                        $securityFunctions->aliasField('id = ') . $SecurityRoleFunctions->aliasField('security_function_id'),
                    ]
                )->where([
                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $rowId,
                    $securityFunctions->aliasField('category') => 'Archive',
                    $SecurityRoleFunctions->aliasField('_view') => 1
                ])->toArray();
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
                        'params' => [
                            'plugin' => 'Archive',
                            'controller' => 'Archives',
                            'action' => 'CopyData'
                        ],
                    ],
                    'Archive.Backup' => [
                        'title' => 'Backup',
                        'parent' => 'Administration.Archive',
                        'selected' => ['Archives.BackupLog'],
                        'params' => [
                            'plugin' => 'Archive',
                            'controller' => 'Archives',
                            'action' => 'BackupLog'
                        ],
                    ],
                    'Archive.Transfer' => [
                        'title' => 'Archive',
                        'parent' => 'Administration.Archive',
                        'params' => [
                            'plugin' => 'Archive',
                            'controller' => 'Archives',
                            'action' => 'Transfer'
                        ],
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
                    'params' => [
                        'plugin' => 'Archive',
                        'controller' => 'Archives',
                        'action' => 'CopyData'
                    ],
                ],
                'Archive.Backup' => [
                    'title' => 'Backup',
                    'parent' => 'Administration.Archive',
                    'selected' => ['Archives.BackupLog'],
                    'params' => [
                        'plugin' => 'Archive',
                        'controller' => 'Archives',
                        'action' => 'BackupLog'
                    ],
                ],
                'Archive.Transfer' => [
                    'title' => 'Archive',
                    'parent' => 'Administration.Archive',
                    'params' => [
                        'plugin' => 'Archive',
                        'controller' => 'Archives',
                        'action' => 'Transfer'
                    ],
                    'selected' => ['Archives.Transfer'],
                ],
            ];
        }
        return $navdataMgt;
    }

    public function checkSelectedLink(array &$navigations)
    {
        // Set the pass variable
        if (!empty($this->getController()->getRequest()->getParam('pass'))) {
            $pass = $this->getController()->getRequest()->getParam('pass');
        } else {
            $pass[0] = '';
        }

        // The URL name "Controller.Action.Model or Controller.Action"
        $controller = $this->getController()->getName();
        $action = $this->action;
        $linkName = $controller . '.' . $action;
        $controllerActionLink = $linkName;
        if (!empty($pass[0])) {
            $linkName .= '.' . $pass[0];
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

    public function checkPermissionsOne(array &$navigations)
    {
        // $session = $this->request->session();
        // $superAdmin = $session->read('Auth.User.super_admin');
        // if ($superAdmin) {
        //     return;
        // }

        $user_id = $this->getCurrentUserId();
        $superAdmin = self::isSuperUser($user_id);
        if ($superAdmin) {
            return;
        }

        $roles = [];
        $restrictedTo = [];
        $event = $this->controller->dispatchEvent('Controller.Navigation.onUpdateRoles', null, $this);

        if ($event->getResult()) {
            $roles = $event->getResult('roles');
            $restrictedTo = $event->getResult('restrictedTo');
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
                    if (!$restrictedURL) {
                        $restrictedURL = [];
                    }
                    if (count(array_intersect($restrictedURL, $url)) > 0) {
                        break;
                    } else {
                        $rolesRestrictedTo = [];
                    }
                }
                // POCOR-8436 removed strange option
                if (isset($url['controller'])) {
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

    public function checkPermissions(array &$navigations)
    {
        $user_id = $this->getCurrentUserId();
        $superAdmin = self::isSuperUser($user_id);
        if ($superAdmin) {
            return;
        }

        $roles = [];
        $restrictedTo = [];
        $event = $this->controller->dispatchEvent('Controller.Navigation.onUpdateRoles', null, $this);
        //    dd($event->getResult());
        // POCOR-8527 start fix roles for navs
        if ($event->getResult()) {
            $result = $event->getResult();
            $roles = $result['roles'];
            $restrictedTo = $result['restrictedTo'];
            $isRestricted = false;
        } else {
            $rolesByUser = $this->AccessControl->getRolesByUser()->toArray();
            foreach ($rolesByUser as $key => $role) {
                $roles[$role->security_role_id] = $role->security_role_id;
            }
            $isRestricted = true;
        }
        // POCOR-8527 end
        // Unset the children
        $linkOnly = [];
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
                // POCOR-8128 start
                if (isset($value['link']) && $value['link']) {
                    $params = ['plugin' => 'Systems'];
                    $url = $this->getLink($key, $params);
                }
                // POCOR-8128 end
                // Ensure $url is an array and has necessary keys
                if (!is_array($url) || !isset($url['controller'], $url['action'], $url['plugin'])) {
                    // Log or handle the case where $url is not as expected
                    // Example: Log error and continue or skip this navigation item
                    unset($navigations[$key]);
                    continue;
                }

                // Check if $restrictedTo is an array
                if (!is_array($restrictedTo)) {
                    // Log or handle the case where $restrictedTo is not an array
                    // Example: Log error and continue or skip this navigation item
                    unset($navigations[$key]);
                    continue;
                }

                // Check if the role is only restricted to a certain page
                //                $isRestricted = false; // POCOR-8527
                foreach ($restrictedTo as $restrictedURLs) {
                    if (!is_array($restrictedURLs)) {
                        // Log or handle the case where $restrictedURLs is not an array
                        // Example: Log error and continue or skip this navigation item
                        continue;
                    }

                    $intersection = array_intersect($restrictedURLs, $url);
                    if (count($intersection) > 0) {
                        $isRestricted = true;
                        break;
                    }
                }

                // If roles are restricted, check permissions
                if ($isRestricted) {
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

    public function checkPermissionsOld(array &$navigations)
    {
        $linkOnly = [];
        //$ignoredPlugin = ['Profile']; // Plugin that will be excluded from checking //POCOR-5312
        $roles = [];
        $restrictedTo = [];
        $event = $this->controller->dispatchEvent('Controller.Navigation.onUpdateRoles', null, $this);
        if ($event->getResult()) {
            $roles = $event->getResult('roles');
            $restrictedTo = $event->getResult('restrictedTo');
        }

        // Unset the children
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
                //                Log::debug(print_r($url, true));

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
                if (isset($url['controller']) && !in_array($url['plugin'])) {
                    //   print_r($url);die();
                    if (!$this->AccessControl->check($url, $rolesRestrictedTo)) {
                        unset($navigations[$key]);
                    }
                }
            }
        }
        // unset the parents if there is no children
        //        $linkOnly = array_reverse($linkOnly);
        //            foreach ($linkOnly as $link) {
        //                if (!array_search($link, $this->array_column($navigations, 'parent'))) {
        //                    unset($navigations[$link]);
        //                }
        //            }
    }

    private function getLink($controllerActionModelLink, $params = [])
    {
        $url = ['plugin' => null, 'controller' => null, 'action' => null];
        if (isset($params['plugin'])) {
            $url['plugin'] = $params['plugin'];
            unset($params['plugin']);
        }

        $link = explode('.', $controllerActionModelLink);
        if (sizeof($link) <= 3) {
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
        } else {
            if (isset($params['plugin'])) {
                $url['plugin'] = $params['plugin'];
                unset($params['plugin']);
            } else if (isset($link[0])) {
                $url['plugin'] = $link[0];
            }
            if (isset($params['controller'])) {
                $url['controller'] = $params['controller'];
                unset($params['controller']);
            } else if (isset($link[1])) {
                $url['controller'] = $link[1];
            }

            if (isset($params['action'])) {
                $url['action'] = $params['action'];
                unset($params['action']);
            } else if (isset($link[2])) {
                $url['action'] = $link[2];
            }

            if (isset($link[3])) {
                $url['0'] = $link[3];
            }
        }
        if (!empty($params)) {
            $url = array_merge($url, $params);
        }
        return $url;
    }

    public function getProfileGuardianStudentNavigation()
    {
        $sID = $this->request->getParam('pass')[1];
        $session = $this->getController()->getRequest()->getSession();
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
                'params' => [
                    'plugin' => 'Profile',
                    'controller' => 'Profiles',
                    'action' => 'ProfileStudentUser',
                    0 => 'view',
                    $studentId
                ],
                'selected' => ['Profiles.ProfileStudentUser']
            ],
            'Profiles.StudentProgrammes.index' => [
                'title' => 'Academic',
                'parent' => 'Profiles.ProfileStudents.index',
                'params' => [
                    'plugin' => 'Profile',
                    'controller' => 'Profiles',
                    $studentId
                ],
                'selected' => [
                    'Profiles.StudentProgrammes.index',
                    'Profiles.StudentSubjects',
                    'Profiles.StudentClasses',
                    'Profiles.StudentAbsences',
                    'Profiles.StudentBehaviours',
                    'Profiles.StudentCompetencies',
                    'Profiles.StudentCompetencies.index',
                    'Profiles.StudentResults',
                    'Profiles.StudentExaminationResults',
                    'Profiles.StudentReportCards',
                    'Profiles.StudentAwards',
                    'Profiles.StudentExtracurriculars',
                    'Profiles.StudentTextbooks',
                    'Profiles.StudentOutcomes',
                    'Profiles.StudentRisks',
                    'Profiles.StudentAssociations',
                    'Profiles.Absences'
                ]
            ],
        ];

        return $navigation;
    }

    private
    function getClassID($debug = "")
    {
        // POCOR-8115;
        // class_id should always be in query string, if not, die as an error
        $class_id = $this->controller->getQueryString('class_id');
        if ($debug != "") {
            if (!$class_id) {
                die($debug . 'For Developer: You should put class_id into query string first');
            }
        }
        return $class_id;
    }

    /**
     * @param array $navigations
     * @return array
     */
    private function makeInstitutionNavigations(array $navigations): array
    {
        $navigations = $this->appendNavigation(
            'Institutions.Institutions.index',
            $navigations,
            $this->getInstitutionNavigation()
        );
        $navigations = $this->appendNavigation(
            'Institutions.Students.index',
            $navigations,
            $this->getInstitutionStudentNavigation()
        );
        $navigations = $this->appendNavigation(
            'Institutions.Staff.index',
            $navigations,
            $this->getInstitutionStaffNavigation()
        );
        $this->checkClassification($navigations);
        return $navigations;
    }

    /**
     * @param array $navigations
     * @return array
     */
    private function makeStudentNavigations(array $navigations): array
    {
        $navigations = $this->appendNavigation('Institutions.Institutions.index', $navigations, $this->getInstitutionNavigation());
        $navigations = $this->appendNavigation('Institutions.Students.index', $navigations, $this->getInstitutionStudentNavigation());
        $this->checkClassification($navigations);
        return $navigations;
    }

    /**
     * @param array $navigations
     * @return array
     */
    private function makeStaffNavigations(array $navigations): array
    {
        $navigations = $this->appendNavigation('Institutions.Institutions.index', $navigations, $this->getInstitutionNavigation());
        $navigations = $this->appendNavigation('Institutions.Staff.index', $navigations, $this->getInstitutionStaffNavigation());
        $this->checkClassification($navigations);
        return $navigations;
    }

    /**
     * @param array $navigations
     * @return array
     */
    private function makeGuardianNavigations(array $navigations): array
    {
        $navigations = $this->appendNavigation(
            'GuardianNavs.GuardianNavs.index',
            $navigations,
            $this->getGuardianNavNavigation()
        );
        $this->checkClassification($navigations);
        return $navigations;
    }

    /**
     * @param array $navigations
     * @param \Cake\Http\ServerRequest $request
     * @return array
     */
    private function makeProfileNavigations(array $navigations, \Cake\Http\ServerRequest $request): array
    {
        $navigations = $this->appendNavigation('Profiles.Profiles', $navigations, $this->getProfileNavigation());
        $navigations = $this->appendNavigation('Profiles.Personal', $navigations, $this->getProfileNavigation());

        $session = $request->getSession();
        $isStudent = $session->read('Auth.User.is_student');
        $isStaff = $session->read('Auth.User.is_staff');

        if ($isStaff) {
            $navigations = $this->appendNavigation(
                'Profiles.Profiles.view',
                $navigations,
                $this->getProfileStaffNavigation()
            );
            $session->write('Profile.Profiles.reload', true);
        }

        if ($isStudent) {
            $navigations = $this->appendNavigation(
                'Profiles.Profiles.view',
                $navigations,
                $this->getProfileStudentNavigation()
            );
            $session->write('Profile.Profiles.reload', true);
        }
        return $navigations;
    }

    /**
     * @param array $navigations
     * @return array
     */
    private function makeDirectoryNavigations(array $navigations): array
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $this->request = $request;
        $session = $request->getSession();
        $action = $this->action;
        $pass = $request->getParam('pass');

        $directoryActions = [
            'StaffEmploymentStatuses',
            'StaffPositions',
            'StaffClasses',
            'StaffSubjects',
            'StaffLeave',
            'StaffAttendances',
            'StaffBehaviours',
            'StaffAppraisals',
            'StaffDuties',
            'StaffAssociations',
            'Directories',
            'Accounts',
            'TrainingNeeds',
            'StaffProfiles',
            'StaffBankAccounts',
            'HistoricalStaffPositions',
            'HistoricalStaffLeave',
            'StaffSalaries',
            'StaffPayslips',
            'Courses',
            'TrainingResults',
            'Healths',
            'HealthAllergies',
            'HealthConsultations',
            'HealthFamilies',
            'HealthHistories',
            'HealthImmunizations',
            'HealthMedications',
            'HealthTests',
            'HealthBodyMasses',
            'HealthInsurances',
            'Employments',
            'StaffQualifications',
            'StaffMemberships',
            'StaffLicenses',
            'StaffAwards',
            'SpecialNeedsReferrals',
            'SpecialNeedsAssessments',
            'SpecialNeedsServices',
            'SpecialNeedsDevices',
            'SpecialNeedsPlans',
            'SpecialNeedsDiagnostics',
            'StudentBankAccounts',
            'Counsellings',
            'StudentFees',
            'StudentLicenses',
            'ImportSalaries'

        ];

        $queryString = $controller->getQueryString();
        $navigations = $this->appendNavigation('Directories.Directories.index', $navigations, $this->getDirectoryNavigation());

        if ($action === 'ImportUsers') {
            $queryString = null; // POCOR-8683 ignore query string
        }

        $securityUserId = $queryString['security_user_id'];
        $id = $queryString['id'];
        $staffId = $queryString['staff_id'];
        $studentId = $queryString['student_id'];
        /*POCOR-STARTS*/
        if (!empty($queryString)) {
            if ($securityUserId) {
                $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($securityUserId);
            } else if ($id) {
                $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($id);
            }
            if ($action == 'GuardianStudents') {
                $userInfo = TableRegistry::getTableLocator()->get('Guardian.Students')->get($securityUserId);
            }
            if ($action == 'StudentGuardians') {
                $studentId = $id;
                try {
                    $studentInfo = TableRegistry::getTableLocator()->get('Student.StudentGuardians')->get($studentId); //POCOR-6453 ends
                    $guardianId = $studentInfo->guardian_id;
                    $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($guardianId); //POCOR-6453 ends
                } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
                    // Handle the exception, e.g., log the error or set $userInfo to null
                    $userInfo = null;
                }
            }
            if ($action == 'Identities') { //POCOR-6453 starts
                //                $securityUserId = $this->controller->paramsDecode($this->request->getQuery('queryString'));
                $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($securityUserId); //POCOR-6453 ends
            } /*POCOR-6286 : added condition to get selected student id */
            if ($action == 'StudentProfiles') {
                $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($studentId);
            } //Start POCOR-7055
            if ($action == 'StudentReportCards' || $action == 'StudentAwards') {
                $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($studentId);
            }
            if (
                $this->request->getParam('plugin') == 'Directory'
                && $this->request->getParam('controller') == 'Directories'
                && $pass[0] == 'download' && $action == 'Attachments'
            ) {
                $userId = $this->controller->paramsDecode($pass[2])['security_user_id'];
                $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($userId);
            } // End POCOR-7384
            if ($this->request->getParam('controller') == 'Directories' && in_array($action, $directoryActions)) {
                if ($action == 'Directories' || $action == 'Accounts') {
                    $userId = $id;
                } else {
                    $userId = $staffId;
                }
                if (empty($userId)) {
                    $userId = $securityUserId;
                }
                if (empty($userId)) {
                    $userId = $studentId;
                }
                $userInfo = TableRegistry::getTableLocator()->get('Security.Users')->get($userId);
            }
            //POCOR-6202 end
        }
        $userType = '';
        if (!empty($userInfo)) {
            if ($userInfo->is_student && $userInfo->is_staff == 0 && $userInfo->is_guardian == 0) {
                $userType = 1;
            } elseif ($userInfo->is_staff && $userInfo->is_student == 0 && $userInfo->is_guardian == 0) {
                $userType = 2;
            } elseif ($userInfo->is_guardian && $userInfo->is_staff == 0 && $userInfo->is_student == 0) {
                $userType = 3;
            } elseif ($userInfo->is_student == 1 && $userInfo->is_staff == 1 && $userInfo->is_guardian == 1) {
                $userType = 4; //superrole user
            } elseif ($userInfo->is_student == 1 && $userInfo->is_staff == 1 && $userInfo->is_guardian == 0) {
                $userType = 5;
            } /*POCOR-6332 starts*/ elseif ($userInfo->is_student == 1 && $userInfo->is_staff == 0 && $userInfo->is_guardian == 1) {
                $userType = 6;
            } elseif ($userInfo->is_student == 0 && $userInfo->is_staff == 1 && $userInfo->is_guardian == 1) {
                $userType = 7;
            }/*POCOR-6332 ends*/
        }


        $userType = '';
        if (!empty($userInfo)) {
            if ($userInfo->is_student) {
                $userType = 1;
            } elseif ($userInfo->is_staff) {
                $userType = 2;
            } elseif ($userInfo->is_guardian) {
                $userType = 3;
            }
        }
        $session = $request->getSession();
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
        return $navigations;
        // POCOR-6372 (end) initially here userType was checking but it did not work for directory navigation so changed with roles
        /*POCOR-6332 ends*/
    }
}
