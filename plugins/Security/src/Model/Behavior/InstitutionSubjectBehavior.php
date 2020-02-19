<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\ResultSet;
use Cake\Event\Event;
use Cake\I18n\Date;

class InstitutionSubjectBehavior extends Behavior
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

        if (array_key_exists('user', $options) && is_array($options['user'])) { // the user object is set by RestfulComponent
            $user = $options['user'];
            if ($user['super_admin'] == 0) { // if he is not super admin
                $userId = $user['id'];
                $today = Date::now();

                $query->innerJoin(['SecurityRoleFunctions' => 'security_role_functions'], [
                    'SecurityRoleFunctions.security_role_id = SecurityAccess.security_role_id',
                    'SecurityRoleFunctions.`_view` = 1' // check if the role have view access
                ])
                ->innerJoin(['SecurityFunctions' => 'security_functions'], [
                    'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    "SecurityFunctions.controller = 'Institutions'" // only restricted to permissions of Institutions
                ])
                ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                    'InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id'
                ])
                ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                    'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
                ])
                ->where([
                    // basically if AllSubjects permission is granted, the user should see all subjects of that classes
                    // if MySubjects permission is granted, the user must be a teacher of that subject
                    'OR' => [
                        [
                            'OR' => [ // AllSubjects permissions
                                "SecurityFunctions.`_view` LIKE '%AllSubjects.index%'",
                                "SecurityFunctions.`_view` LIKE '%AllSubjects.view%'"
                            ]
                        ], [
                            'AND' => [
                                [
                                    'OR' => [ // MySubjects permissions
                                        "SecurityFunctions.`_view` LIKE '%Subjects.index%'",
                                        "SecurityFunctions.`_view` LIKE '%Subjects.view%'"
                                    ]
                                ],
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id',
                                'InstitutionSubjectStaff.staff_id' => $userId,
                                'OR' => [
                                    'InstitutionSubjectStaff.end_date IS NULL',
                                    'InstitutionSubjectStaff.end_date >= ' => $today->format('Y-m-d')
                                ]
                            ]
                        ], [
                            'AND' => [
                                [
                                    'OR' => [
                                        "SecurityFunctions.`_view` LIKE '%Classes.index%'",
                                        "SecurityFunctions.`_view` LIKE '%Classes.view%'"
                                    ]
                                ], [
                                    'EXISTS (
                                        SELECT 1
                                        FROM institution_class_subjects
                                        JOIN institution_classes
                                        ON institution_classes.id = institution_class_subjects.institution_class_id
                                        JOIN institution_classes_secondary_staff
                                        ON institution_classes_secondary_staff.institution_class_id = institution_classes.id
                                        AND (institution_classes.staff_id = ' . $userId . ' OR institution_classes_secondary_staff.secondary_staff_id = ' . $userId . ')
                                        WHERE institution_class_subjects.institution_subject_id = InstitutionSubjects.id
                                        LIMIT 1
                                    )'
                                ]
                            ]
                        ]
                    ]
                ])
                ->group([$this->_table->aliasField('id')]); // so it doesn't show duplicate subjects
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
            $userId = $this->_table->Auth->user('id');
            $AccessControl = $this->_table->AccessControl;
            $query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->_table->controller]);
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $action = 'edit';
        if (!$this->checkAllSubjectsPermission($action)) {
            if ($this->checkMySubjectsPermission($action)) {
                $userId = $this->_table->Auth->user('id');
                if (empty($entity->teachers)) {
                    $urlParams = $this->_table->url('view');
                    $event->stopPropagation();
                    $this->_table->Alert->error('security.noAccess');
                    return $this->_table->controller->redirect($urlParams);
                } else {
                    $isFound = false;
                    foreach ($entity->teachers as $staff) {
                        if ($userId == $staff->id) {
                            $isFound = true;
                            break;
                        }
                    }
                    if (! $isFound) {
                        $urlParams = $this->_table->url('view');
                        $event->stopPropagation();
                        $this->_table->Alert->error('security.noAccess');
                        return $this->_table->controller->redirect($urlParams);
                    }
                }
            }
        }
    }

    // Function to check MySubjects permission is set
    private function checkMySubjectsPermission($action)
    {
        $AccessControl = $this->_table->AccessControl;
        $controller = $this->_table->controller;
        $roles = [];
        $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
        if ($event->result) {
            $roles = $event->result;
        }
        $mySubjectsEditPermission = $AccessControl->check(['Institutions', 'Subjects', $action], $roles);
        if ($mySubjectsEditPermission) {
            return true;
        } else {
            return false;
        }
    }

    // Function to check AllSubjects permission is set
    private function checkAllSubjectsPermission($action)
    {
        $AccessControl = $this->_table->AccessControl;
        $controller = $this->_table->controller;
        $roles = [];
        $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
        if ($event->result) {
            $roles = $event->result;
        }
        $allSubjectsEditPermission = $AccessControl->check(['Institutions', 'AllSubjects', $action], $roles);
        if ($allSubjectsEditPermission) {
            return true;
        } else {
            return false;
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $action = 'view';

        // Check if the staff has access to the subject
        if (!$this->checkAllSubjectsPermission($action)) {
            if ($this->checkMySubjectsPermission($action)) {
                $isFound = false;
                $userId = $this->_table->Auth->user('id');

                // Homeroom teacher of the class will be able to view the subject
                if ($entity->has('classes')) {
                    foreach ($entity->classes as $class) {
                        if ($class->staff_id = $userId) {
                            $isFound = true;
                            break;
                        }

                        if ($class->has('classes_secondary_staff') && !empty($class->classes_secondary_staff)) {
                            $secondaryStaffList = $class->classes_secondary_staff;
                            foreach ($secondaryStaffList as $secondaryStaffEntity) {
                                if ($secondaryStaffEntity->secondary_staff_id == $userId) {
                                    $isFound = true;
                                    break;
                                }
                            }
                        }
                    }
                }

                // Teachers who are owner of the classes will be able to access the subjects
                if (!empty($entity->teachers)) {
                    foreach ($entity->teachers as $staff) {
                        if ($userId == $staff->id) {
                            $isFound = true;
                            break;
                        }
                    }
                }
            }
            if (!$isFound) {
                $urlParams = $this->_table->ControllerAction->url('index');
                $event->stopPropagation();
                $this->_table->Alert->error('security.noAccess');
                $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'institutionId' => $urlParams['institution_id'], 'action' => 'Institutions'];
                return $this->_table->controller->redirect($url);
            }
        }

        switch ($this->_table->action) {
            case 'view':
                if (!$this->checkAllSubjectsPermission('edit')) {
                    if ($this->checkMySubjectsPermission('edit')) {
                        $userId = $this->_table->Auth->user('id');
                        $staffs = [];
                        if (!empty($entity->teachers)) {
                            $staffs = $entity->teachers;
                        }
                        $isFound = false;
                        foreach ($staffs as $staff) {
                            if ($userId == $staff->id) {
                                $isFound = true;
                                break;
                            }
                        }
                        if (! $isFound) {
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

    public function findByAccess(Query $query, array $options)
    {
        if (array_key_exists('accessControl', $options)) {
            $AccessControl = $options['accessControl'];
            $userId = $options['userId'];
            $roles = [];
            if (array_key_exists('controller', $options)) {
                $controller = $options['controller'];
                $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
                if (is_array($event->result)) {
                    $roles = $event->result;
                }
            }

            $hasAllSubjectsPermission = $AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles);
            $hasMySubjectsPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
            $hasAllClassesPermission = $AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles);
            $hasMyClassesPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);

            $orConditions = [];

            if ($hasAllSubjectsPermission) {
                $orConditions[] = ['1 = 1', [], true];
            }

            if ($hasMySubjectsPermission) {
                $orConditions[] = [
                    'EXISTS (
                        SELECT 1
                        FROM institution_subject_staff
                        WHERE institution_subject_staff.institution_subject_id = ' . $this->_table->aliasField('id') . '
                        AND institution_subject_staff.staff_id = ' . $userId . '
                        LIMIT 1
                    )'
                ];
            }

            if ($hasMyClassesPermission) {
                $orConditions[] = [
                    'EXISTS (
                        SELECT 1
                        FROM institution_class_subjects
                        JOIN institution_classes
                        ON institution_classes.id = institution_class_subjects.institution_class_id
                        JOIN institution_classes_secondary_staff
                        ON institution_classes_secondary_staff.institution_class_id = institution_classes.id
                        AND (institution_classes.staff_id = ' . $userId . ' OR institution_classes_secondary_staff.secondary_staff_id = ' . $userId . ')
                        WHERE institution_class_subjects.institution_subject_id = InstitutionSubjects.id
                        LIMIT 1
                    )'
                ];
            }

            if (!$hasAllSubjectsPermission && !$hasMySubjectsPermission && !$hasMyClassesPermission) {
                $query->where(['1 = 0', [], true]);
            } else {
                $query->where([
                    'OR' => $orConditions
                ]);
            }
        }
        return $query;
    }
}
