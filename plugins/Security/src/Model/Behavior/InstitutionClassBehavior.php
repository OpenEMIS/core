<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\I18n\Date;

class InstitutionClassBehavior extends Behavior
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        // priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 100];
        // set the priority of the action button to be after the academic period behavior
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        return $events;
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        // This logic is dependent on SecurityAccessBehavior because it relies on SecurityAccess join table
        // This logic will only be triggered when the table is accessed by RestfulController
        if (array_key_exists('user', $options) && is_array($options['user']) && !array_key_exists('iss', $options['user'])) { // the user object is set by RestfulComponent
            $user = $options['user'];
            if ($user['super_admin'] == 0) { // if he is not super admin
                $userId = $user['id'];
                $today = Date::now();

                $query->innerJoin(['SecurityRoleFunctions' => 'security_role_functions'], [
                    'SecurityRoleFunctions.security_role_id = SecurityAccess.security_role_id',
                    'SecurityRoleFunctions.`_view` = 1' // check if the role have view access
                ])
                ->innerJoin(['SecurityRoles' => 'security_roles'], [
                    'SecurityRoles.id = SecurityRoleFunctions.security_role_id'
                ])
                ->innerJoin(['SecurityFunctions' => 'security_functions'], [
                    'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    "SecurityFunctions.controller = 'Institutions'" // only restricted to permissions of Institutions
                ])
                ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                    'InstitutionClassSubjects.institution_class_id = InstitutionClasses.id'
                ])
                ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                    'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
                ])
                ->leftJoin(['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                    'InstitutionClasses.id = InstitutionClassesSecondaryStaff.institution_class_id'
                ])
                ->where([
                    // basically if AllClasses permission is granted, the user should see all classes of that institution
                    // if MyClasses permission is granted, the user must either be a homeroom teacher or a secondary teacher of that class in order to see that class
                    'OR' => [
                        [
                            'OR' => [ // AllClasses permissions
                                "SecurityFunctions.`_view` LIKE '%AllClasses.index%'",
                                "SecurityFunctions.`_view` LIKE '%AllClasses.view%'",
                                "SecurityFunctions.`_view` LIKE '%AllSubjects.index%'",
                                "SecurityFunctions.`_view` LIKE '%AllSubjects.view%'"
                            ]
                        ], [
                            'AND' => [
                                [
                                    'OR' => [ // MyClasses permissions
                                        "SecurityFunctions.`_view` LIKE '%Classes.index%'",
                                        "SecurityFunctions.`_view` LIKE '%Classes.view%'"
                                    ]
                                ], [
                                    'OR' => [ // cater for both homeroom teacher and secondary teacher
                                        "InstitutionClasses.staff_id" => $userId,
                                        "InstitutionClassesSecondaryStaff.secondary_staff_id" => $userId
                                    ]
                                ],
                                'SecurityRoles.code' => 'HOMEROOM_TEACHER'
                            ]
                        ], [
                            'AND' => [
                                [
                                    'OR' => [ // MySubjects permissions
                                        "SecurityFunctions.`_view` LIKE '%Subjects.index%'",
                                        "SecurityFunctions.`_view` LIKE '%Subjects.view%'"
                                    ]
                                ],
                                'InstitutionSubjectStaff.staff_id' => $userId,
                                'OR' => [
                                    'InstitutionSubjectStaff.end_date IS NULL',
                                    'InstitutionSubjectStaff.end_date >= ' => $today->format('Y-m-d')
                                ],
                                'SecurityRoles.code' => 'TEACHER',
                                "InstitutionClasses.staff_id != " => $userId  
                            ]
                        ]
                    ]
                ])
                ->group([$this->_table->aliasField('id')]); // so it doesn't show duplicate classes

                /* Generated conditions */
                // INNER JOIN security_role_functions
                //     ON security_role_functions.security_role_id = security.security_role_id
                //     AND security_role_functions._view = 1
                // INNER JOIN security_functions ON security_functions.id = security_role_functions.security_function_id
                // WHERE (security_functions._view LIKE '%AllClasses.index%' OR security_functions._view LIKE '%AllClasses.view%')
                // OR ((security_functions._view LIKE '%Classes.index%' OR security_functions._view LIKE '%Classes.view%')
                //     AND (InstitutionClasses.staff_id = 4 OR InstitutionClasses.secondary_staff_id = 4))
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
            $userId = $this->_table->Auth->user('id');
            $AccessControl = $this->_table->AccessControl;
            $controller = $this->_table->controller;
            $query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->_table->controller]);
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $action = 'edit';
        if (!$this->checkAllClassesPermission($action)) {
            if ($this->checkMyClassesPermission($action)) {
                $userId = $this->_table->Auth->user('id');
                if (!$this->checkUserHasClassPermission($entity, $userId)) {
                    $urlParams = $this->_table->url('view');
                    $event->stopPropagation();
                    $this->_table->Alert->error('security.noAccess');
                    return $this->_table->controller->redirect($urlParams);
                }
            }
        }
    }

    public function findByAccess(Query $query, array $options)
    {
        if (array_key_exists('accessControl', $options)) {
            $AccessControl = $options['accessControl'];
            $userId = $options['userId'];
            $permission = isset($options['permission']) ? $options['permission'] : 'index';
            $roles = [];
            if (array_key_exists('controller', $options)) {
                $controller = $options['controller'];
                $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
                if (is_array($event->result)) {
                    $roles = $event->result;
                }
            }
            if (!$AccessControl->check(['Institutions', 'AllClasses', $permission], $roles)) {
                if ($AccessControl->check(['Institutions', 'Classes', $permission], $roles)) {
                    $query
                        ->leftJoin(['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            'InstitutionClasses.id = InstitutionClassesSecondaryStaff.institution_class_id'
                        ])
                        ->where([
                        'OR' => [
                            [$this->_table->aliasField('staff_id') => $userId],
                            ['InstitutionClassesSecondaryStaff.secondary_staff_id' => $userId]
                        ]
                    ]);
                } else {
                    $query->where(['1 = 0']);
                }
            }
        }
        return $query;
    }

    // Function to check MyClass permission is set
    private function checkMyClassesPermission($action)
    {
        $AccessControl = $this->_table->AccessControl;
        $controller = $this->_table->controller;
        $roles = [];
        $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
        if ($event->result) {
            $roles = $event->result;
        }
        $myClassesPermission = $AccessControl->check(['Institutions', 'Classes', $action], $roles);
        if ($myClassesPermission) {
            return true;
        } else {
            return false;
        }
    }

    // Function to check AllClass permission is set
    private function checkAllClassesPermission($action)
    {
        $AccessControl = $this->_table->AccessControl;
        $controller = $this->_table->controller;
        $roles = [];
        $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
        if ($event->result) {
            $roles = $event->result;
        }
        $allClassesPermission = $AccessControl->check(['Institutions', 'AllClasses', $action], $roles);
        if ($allClassesPermission) {
            return true;
        } else {
            return false;
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        switch ($this->_table->action) {
            case 'view':
                // To handle the redirection out of view page if the user does not have permission
                $action = 'view';
                if (!$this->checkAllClassesPermission($action)) {
                    if ($this->checkMyClassesPermission($action)) {
                        $userId = $this->_table->Auth->user('id');
                        if (!$this->checkUserHasClassPermission($entity, $userId)) {
                            $urlParams = $this->_table->ControllerAction->url('index');
                            $event->stopPropagation();
                            $this->_table->Alert->error('security.noAccess');
                            $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'institutionId' => $urlParams['institution_id'], 'action' => 'Institutions'];
                            return $this->_table->controller->redirect($url);
                        }
                    }
                }

                // If all classes can edit, then skip the removal of the button
                if (!$this->checkAllClassesPermission('edit')) {
                    // If there is no permission to edit my classes
                    if ($this->checkMyClassesPermission('edit')) {
                        $userId = $this->_table->Auth->user('id');
                        // Remove the edit button from those records who does not belong to the user
                        if (!$this->checkUserHasClassPermission($entity, $userId)) {
                            if (isset($extra['toolbarButtons']) && isset($extra['toolbarButtons']['edit'])) {
                                unset($extra['toolbarButtons']['edit']);
                            }
                            if (isset($extra['toolbarButtons']) && isset($extra['toolbarButtons']['remove'])) {
                                unset($extra['toolbarButtons']['remove']);
                            }
                        }
                    }
                }
                break;
        }
    }

    private function checkUserHasClassPermission(Entity $entity, $userId)
    {
        if ($entity->staff_id == $userId) {
            return true;
        }

        $secondaryStaffList = $entity->classes_secondary_staff;
        foreach ($secondaryStaffList as $secondaryStaffEntity) {
            if ($secondaryStaffEntity->secondary_staff_id == $userId) {
                return true;
            }
        }

        return false;
    }
}
