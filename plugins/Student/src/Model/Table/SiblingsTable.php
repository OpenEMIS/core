<?php
namespace Student\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;

//POCOR-4259
class SiblingsTable extends ControllerActionTable
{
    private $siblingEnrolments = [];
    private $siblingEnrolmentsLoaded = [];

    public function initialize(array $config): void
    {
        $this->setTable('student_guardians');
        parent::initialize($config);
        $this->belongsTo('StudentUser', ['className' => 'Institution.StudentUser', 'foreignKey' => 'student_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'guardian_id']);
        $this->belongsTo('InstitutionStudents', ['className' => 'Institution.InstitutionStudents', 'foreignKey' => 'student_id']);
        $this->belongsTo('GuardianRelations', ['className' => 'Student.GuardianRelations', 'foreignKey' => 'guardian_relation_id']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Siblings' => ['id']],
            'implementedMethods' => [
                'setUserTabElements' => 'setUserTabElements',
            ],
        ]);
        $this->addBehavior('ControllerAction.Image');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        // Run after InstitutionTabBehavior (priority 1001) which overwrites view URLs
        $events['Model.custom.onUpdateActionButtons'] = [
            'callable' => 'onUpdateActionButtonsAfterTab',
            'priority' => 1102
        ];
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 1200];
        return $events;
    }

    /**
     * Ensure student / institution context is present for tabs and back button.
     * View URL must keep student_guardians.id for the record, but student_id must
     * remain the ORIGINAL student (not the sibling), or other tabs 404.
     */
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->resolveNavigationParams();
        if (empty($params['student_id'])) {
            return;
        }

