<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentsGraduationSummaryTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_students');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $educationProgrammeId = $requestData->education_programme_id;
        $areaId = $requestData->area_education_id;

        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');

        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($areaId, $areaIds);
            $selectedArea1[] = $areaId;
            if (!empty($allgetArea)) {
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            } else {
                $allselectedAreas = $selectedArea1;
            }
            $conditions[$Institutions->aliasField('area_id').' IN'] = $allselectedAreas;
        }
        $institutionsList = $Institutions
            ->find()
            ->select([
                $Institutions->aliasField('id')
            ])
            ->where($conditions)
            ->toArray();

        $institutionIds = [];
        foreach ($institutionsList as $list) {
            $institutionIds[] = $list->id;
        }

        if ($institutionId <= 0) {
            $conditions[$this->aliasField('institution_id') . ' IN'] = $institutionIds;
        } else {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }


        if ($educationProgrammeId != -1) {
            $educationGradeIds = $EducationGrades
                ->find()
                ->select(['id'])
                ->where(['education_programme_id' => $educationProgrammeId])
                ->order(['`order`' => 'DESC'])
                ->extract('id')
                ->first();

            $conditions[$this->aliasField('education_grade_id')] = $educationGradeIds;
        } else {
            $educationProgrammeIds = $EducationProgrammes
                ->find()
                ->select(['id'])
                ->extract('id')
                ->toArray();

            foreach ($educationProgrammeIds as $id) {

                $educationGrades = $EducationGrades
                    ->find()
                    ->select(['id'])
                    ->where(['education_programme_id' => $id])
                    ->order(['`order`' => 'DESC'])
                    ->first();

                $educationGradeIds[] = $educationGrades->id;
            }

            $conditions[$this->aliasField('education_grade_id') . ' IN'] = $educationGradeIds;
        }

        $query
            ->select([
                'academic_period_name' => 'AcademicPeriods.name',
                'area_name' => 'Areas.name',
                'institution_name' => 'Institutions.name',
                'education_programme_name' => 'EducationProgrammes.name',
                'total_students' => $query->func()->count('Users.id')
            ])

            ->InnerJoin(['Users' => 'security_users'], [
                'Users.id = ' . $this->aliasField('student_id')
            ])

            ->InnerJoin(['Institutions' => 'institutions'], [
                'Institutions.id = ' . $this->aliasField('institution_id')
            ])

            ->leftJoin(['Areas' => 'areas'], [
                'Institutions.area_id  =  Areas.id'
            ])

            ->InnerJoin(['StudentStatuses' => 'student_statuses'], [
                'StudentStatuses.id = ' . $this->aliasField('student_status_id')
            ])

            ->InnerJoin(['EducationGrades' => 'education_grades'], [
                'EducationGrades.id = ' . $this->aliasField('education_grade_id'),
            ])

            ->InnerJoin(['EducationProgrammes' => 'education_programmes'], [
                'EducationProgrammes.id = EducationGrades.education_programme_id'
            ])

            ->InnerJoin(['AcademicPeriods' => 'academic_periods'], [
                'AcademicPeriods.id = ' . $this->aliasField('academic_period_id')
            ])

            ->where(array_merge($conditions, [
                $this->aliasField('academic_period_id') => $academicPeriodId,
                'StudentStatuses.code' => 'CURRENT',
            ]))
            ->group(['Institutions.id', 'EducationProgrammes.id']);

    }


    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area')
        ];

        $extraFields[] = [
            'key' => 'EducationProgrammes.name',
            'field' => 'education_programme_name',
            'type' => 'string',
            'label' => __('Education Programme')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'total_students',
            'type' => 'string',
            'label' => __('Number of Students')
        ];

        $newFields = $extraFields;
        $fields->exchangeArray($newFields);
    }

    public function getChildren($id, $idArray)
    {
        $Areas = TableRegistry::get('Area.Areas');
        $result = $Areas->find()
            ->where([
                $Areas->aliasField('parent_id') => $id
            ])
            ->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
            $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
