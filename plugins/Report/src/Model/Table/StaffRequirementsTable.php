<?php

namespace Report\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class StaffRequirementsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institutions');

        parent::initialize($config);

        $this->addBehavior('Excel', [ 'excludes' => [], 'pages' => ['index'], ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        echo '<pre>';

        $academicPeriodId       = $requestData->academic_period_id;
        $areaLevelId            = $requestData->area_level_id;
        $areaId                 = $requestData->area_education_id;
        $institutionId          = $requestData->institution_id;
        $studentPerTeacherRatio = $requestData->student_per_teacher_ratio;
        $upperTolerance         = $requestData->upper_tolerance;
        $lowerTolerance         = $requestData->lower_tolerance;

        $academicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $academicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');

        $conditions = [];
        if ($academicPeriodId) {
            if ($this->aliasField('end_date')) {
                $conditions = [
                    $this->aliasField('start_date') . ' >=' => $startDate,
                    $this->aliasField('start_date') . ' <=' => $endDate
                ];
            } else {
                $conditions = [
                    $this->aliasField('start_date') . ' >=' => $startDate,
                    $this->aliasField('end_date') . ' <=' => $endDate
                ];
            }
        }

        if ($institutionId) {
            $conditions['Institutions.id'] = $institutionId;
        }

        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }

        exit;
    }
}
