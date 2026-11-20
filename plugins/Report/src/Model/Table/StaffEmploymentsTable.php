<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\FrozenDate;
use App\Model\Table\AppTable;

/**
 * POCOR-9875 Reports > Staff > Staff Employments Report
 * Lists the employment history (Staff > Professional > Employments) of staff
 * in the selected area / institution(s) for the selected academic period.
 */
class StaffEmploymentsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_employments');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('Industries', ['className' => 'FieldOption.Industries', 'foreignKey' => 'industry_id']);

        $this->addBehavior('Excel', [
            'excludes' => [],
            'autoFields' => false,
            'auto_contain' => false,
            'pages' => false,
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $userId = $requestData->user_id;
        $superAdmin = $requestData->super_admin;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;

        $conditions = [];

        // Institution filter (multi-select; 0 = All Institutions)
        $filterInstitutionIds = [];
        if (is_object($institutionId) && isset($institutionId->_ids)) {
            $filterInstitutionIds = array_values(array_filter((array)$institutionId->_ids, function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif (is_array($institutionId) && isset($institutionId['_ids'])) {
            $filterInstitutionIds = array_values(array_filter((array)$institutionId['_ids'], function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif (!empty($institutionId) && $institutionId > 0 && !is_array($institutionId)) {
            $filterInstitutionIds = [(int)$institutionId];
        }
        if (!empty($filterInstitutionIds)) {
            $conditions['InstitutionStaff.institution_id IN'] = $filterInstitutionIds;
        }

        // Area filter (includes child areas)
        if (!empty($areaId) && $areaId != -1) {
            $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
            $areaIds = $Areas->find()
                ->select([$Areas->aliasField('id')])
                ->where([$Areas->aliasField('parent_id') => $areaId])
                ->all()
                ->extract('id')
                ->toList();
            $areaIds[] = $areaId;
            $conditions['Institutions.area_id IN'] = $areaIds;
        }

        // Staff assigned to the institution during the selected academic period
        if (!empty($academicPeriodId)) {
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $periodEntity = $AcademicPeriods->get($academicPeriodId);
            $startDate = $periodEntity->start_date->format('Y-m-d');
            $endDate = $periodEntity->end_date->format('Y-m-d');
            $conditions[] = [
                'InstitutionStaff.start_date <=' => $endDate,
                'OR' => [
                    'InstitutionStaff.end_date IS NULL',
                    'InstitutionStaff.end_date >=' => $startDate
                ]
            ];
        }

        $query
            ->select([
                'id' => $this->aliasField('id'),
                'security_user_id' => $this->aliasField('security_user_id'),
                'openemis_no' => 'Users.openemis_no',
                'first_name' => 'Users.first_name',
                'middle_name' => 'Users.middle_name',
                'third_name' => 'Users.third_name',
                'last_name' => 'Users.last_name',
                'date_from' => $this->aliasField('date_from'),
                'date_to' => $this->aliasField('date_to'),
                'organisation' => $this->aliasField('organisation'),
                'position' => $this->aliasField('position'),
                'industry_name' => 'Industries.name'
            ])
            ->innerJoin(
                ['Users' => 'security_users'],
                ['Users.id = ' . $this->aliasField('security_user_id')]
            )
            ->leftJoin(
                ['Industries' => 'industries'],
                ['Industries.id = ' . $this->aliasField('industry_id')]
            )
            ->innerJoin(
                ['InstitutionStaff' => 'institution_staff'],
                ['InstitutionStaff.staff_id = ' . $this->aliasField('security_user_id')]
            )
            ->innerJoin(
                ['Institutions' => 'institutions'],
                ['Institutions.id = InstitutionStaff.institution_id']
            )
            ->where($conditions)
            // a staff member may have several institution_staff records - list each employment once
            ->distinct()
            ->order([
                'Users.openemis_no' => 'ASC',
                $this->aliasField('date_from') => 'DESC'
            ]);

        if (!$superAdmin) {
            $query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => 'Institutions.id']);
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Name')
        ];
        $newFields[] = [
            'key' => 'StaffEmployments.date_from',
            'field' => 'date_from',
            'type' => 'string',
            'label' => __('Date From')
        ];
        $newFields[] = [
            'key' => 'StaffEmployments.date_to',
            'field' => 'date_to',
            'type' => 'string',
            'label' => __('Date To')
        ];
        $newFields[] = [
            'key' => 'StaffEmployments.organisation',
            'field' => 'organisation',
            'type' => 'string',
            'label' => __('Organisation')
        ];
        $newFields[] = [
            'key' => 'StaffEmployments.position',
            'field' => 'position',
            'type' => 'string',
            'label' => __('Position')
        ];
        $newFields[] = [
            'key' => 'Industries.name',
            'field' => 'industry_name',
            'type' => 'string',
            'label' => __('Industry')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffName(EventInterface $event, Entity $entity)
    {
        $nameParts = array_filter([
            $entity->first_name,
            $entity->middle_name,
            $entity->third_name,
            $entity->last_name
        ], function ($part) {
            return $part !== null && trim($part) !== '';
        });
        return implode(' ', $nameParts);
    }

    public function onExcelGetDateFrom(EventInterface $event, Entity $entity)
    {
        return $this->formatEmploymentDate($entity->date_from);
    }

    public function onExcelGetDateTo(EventInterface $event, Entity $entity)
    {
        return $this->formatEmploymentDate($entity->date_to);
    }

    private function formatEmploymentDate($date)
    {
        if (empty($date)) {
            return '';
        }
        if (!is_object($date)) {
            try {
                $date = new FrozenDate($date);
            } catch (\Exception $e) {
                return (string)$date;
            }
        }
        return $this->formatDate($date);
    }
}
