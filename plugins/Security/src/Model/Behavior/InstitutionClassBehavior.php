<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\I18n\Date;

class InstitutionClassBehavior extends Behavior
{
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        // priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 100];
        // set the priority of the action button to be after the academic period behavior
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        return $events;
    }

    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary)
    {
        // This logic is dependent on SecurityAccessBehavior because it relies on SecurityAccess join table
        // This logic will only be triggered when the table is accessed by RestfulController
        if (isset($options['user']) &&
            is_array($options['user']) &&
            !isset($options['user']['iss'])) { // the user object is set by RestfulComponent
            $user = $options['user'];
            if ($user['super_admin'] == 0) { // if he is not super admin
                // POCOR-7538 points 3.4-3.7: a role this user holds in the institution (the
                // SecurityAccess join) with "Access All Classes"/"Access All Subjects" sees
                // every class; otherwise only the classes the user teaches - homeroom,
                // secondary or subject teacher - whatever their role. Replaces the old
                // HOMEROOM_TEACHER/TEACHER role-code and security_functions LIKE matching,
                // which hid classes a homeroom teacher only teaches a subject in.
                $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                $query
                    ->where([
                        'OR' => [
                            'EXISTS (
                                SELECT 1
                                FROM security_roles
                                WHERE security_roles.id = SecurityAccess.security_role_id
                                AND (security_roles.access_all_classes = 1 OR security_roles.access_all_subjects = 1)
                            )',
                            $InstitutionClasses->getTeacherClassConditions($user['id'], $this->_table->aliasField('id'))
                        ]
                    ])
                    ->group([$this->_table->aliasField('id')]); // so it doesn't show duplicate classes
            }
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
            $userId = $this->_table->Auth->user('id');
            $AccessControl = $this->_table->AccessControl;
            $controller = $this->_table->controller;
            $query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->_table->controller]);
        }
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
        $InstitutionSubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        if (isset($options['accessControl'])) {
            $AccessControl = $options['accessControl'];
            $userId = $options['userId'];
            $permission = isset($options['permission']) ? $options['permission'] : 'index';
            $roles = [];
            if (isset($options['controller'])) {
                $controller = $options['controller'];
                $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
                if (is_array($event->getResult())) {
                    $roles = $event->getResult();
                }
            }
            $currentUrl = $_SERVER['REQUEST_URI'];
            // POCOR-7538 points 2.1/3.4-3.6: "Access All Classes" (role-level flag, point
            // 1.5) shows every class; otherwise only the classes the user teaches -
            // homeroom, secondary or subject teacher - whatever their role. Page CRUD is
            // gated by the Classes permission (SecurityAuthorize), not here.
            if (!$this->hasAccessAllClasses($roles)) {
                if (strpos($currentUrl, 'ReportCardStatusProgress') === false) {
                    $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                    $query->where($InstitutionClasses->getTeacherClassConditions($userId, $this->_table->aliasField('id')));
                } elseif ($AccessControl->check(['Institutions', 'Classes', $permission], $roles)) {
                    $query = $this->_table->find(); //POCOR-8773
                    $query
                        ->leftJoin(['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            'InstitutionClasses.id = InstitutionClassesSecondaryStaff.institution_class_id'
                        ])
                        ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                            'InstitutionClasses.id = InstitutionClassSubjects.institution_class_id'
                        ])
                        // ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                        //     'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
                        // ])
                        /*->where(function ($exp, $query) use ($userId) { //POCOR-8773
                            return $exp->or_([
                                $this->_table->aliasField('staff_id') => $userId,
                                'InstitutionClassesSecondaryStaff.secondary_staff_id' => $userId,
                                $exp->isNotNull('InstitutionClassSubjects.institution_subject_id'),
                            ]);
                        });*/
                        ->where([
                        'OR' => [
                            [$this->_table->aliasField('staff_id') => $userId],
                            ['InstitutionClassesSecondaryStaff.secondary_staff_id' => $userId]
                        ],
                        // 'OR' =>[
                        //     ['InstitutionSubjectStaff.staff_id' => $userId]
                        // ]
                    ]);
                        //echo "<pre>";print_r($query);die();
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
        if ($event->getResult()) {
            $roles = $event->getResult();
        }
        $myClassesPermission = $AccessControl->check(['Institutions', 'Classes', $action], $roles);
        if ($myClassesPermission) {
            return true;
        } else {
            return false;
        }
    }

    // Function to check AllClass permission is set
    // POCOR-7538: now checks the role-level "Access All Classes" flag (point 1.5)
    // instead of the removed All Classes security_function (point 1.4). The flag is
    // a blanket override, not per view/edit/add/delete/execute, so $action is kept
    // only for call-site compatibility and is otherwise unused.
    private function checkAllClassesPermission($action)
    {
        $controller = $this->_table->controller;
        $roles = [];
        $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
        if ($event->getResult()) {
            $roles = $event->getResult();
        }
        return $this->hasAccessAllClasses($roles);
    }

    // POCOR-7538: shared check for the role-level "Access All Classes" flag (point 1.5).
    private function hasAccessAllClasses($roles)
    {
        return TableRegistry::getTableLocator()->get('Security.SecurityRoles')->hasAccessAllClasses($roles);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        switch ($this->_table->action) {
            case 'view':
                // To handle the redirection out of view page if the user does not have permission
                $action = 'view';
                if (!$this->checkAllClassesPermission($action)) {
                    if ($this->checkMyClassesPermission($action)) {
                        $userId = $this->_table->Auth->user('id');
                        if (!$this->checkUserHasClassPermission($entity, $userId, true)) {
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

    // POCOR-7538 point 3.4/3.5: $includeSubjectTeachers also lets a subject teacher of
    // the class through (viewing); edit/delete stay with homeroom/secondary teachers.
    private function checkUserHasClassPermission(Entity $entity, $userId, $includeSubjectTeachers = false)
    {
        if ($includeSubjectTeachers) {
            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            return $InstitutionClasses->find()
                ->where([$InstitutionClasses->aliasField('id') => $entity->id])
                ->where($InstitutionClasses->getTeacherClassConditions($userId, $InstitutionClasses->aliasField('id')))
                ->count() > 0;
        }

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
