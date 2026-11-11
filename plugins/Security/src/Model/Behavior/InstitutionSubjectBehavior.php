<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\I18n\Date;

class InstitutionSubjectBehavior extends Behavior
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

        if (array_key_exists('user', $options instanceof \ArrayObject ? $options->getArrayCopy() : $options) && is_array($options['user'])) { // the user object is set by RestfulComponent
            $user = $options['user'];
            if ($user['super_admin'] == 0) { // if he is not super admin
                $userId = $user['id'];

                // POCOR-7538 points 3.4/3.5/3.7: a role this user holds in the institution
                // (the SecurityAccess join) with "Access All Subjects" sees every subject;
                // otherwise only subjects they teach plus every subject of a class they are
                // homeroom/secondary teacher of - whatever their role. Replaces the old
                // security_functions LIKE matching on My Subjects/My Classes.
                $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                $query
                    ->where([
                        'OR' => [
                            'EXISTS (
                                SELECT 1
                                FROM security_roles
                                WHERE security_roles.id = SecurityAccess.security_role_id
                                AND security_roles.access_all_subjects = 1
                            )',
                            $InstitutionClasses->getTeacherSubjectConditions($userId, $this->_table->aliasField('id'))
                        ]
                    ])
                    ->group([$this->_table->aliasField('id')]); // so it doesn't show duplicate subjects
            }
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
            $userId = $this->_table->Auth->user('id');
            $AccessControl = $this->_table->AccessControl;
            $query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->_table->controller]);
        }
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $action = 'edit';
        if (!$this->checkAllSubjectsPermission($action)) {
            if ($this->checkMySubjectsPermission($action)) {
                $userId = $this->_table->Auth->user('id');
                if (!$this->userCanAccessSubject($entity->id, $userId)) {
                    $urlParams = $this->_table->url('view');
                    $event->stopPropagation();
                    $this->_table->Alert->error('security.noAccess');
                    return $this->_table->controller->redirect($urlParams);
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
        if ($event->getResult()) {
            $roles = $event->getResult();
        }
        $mySubjectsEditPermission = $AccessControl->check(['Institutions', 'Subjects', $action], $roles);
        if ($mySubjectsEditPermission) {
            return true;
        } else {
            return false;
        }
    }

    // Function to check AllSubjects permission is set
    // POCOR-7538: now checks the role-level "Access All Subjects" flag (point 1.5)
    // instead of the removed All Subjects security_function (point 1.4). The flag is
    // a blanket override, not per view/edit/add/delete/execute, so $action is kept
    // only for call-site compatibility and is otherwise unused.
    private function checkAllSubjectsPermission($action)
    {
        $controller = $this->_table->controller;
        $roles = [];
        $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
        if ($event->getResult()) {
            $roles = $event->getResult();
        }
        return $this->hasAccessAllSubjects($roles);
    }

    // POCOR-7538: shared check for the role-level "Access All Subjects" flag (point 1.5).
    // A super admin has no roles to carry the flag, so is let through explicitly - the
    // AccessControl->check() this replaced did the same implicitly.
    private function hasAccessAllSubjects($roles, $AccessControl = null)
    {
        $AccessControl = $AccessControl ?: ($this->_table->AccessControl ?? null);
        if ($AccessControl && $AccessControl->isAdmin()) {
            return true;
        }
        return TableRegistry::getTableLocator()->get('Security.SecurityRoles')->hasAccessAllSubjects($roles);
    }

    // POCOR-7538 points 3.4/3.5: a subject teacher of this subject, or the homeroom/
    // secondary teacher of a class it belongs to - the same rule as the Subjects list.
    // Previously edit was limited to assigned subject teachers, so a homeroom teacher
    // with Subjects Edit was turned away from the subjects of their own class.
    private function userCanAccessSubject($subjectId, $userId)
    {
        $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        return $InstitutionSubjects->find()
            ->where([$InstitutionSubjects->aliasField('id') => $subjectId])
            ->where(TableRegistry::getTableLocator()->get('Institution.InstitutionClasses')
                ->getTeacherSubjectConditions($userId, $InstitutionSubjects->aliasField('id')))
            ->count() > 0;
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
                        if (!$this->userCanAccessSubject($entity->id, $userId)) {
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
        if (isset($options['accessControl'])) {
            $AccessControl = $options['accessControl'];
            $userId = $options['userId'];
            $roles = [];
            if (isset($options['controller'])) {
                $controller = $options['controller'];
                $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
                if (is_array($event->getResult())) {
                    $roles = $event->getResult();
                }
            }

            // POCOR-7538 points 2.2/3.4/3.5/3.7: "Access All Subjects" (role-level flag,
            // point 1.5) shows every subject; otherwise subjects the user teaches plus every
            // subject of a class they are homeroom/secondary teacher of - whatever their
            // role. Page CRUD is gated by the Subjects permission (SecurityAuthorize).
            if (!$this->hasAccessAllSubjects($roles, $AccessControl)) {
                $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                $query->where($InstitutionClasses->getTeacherSubjectConditions($userId, $this->_table->aliasField('id')));
            }
        }
        return $query;
    }
}
