<?php
namespace Student\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;

class CounsellingsTable extends ControllerActionTable
{
    const ASSIGNED = 1;

    public function initialize(array $config): void
    {
        $this->setTable('counsellings');
        parent::initialize($config);

        $this->belongsTo('GuidanceTypes', ['className' => 'Student.GuidanceTypes', 'foreign_key' => 'guidance_type_id']);
        $this->belongsTo('Counselors', ['className' => 'Security.Users', 'foreign_key' => 'counselor_id']);
        $this->belongsTo('Requesters', ['className' => 'Security.Users', 'foreign_key' => 'requester_id']);
        /*$this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);*/
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['Counsellings'=>['id']]
        ]);
        $this->addBehavior('OpenEmis.Autocomplete'); //POCOR-9523
        $this->addBehavior('User.AdvancedNameSearch'); //POCOR-9523
        $this->Users = TableRegistry::getTableLocator()->get('Security.Users');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete'; //POCOR-9523
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(EventInterface $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator->allowEmpty('file_content');
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    public function getGuidanceTypesOptions($institutionId)
    {
        // should be auto, if auto the reorder and visible not working
        $guidanceTypesOptions = $this->GuidanceTypes
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $guidanceTypesOptions;
    }

    public function getCounselorOptions($institutionId)
    {
        // get the staff that assigned from the institution from security user
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');

        $counselorOptions = $this->Counselors
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->innerJoin(
                    [$InstitutionStaff->getAlias() => $InstitutionStaff->getTable()],
                    [
                        $InstitutionStaff->aliasField('staff_id = ') . $this->Counselors->aliasField('id'),
                        $InstitutionStaff->aliasField('institution_id') => $institutionId,
                        $InstitutionStaff->aliasField('staff_status_id') => self::ASSIGNED
                    ]
                )
            ->order([
                $this->Counselors->aliasField('first_name'),
                $this->Counselors->aliasField('last_name')
            ])
            ->toArray();
   
        return $counselorOptions;
    }

    public function indexBeforeAction(EventInterface $event) {
        
        $this->field('date');
        $this->field('description');
        $this->field('intervention');
        $this->field('counselor_id');
        $this->field('guidance_type_id');
        $this->field('requester_id');
        $this->field('guidance_utilized',['visible' => false]);
        $this->field('file_name',['visible' => false]);
        $this->field('file_content',['visible' => false]);
        $this->field('comment',['visible' => false]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
        ->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        /*$academicPeriodOptions = $this->AcademicPeriods->getYearList();
        
        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);*/
        $queryString = $this->getQueryString();
        $institutionId = $queryString['institution_id'];
        $studentId = $queryString['student_id'];

        $counselorOptions = $this->getCounselorOptions($institutionId);
        
        $this->fields['counselor_id']['type'] = 'select';
        $this->fields['counselor_id']['options'] = $counselorOptions;
        $this->fields['counselor_id']['onChangeReload'] = true;
        $this->field('counselor_id', ['attr' => ['label' => __('Counselor')]]);

        $this->fields['guidance_type_id']['type'] = 'select';
        $this->field('guidance_type_id', ['attr' => ['label' => __('Guidance Type')]]);
        $this->field('requester_id', ['visible' => true]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment'), 'required' => true], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('student_id', ['type' => 'hidden', 'value'=> $studentId]);

        $this->setFieldOrder(['date', 'counselor_id', 'guidance_type_id', 'requester_id', 'guidance_utilized', 'description', 'intervention', 'comment', 'file_name', 'file_content']);
    }

     
   //POCOR-9523
   public function onUpdateFieldRequesterId(EventInterface $event,array $attr,$action,ServerRequest $request)
    {
        if (in_array($action, ['add', 'edit'])) {

            $attr['type'] = 'autocomplete';
            $attr['target'] = [
                'key'  => 'requester_id',
                'name' => $this->aliasField('requester_id')
            ];

            $attr['attr'] = [
                'placeholder' => __('OpenEMIS ID, Identity Number or Name')
            ];

            $attr['url'] = [
                'controller' => 'Students',
                'action'     => 'Counsellings',
                'ajaxUserAutocomplete'
            ];

            /*EDIT mode */
            if ($action === 'edit') {
                $queryString = $this->getQueryString();

                if (!empty($queryString['id'])) {
                    $record = $this->find()
                        ->select(['requester_id'])
                        ->where(['id' => $queryString['id']])
                        ->first();

                    if (!empty($record->requester_id)) {
                        try {
                            $Users = TableRegistry::get('User.Users');
                            $user  = $Users->get($record->requester_id);
                            $attr['attr']['value'] =
                                $user->openemis_no . ' - ' . $user->name;

                        } catch (\Exception $e) {
                            // fail
                        }
                    }
                }
            }
        }

        return $attr;
    }

    //POCOR-9523
    public function ajaxUserAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {

            $term = trim($this->request->getQuery('term'));

            $queryString = $this->getQueryString();
            $studentId   = $queryString['student_id'] ?? null;

            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $this->Users
                ->find()
                ->select([
                    $this->Users->aliasField('openemis_no'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name'),
                    $this->Users->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->getAlias() => $UserIdentitiesTable->getTable()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $this->Users->aliasField('id')
                    ]
                )
                ->where([
                    $this->Users->aliasField('status IS') => 1,
                ])
                ->andWhere([
                    'OR' => [
                        $this->Users->aliasField('is_student') => 1,
                        $this->Users->aliasField('is_staff') => 1,
                        $this->Users->aliasField('is_guardian') => 1
                    ]
                ])
                ->group([$this->Users->aliasField('id')])
                ->limit(100);
            if (!empty($studentId)) {
                $query->where([
                    $this->Users->aliasField('id !=') => $studentId
                ]);
            }
            if (!empty($term)) {
                $query = $this->addSearchConditions(
                    $query,
                    [
                        'alias' => 'Users',
                        'searchTerm' => $term,
                        'OR' => [
                            '`Identities`.number LIKE ' => $term . '%'
                        ]
                    ]
                );
            }
            $data = [];
            foreach ($query->all() as $obj) {
                $data[] = [
                    'label' => $obj->openemis_no . ' - ' . $obj->name,
                    'value' => $obj->id
                ];
            }
            echo json_encode($data);
            die;
        }
    }

    /**
     * Retrieve requester options for a given institution.
     * 
     * This function returns a combined list of Users as requesters in the system.It includes-
     *  - Students (linked to the institution_students)
     *  - Staff (assigned to the institution_staff)
     *  - Guardians (related to students. student_guardians)
     * @param int $institutionId  The institution ID used to filter students, staff, and guardians.
     * @return array List of requester options formatted for dropdowns.
     * 
     */
    /*public function getRequesterOptionsbkp($institutionId)
    {
        $UserData = TableRegistry::get('User.Users');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = (int) $AcademicPeriods->getCurrent();

        $subQuery = "
            SELECT ist.student_id AS user_id
            FROM institution_students ist
            INNER JOIN academic_periods ap
                ON ap.id = ist.academic_period_id
            WHERE ap.id = {$academicPeriodId}
              AND ist.institution_id = {$institutionId}
              AND (
                    (CURRENT_DATE BETWEEN ap.start_date AND ap.end_date
                     AND ist.student_status_id = 1)
                    OR
                    (NOT (CURRENT_DATE BETWEEN ap.start_date AND ap.end_date)
                     AND ist.student_status_id IN (1, 7, 6, 8))
                  )
            GROUP BY ist.student_id
            UNION ALL
            SELECT ins.staff_id AS user_id
            FROM institution_staff ins
            INNER JOIN academic_periods ap
                ON (
                        (ins.end_date IS NOT NULL AND ins.start_date <= ap.start_date AND ins.end_date >= ap.start_date)
                    OR  (ins.end_date IS NOT NULL AND ins.start_date <= ap.end_date AND ins.end_date >= ap.end_date)
                    OR  (ins.end_date IS NOT NULL AND ins.start_date >= ap.start_date AND ins.end_date <= ap.end_date)
                    OR  (ins.end_date IS NULL AND ins.start_date <= ap.end_date)
                   )
            WHERE ap.id = {$academicPeriodId}
              AND ins.institution_id = {$institutionId}
              AND ins.staff_status_id = 1
            GROUP BY ins.staff_id
            UNION ALL
            SELECT sg.guardian_id AS user_id
            FROM student_guardians sg
            INNER JOIN institution_students ist
                  ON ist.student_id = sg.student_id
                 AND ist.institution_id = {$institutionId}
                 AND ist.academic_period_id = {$academicPeriodId}
            INNER JOIN security_users su
                  ON su.id = sg.guardian_id
            WHERE su.is_guardian = 1
            GROUP BY sg.guardian_id
        ";

        $join = [
            [
                'table' => "({$subQuery})",
                'alias' => 'subq',
                'type' => 'INNER',
                'conditions' => "subq.user_id = Users.id"
            ]
        ];

        $rows = $UserData->find()
        ->select([
            'id' => 'Users.id',
            'openemis_no' => 'Users.openemis_no',
            'first_name' => 'Users.first_name',
            'middle_name' => 'Users.middle_name',
            'third_name' => 'Users.third_name',
            'last_name' => 'Users.last_name'
        ])
        ->join($join)
        ->enableHydration(false)
        ->toArray();

        $data = [];
        foreach ($rows as $r) {
            // Combine full name 
            $fullName = trim(
                $r['first_name'] . ' ' .
                ($r['middle_name'] ?? '') . ' ' .
                ($r['third_name'] ?? '') . ' ' .
                ($r['last_name'] ?? '')
            );

            $fullName = preg_replace('/\s+/', ' ', $fullName);
            $data[$r['id']] = $r['openemis_no'] . ' - ' . $fullName;
        }

        return $data;
    }*/
   


}