        // Keep navigation context available for InstitutionTab back button / tabs
        if ($this->action === 'view' || $this->action === 'index') {
            if (isset($extra['toolbarButtons']) && $extra['toolbarButtons'] instanceof ArrayObject
                && $extra['toolbarButtons']->offsetExists('back')) {
                $backParams = $params;
                // Index/back should not carry the sibling guardian-row id as page id
                unset($backParams['id']);
                $extra['toolbarButtons']['back']['url'][0] = 'index';
                $extra['toolbarButtons']['back']['url'][1] = $this->paramsEncode($backParams);
            }
        }
    }

    public function indexAfterAction(EventInterface $event, $data)
    {
        $this->setupTabElements();
        $this->field('photo_content', ['visible' => true, 'type' => 'image']);
        $this->field('openemis_no');
        $this->field('student_name');
        $this->field('institution', ['visible' => true]);
        $this->field('education_grade');

        $this->field('guardian_id', ['visible' => false]);
        $this->field('guardian_relation_id', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);
    }

    private function setupTabElements($entity = null)
    {
        $options = [
            'userRole' => 'Student',
            'action' => $this->action,
            'id' => 0,
            'userId' => 0
        ];
        $tabElements = $this->setUserTabElements($options);

        // Rebuild tab query strings with a reliable student / institution context
        $navParams = $this->resolveNavigationParams();
        if (!empty($navParams['student_id'])) {
            $tabStudentParams = $navParams;
            unset($tabStudentParams['id']);
            $tabStudentParams['user_id'] = $navParams['student_id'];
            $tabStudentParams['security_user_id'] = $navParams['student_id'];

            $withId = $tabStudentParams;
            $withId['id'] = $navParams['student_id'];
            $encodedWithId = $this->paramsEncode($withId);
            $encodedWithoutId = $this->paramsEncode($tabStudentParams);

            foreach ($tabElements as $key => $value) {
                if (!isset($tabElements[$key]['url'])) {
                    continue;
                }
                if ($key === 'StudentUser' || $key === 'StudentAccount') {
                    $tabElements[$key]['url'][1] = $encodedWithId;
                } else {
                    $tabElements[$key]['url'][1] = $encodedWithoutId;
                }
            }
        }

        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->where([], [], true);
        $navParams = $this->resolveNavigationParams();
        $userId = !empty($navParams['student_id']) ? $navParams['student_id'] : null;

        $record = null;
        if (!empty($userId)) {
            $record = $this->find()
                ->where([
                    $this->aliasField('student_id') => $userId
                ])
                ->first();
        }

        if (empty($record)) {
            $query->where(['1 = 0']);
            return $query;
        }else{
            $guardianId = $record->guardian_id;
            $query->select([
                    'sibling_id' => 'SecurityUsers.id',
                    'student_id' => $this->aliasField('student_id'),
                    'id' => $this->aliasField('id'),
                    'guardian_id' => $this->aliasField('guardian_id'),
                    'openemis_no' => 'SecurityUsers.openemis_no',
                    'student_name' => "CONCAT(SecurityUsers.first_name, ' ', SecurityUsers.last_name)",
                    'photo_content' => 'SecurityUsers.photo_content',
                    'photo_name' => 'SecurityUsers.photo_name'
                ])
                ->innerJoin(
                    ['SecurityUsers' => 'security_users'],
                    ['SecurityUsers.id = ' . $this->aliasField('student_id')]
                )
                ->where([
                    $this->aliasField('guardian_id') => $guardianId,
                    $this->aliasField('student_id !=') => $userId
                ]);

            // Batch-load for all siblings once
            $siblingIds = $this->find()
                ->select([$this->aliasField('student_id')])
                ->where([
                    $this->aliasField('guardian_id') => $guardianId,
                    $this->aliasField('student_id !=') => $userId
                ])
                ->all()
                ->extract('student_id')
                ->toList();
            $this->loadSiblingEnrolments($siblingIds);
        }
    }


    /**
     * Batch-load latest Institution.Students rows (with EducationGrades + Institutions)
     * for the given sibling student_ids. Keeps the highest academic_period_id per student.
     */
    private function loadSiblingEnrolments(array $siblingIds)
    {
        $siblingIds = array_values(array_unique(array_filter(array_map('intval', $siblingIds))));
        $loaded = array_map('intval', $this->siblingEnrolmentsLoaded);
        $toLoad = array_values(array_diff($siblingIds, $loaded));
        if (empty($toLoad)) {
            return;
        }

        $studentTable = TableRegistry::getTableLocator()->get('Institution.Students');
        $records = $studentTable->find()
            ->contain(['EducationGrades', 'Institutions'])
            ->where(['Students.student_id IN' => $toLoad])
            ->order(['Students.academic_period_id' => 'DESC'])
            ->all();

        foreach ($records as $record) {
            $sid = (int)$record->student_id;
            // First row wins (DESC academic_period_id = latest enrolment)
            if (!isset($this->siblingEnrolments[$sid])) {
                $this->siblingEnrolments[$sid] = $record;
            }
        }

        $this->siblingEnrolmentsLoaded = array_values(array_unique(array_merge($loaded, $toLoad)));
    }

    private function getSiblingEnrolment(Entity $entity)
    {
        $siblingId = !empty($entity->sibling_id) ? $entity->sibling_id : $entity->student_id;
        if ($siblingId === null || $siblingId === '') {
            return null;
        }
        $siblingId = (int)$siblingId;

        if (!in_array($siblingId, array_map('intval', $this->siblingEnrolmentsLoaded), true)) {
            $this->loadSiblingEnrolments([$siblingId]);
        }

        return isset($this->siblingEnrolments[$siblingId]) ? $this->siblingEnrolments[$siblingId] : null;
    }

    public function onGetEducationGrade(EventInterface $event, Entity $entity)
    {
        $data = $this->getSiblingEnrolment($entity);

        return $data && $data->education_grade ? $data->education_grade->name : '';
    }

    public function onGetInstitution(EventInterface $event, Entity $entity)
    {
        $data = $this->getSiblingEnrolment($entity);

        return $data && $data->institution ? $data->institution->name : '';
    }

    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        if (!empty($entity->openemis_no)) {
            return $entity->openemis_no;
        }
        if (!empty($entity->student_user) && !empty($entity->student_user->openemis_no)) {
            return $entity->student_user->openemis_no;
        }
        return '';
    }

    public function onGetStudentName(EventInterface $event, Entity $entity)
    {
        if (!empty($entity->student_name)) {
            return $entity->student_name;
        }
        if (!empty($entity->student_user)) {
            return trim($entity->student_user->first_name . ' ' . $entity->student_user->last_name);
        }
        return '';
    }

    public function onGetPhotoContent(EventInterface $event, Entity $entity)
    {
        $fileContent = $entity->photo_content;
        if (empty($fileContent) && !empty($entity->student_user)) {
            $fileContent = $entity->student_user->photo_content;
        }
        if (!empty($fileContent)) {
            return base64_encode(stream_get_contents($fileContent));
        }
        return '';
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['StudentUser', 'Users', 'GuardianRelations']);

        // Batch-load enrolment for the sibling being viewed (same path as index onGet*)
        $navParams = $this->resolveNavigationParams();
        $siblingId = !empty($navParams['sibling_id']) ? $navParams['sibling_id'] : null;
        if (empty($siblingId)) {
            $queryString = $this->getQueryString();
            if (!empty($queryString['id'])) {
                $record = $this->find()
                    ->select([$this->aliasField('student_id')])
                    ->where([$this->aliasField('id') => $queryString['id']])
                    ->first();
                if ($record) {
                    $siblingId = $record->student_id;
                }
            }
        }
        if (!empty($siblingId)) {
            $this->loadSiblingEnrolments([$siblingId]);
        }

        return $query;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        return $this->applySiblingViewButton($entity, $buttons);
    }

    /**
     * Re-apply after InstitutionTabBehavior::fixActionButtons overwrites the URL.
     */
    public function onUpdateActionButtonsAfterTab(EventInterface $event, Entity $entity, array $buttons)
    {
        return $this->applySiblingViewButton($entity, $buttons);
    }

    private function applySiblingViewButton(Entity $entity, array $buttons)
    {
        if (!isset($buttons['view'])) {
            return $buttons;
        }

        $params = $this->resolveNavigationParams($entity->id);
        if (!empty($entity->sibling_id)) {
            $params['sibling_id'] = $entity->sibling_id;
        }

        $encodedParams = $this->paramsEncode($params);

        $buttons['view']['label'] = '<i class="fa fa-eye"></i>' . __('View');
        $buttons['view']['url']['plugin'] = 'Student';
        $buttons['view']['url']['controller'] = 'Students';
        $buttons['view']['url']['action'] = 'Siblings';
        $buttons['view']['url'][0] = 'view';
        $buttons['view']['url'][1] = $encodedParams;

        unset($buttons['view']['url'][2], $buttons['view']['url']['?'], $buttons['view']['url']['queryString']);

        return $buttons;
    }

    /**
     * Build navigation params: keep student_guardians.id for view record lookup,
     * but always use the ORIGINAL student as student_id / security_user_id.
     */
    private function resolveNavigationParams($recordId = null)
    {
        $queryString = $this->getQueryString();
        if (!is_array($queryString)) {
            $queryString = [];
        }

        $session = $this->request->getSession();

        $studentId = null;
        if (!empty($queryString['student_id'])) {
            $studentId = $queryString['student_id'];
        } elseif (!empty($queryString['security_user_id'])) {
            $studentId = $queryString['security_user_id'];
        } elseif (!empty($queryString['user_id'])) {
            $studentId = $queryString['user_id'];
        } elseif (method_exists($this, 'getStudentID')) {
            $studentId = $this->getStudentID();
        }
        if (empty($studentId) && $session->check('Student.Students.id')) {
            $studentId = $session->read('Student.Students.id');
        }

        $institutionId = null;
        if (!empty($queryString['institution_id'])) {
            $institutionId = $queryString['institution_id'];
        } elseif (method_exists($this, 'getInstitutionID')) {
            $institutionId = $this->getInstitutionID();
        }
        if (empty($institutionId) && $session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }

        $params = [];
        if ($recordId !== null) {
            $params['id'] = $recordId;
        } elseif (!empty($queryString['id']) && $this->action === 'view') {
            // Keep current guardian-row id only while viewing a sibling record
            $params['id'] = $queryString['id'];
        }

        if (!empty($studentId)) {
            $params['student_id'] = $studentId;
            $params['user_id'] = $studentId;
            $params['security_user_id'] = $studentId;
        }
        if (!empty($institutionId)) {
            $params['institution_id'] = $institutionId;
        }
        if (!empty($queryString['institution_student_id'])) {
            $params['institution_student_id'] = $queryString['institution_student_id'];
        }
        if (!empty($queryString['sibling_id']) && $this->action === 'view') {
            $params['sibling_id'] = $queryString['sibling_id'];
        }

        return $params;
    }

    public function viewAfterAction(EventInterface $event, $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);

        $this->field('photo_content', ['visible' => true, 'type' => 'image']);
        $this->field('openemis_no');
        $this->field('student_name');
        $this->field('institution', ['visible' => true]);
        $this->field('education_grade');
        $this->field('guardian_id', ['visible' => true]);
        $this->field('guardian_relation_id', ['visible' => true]);
        $this->field('student_id', ['visible' => false]);

        $this->setFieldOrder([
            'photo_content',
            'openemis_no',
            'student_name',
            'institution',
            'education_grade',
            'guardian_id',
            'guardian_relation_id'
        ]);
    }
}
