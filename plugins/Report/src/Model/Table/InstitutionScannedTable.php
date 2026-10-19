<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

// POCOR-9840: Reports > Institutions > Scanned - one row per QR code scan (institution_scanned)
// within the selected date range. A scan only stores the OpenEMIS ID, so the institution and user
// type are resolved from the scanned user's student enrolment / staff assignment that was active
// on the scan date. Scans of users with no such enrolment (e.g. guardians) have no institution and
// are only included when no specific area/institution is selected.
class InstitutionScannedTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_scanned');
        parent::initialize($config);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false,
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
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

        $areaId = $requestData->area_education_id ?? '';
        $filterInstitutionIds = $this->parseInstitutionIds($requestData->institution_id ?? null);
        // AppTable::parseReportDate() reads the system date format; Y-m-d strings from here on
        $startDate = $this->parseReportDate($requestData->report_start_date ?? null)?->format('Y-m-d');
        $endDate = $this->parseReportDate($requestData->report_end_date ?? null)?->format('Y-m-d');

        $conditions = [];
        if ($startDate) {
            $conditions[$this->aliasField('datetime') . ' >='] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $conditions[$this->aliasField('datetime') . ' <='] = $endDate . ' 23:59:59';
        }
        if (!empty($filterInstitutionIds)) {
            $conditions['ScannedInstitutions.id IN'] = $filterInstitutionIds;
        }
        if ($areaId !== '' && $areaId !== null && $areaId != -1) {
            $areaIds = $this->getChildren($areaId, []);
            $areaIds[] = $areaId;
            $conditions['ScannedInstitutions.area_id IN'] = $areaIds;
        }

        $scanInstitutionSql = $this->buildScanInstitutionSQL($startDate, $endDate);
        $query
            ->select([
                'scan_id' => $this->aliasField('id'),
                'openemis_no' => $this->aliasField('openemis_no'),
                'scan_datetime' => $this->aliasField('datetime'),
                'latitude' => $this->aliasField('latitude'),
                'longitude' => $this->aliasField('longitude'),
                'location' => $this->aliasField('location'),
                'access' => $this->aliasField('access'),
                'first_name' => 'Users.first_name',
                'middle_name' => 'Users.middle_name',
                'third_name' => 'Users.third_name',
                'last_name' => 'Users.last_name',
                'institution_code' => 'ScannedInstitutions.code',
                'institution_name' => 'ScannedInstitutions.name',
                'user_type' => "(CASE
                    WHEN ScanInstitutions.user_type IS NOT NULL THEN ScanInstitutions.user_type
                    WHEN Users.is_student = 1 THEN 'Student'
                    WHEN Users.is_staff = 1 THEN 'Staff'
                    WHEN Users.is_guardian = 1 THEN 'Guardian'
                    ELSE '' END)",
            ])
            ->leftJoin(['Users' => 'security_users'], ['Users.openemis_no = ' . $this->aliasField('openemis_no')])
            ->join([
                'ScanInstitutions' => [
                    'type' => 'LEFT',
                    'table' => "({$scanInstitutionSql})",
                    'conditions' => ['ScanInstitutions.scan_id = ' . $this->aliasField('id')],
                ],
            ])
            ->leftJoin(['ScannedInstitutions' => 'institutions'], ['ScannedInstitutions.id = ScanInstitutions.institution_id'])
            ->where($conditions)
            ->order([
                $this->aliasField('datetime') => 'ASC',
                $this->aliasField('id') => 'ASC',
                'ScannedInstitutions.code' => 'ASC',
            ]);

        if (empty($requestData->super_admin)) {
            $query->find('byAccess', [
                'user_id' => $requestData->user_id,
                'institution_field_alias' => 'ScannedInstitutions.id',
            ]);
        }

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row['name'] = trim(preg_replace('/\s+/', ' ', "{$row['first_name']} {$row['middle_name']} {$row['third_name']} {$row['last_name']}"));
                // datetime is hydrated as a Chronos object whose string cast drops the seconds
                $scanDateTime = $row['scan_datetime'];
                if (!empty($scanDateTime) && !($scanDateTime instanceof \DateTimeInterface)) {
                    $scanDateTime = new \DateTime((string)$scanDateTime);
                }
                $row['scan_date'] = !empty($scanDateTime) ? $scanDateTime->format('Y-m-d') : '';
                $row['scan_time'] = !empty($scanDateTime) ? $scanDateTime->format('H:i:s') : '';
                $userTypes = array_filter(explode(',', (string)$row['user_type']));
                $row['user_type'] = implode(', ', array_map(fn($type) => __(trim($type)), $userTypes));
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = ['key' => 'openemis_no', 'field' => 'openemis_no', 'type' => 'string', 'label' => __('QR Code (OpenEMIS ID)')];
        $newFields[] = ['key' => 'latitude', 'field' => 'latitude', 'type' => 'string', 'label' => __('Latitude')];
        $newFields[] = ['key' => 'longitude', 'field' => 'longitude', 'type' => 'string', 'label' => __('Longitude')];
        $newFields[] = ['key' => 'scan_date', 'field' => 'scan_date', 'type' => 'string', 'label' => __('Date')];
        $newFields[] = ['key' => 'scan_time', 'field' => 'scan_time', 'type' => 'string', 'label' => __('Time')];
        $newFields[] = ['key' => 'institution_code', 'field' => 'institution_code', 'type' => 'string', 'label' => __('Institution Code')];
        $newFields[] = ['key' => 'user_type', 'field' => 'user_type', 'type' => 'string', 'label' => __('User Type')];
        $newFields[] = ['key' => 'location', 'field' => 'location', 'type' => 'string', 'label' => __('Location')];
        $newFields[] = ['key' => 'access', 'field' => 'access', 'type' => 'string', 'label' => __('Access Option')];

        $fields->exchangeArray($newFields);
    }

    /**
     * One row per (scan, institution) where the scanned user was an enrolled student or an
     * assigned staff member on the scan date. Grouped here (not in the outer query) so a user with
     * overlapping enrolment rows at the same institution doesn't duplicate the scan, and so the
     * outer query stays ONLY_FULL_GROUP_BY safe and can be paged by the Excel behavior.
     */
    private function buildScanInstitutionSQL($startDate, $endDate)
    {
        $dateConditionSQL = '';
        if ($startDate) {
            $dateConditionSQL .= " AND s.datetime >= '{$startDate} 00:00:00'";
        }
        if ($endDate) {
            $dateConditionSQL .= " AND s.datetime <= '{$endDate} 23:59:59'";
        }

        return "SELECT s.id AS scan_id
                    ,ui.institution_id
                    ,GROUP_CONCAT(DISTINCT ui.user_type ORDER BY ui.user_type SEPARATOR ',') AS user_type
                FROM institution_scanned s
                INNER JOIN security_users u ON u.openemis_no = s.openemis_no
                INNER JOIN (
                    SELECT student_id AS security_user_id, institution_id, 'Student' AS user_type, start_date, end_date
                    FROM institution_students
                    UNION ALL
                    SELECT staff_id AS security_user_id, institution_id, 'Staff' AS user_type, start_date, end_date
                    FROM institution_staff
                ) ui ON ui.security_user_id = u.id
                    AND DATE(s.datetime) >= ui.start_date
                    AND (ui.end_date IS NULL OR DATE(s.datetime) <= ui.end_date)
                WHERE 1 = 1 {$dateConditionSQL}
                GROUP BY s.id, ui.institution_id";
    }


    private function parseInstitutionIds($institutionId)
    {
        $ids = [];
        if (is_object($institutionId) && isset($institutionId->_ids)) {
            $ids = (array) $institutionId->_ids;
        } elseif (is_array($institutionId) && isset($institutionId['_ids'])) {
            $ids = (array) $institutionId['_ids'];
        } elseif (!empty($institutionId)) {
            $ids = [$institutionId];
        }

        return array_values(array_map('intval', array_filter($ids, function ($id) {
            return $id !== '' && $id !== null && (int) $id > 0;
        })));
    }

    public function getChildren($id, $idArray)
    {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $node = $Areas->find()
            ->select(['lft', 'rght'])
            ->where([$Areas->aliasField('id') => $id])
            ->first();

        if (empty($node)) {
            return $idArray;
        }

        $descendants = $Areas->find()
            ->select(['id'])
            ->where([
                $Areas->aliasField('lft') . ' >' => $node->lft,
                $Areas->aliasField('rght') . ' <' => $node->rght,
            ])
            ->enableHydration(false)
            ->toArray();

        return array_merge($idArray, array_column($descendants, 'id'));
    }
}
