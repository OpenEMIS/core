<?php
//POCOR-9756 Starts
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;

class CounsellingsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('counsellings');
        parent::initialize($config);

        $this->belongsTo('Students', [
            'className' => 'Security.Users',
            'foreignKey' => 'student_id',
        ]);
        $this->belongsTo('Counselors', [
            'className' => 'Security.Users',
            'foreignKey' => 'counselor_id',
        ]);
        $this->belongsTo('Requesters', [
            'className' => 'Security.Users',
            'foreignKey' => 'requester_id',
        ]);
        $this->belongsTo('GuidanceTypes', [
            'className' => 'Student.GuidanceTypes',
            'foreignKey' => 'guidance_type_id',
        ]);

        $this->addBehavior('Excel', [
            'excludes' => [
                'file_name',
                'file_content',
                'counselor_id',
                'student_id',
                'guidance_type_id',
                'requester_id',
                'modified_user_id',
                'modified',
                'created_user_id',
                'created',
            ],
            'pages' => false,
            'autoFields' => false,
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.AreaList');
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
        return $attr;
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape',
        ];
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id ?? null;
        $institutionId = $requestData->institution_id ?? null;
        $areaId = $requestData->area_education_id ?? -1;

        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');

        $conditions = [];

        if (!empty($academicPeriodId)) {
            $conditions['InstitutionStudents.academic_period_id'] = $academicPeriodId;

            $currentAcademicPeriod = $AcademicPeriods->getCurrent();
            if ($academicPeriodId == $currentAcademicPeriod) {
                $currentStatus = $StudentStatuses->getIdByCode('CURRENT');
                $conditions['InstitutionStudents.student_status_id'] = $currentStatus ?: 1;
            } else {
                $statusIds = $StudentStatuses->find()
                                ->select(['id'])
                                ->where(['code IN' => ['CURRENT','PROMOTED','GRADUATED','REPEATED']])
                                ->enableHydration(false)
                                ->extract('id')
                                ->toList();
                $conditions['InstitutionStudents.student_status_id IN'] = $statusIds;
            }
        }

        if (!empty($institutionId) && $institutionId != 0) {
            $conditions['InstitutionStudents.institution_id'] = $institutionId;
        }

        if ($areaId != -1 && $areaId != '') {
            $areaIds = $this->getAreaList($areaId);
            $areaIds[] = $areaId;
            $conditions['Institutions.area_id IN'] = array_values(array_unique($areaIds));
        }

        $query
            ->select([
                'id' => $this->aliasField('id'),
                'date' => $this->aliasField('date'),
                'guidance_utilized' => $this->aliasField('guidance_utilized'),
                'description' => $this->aliasField('description'),
                'intervention' => $this->aliasField('intervention'),
                'comment' => $this->aliasField('comment'),
                'openemis_id' => 'StudentUsers.openemis_no',
                'student_name' => $query->func()->concat([
                    'StudentUsers.first_name' => 'identifier',
                    $query->newExpr("' '"),
                    'StudentUsers.last_name' => 'identifier',
                ]),
                'counsellor_name' => $query->func()->concat([
                    'Counselors.first_name' => 'identifier',
                    $query->newExpr("' '"),
                    'Counselors.last_name' => 'identifier',
                ]),
                'guidance_type' => 'GuidanceTypes.name',
                'requester_openemis_id' => 'Requesters.openemis_no',
                'requester_name' => $query->func()->concat([
                    'Requesters.first_name' => 'identifier',
                    $query->newExpr("' '"),
                    'Requesters.last_name' => 'identifier',
                ]),
            ])
            ->innerJoin(
                ['InstitutionStudents' => $InstitutionStudents->getTable()],
                [
                    'InstitutionStudents.student_id = ' . $this->aliasField('student_id'),
                ]
            )
            ->innerJoin(
                ['Institutions' => $Institutions->getTable()],
                [
                    'Institutions.id = InstitutionStudents.institution_id',
                ]
            )
            ->innerJoin(
                ['StudentUsers' => 'security_users'],
                ['StudentUsers.id = ' . $this->aliasField('student_id')]
            )
            ->leftJoin(
                ['Counselors' => 'security_users'],
                ['Counselors.id = ' . $this->aliasField('counselor_id')]
            )
            ->leftJoin(
                ['Requesters' => 'security_users'],
                ['Requesters.id = ' . $this->aliasField('requester_id')]
            )
            ->leftJoin(
                ['GuidanceTypes' => 'guidance_types'],
                ['GuidanceTypes.id = ' . $this->aliasField('guidance_type_id')]
            )
            ->where($conditions)
            ->order([
                $this->aliasField('date') => 'DESC',
                'StudentUsers.first_name' => 'ASC',
                'StudentUsers.last_name' => 'ASC',
            ])
            ->group([$this->aliasField('id')]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $extraFields = [
            [
                'key' => 'openemis_id',
                'field' => 'openemis_id',
                'type' => 'string',
                'label' => __('Student OpenEMIS ID'),
            ],
            [
                'key' => 'student_name',
                'field' => 'student_name',
                'type' => 'string',
                'label' => __('Student Name'),
            ],
            [
                'key' => 'Counsellings.date',
                'field' => 'date',
                'type' => 'date',
                'label' => __('Date'),
            ],
            [
                'key' => 'counsellor_name',
                'field' => 'counsellor_name',
                'type' => 'string',
                'label' => __('Counsellor'),
            ],
            [
                'key' => 'guidance_type',
                'field' => 'guidance_type',
                'type' => 'string',
                'label' => __('Guidance Type'),
            ],
            [
                'key' => 'requester_openemis_id',
                'field' => 'requester_openemis_id',
                'type' => 'string',
                'label' => __('Requester OpenEMIS ID'),
            ],
            [
                'key' => 'requester_name',
                'field' => 'requester_name',
                'type' => 'string',
                'label' => __('Requester Name'),
            ],
            [
                'key' => 'Counsellings.guidance_utilized',
                'field' => 'guidance_utilized',
                'type' => 'string',
                'label' => __('Guidance Utilized'),
            ],
            [
                'key' => 'Counsellings.description',
                'field' => 'description',
                'type' => 'string',
                'label' => __('Description'),
            ],
            [
                'key' => 'Counsellings.intervention',
                'field' => 'intervention',
                'type' => 'string',
                'label' => __('Intervention'),
            ],
            [
                'key' => 'Counsellings.comment',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comments'),
            ],
        ];

        $fields->exchangeArray($extraFields);
    }

    public function onExcelRenderDate(EventInterface $event, Entity $entity, $attr)
    {
        $fieldName = $attr['field'] ?? 'date';
        $field = $entity->{$fieldName} ?? $entity->{'Counsellings__' . $fieldName} ?? null;

        if (empty($field)) {
            return '';
        }

        if ($field instanceof FrozenTime || $field instanceof FrozenDate) {
            return $field->format('Y-m-d');
        }

        return (new FrozenTime($field))->format('Y-m-d');
    }
}//POCOR-9756 Ends
