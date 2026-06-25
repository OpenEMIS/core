<?php
//POCOR-9267 Starts
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class MealSummaryTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('institution_meal_students');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('SecurityUsers', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('MealProgrammes', [
            'className' => 'Meal.MealProgrammes',
            'foreignKey' => 'meal_programmes_id'
        ]);
        $this->belongsTo('InstitutionClasses', [
            'className' => 'Institution.InstitutionClasses',
            'foreignKey' => 'institution_class_id',
            'joinType' => 'INNER',
        ]);

        $this->hasOne('InstitutionClassGrades', [
            'className' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'bindingKey' => 'institution_class_id',
            'joinType' => 'LEFT',
        ]);

        $this->belongsTo('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'foreignKey' => 'education_grade_id',
            'joinType' => 'LEFT',
        ]);
        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.AreaList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $selectedArea = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;

        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $MealProgrammes = TableRegistry::getTableLocator()->get('Meal.MealProgrammes');
        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');

        $conditions = [];

        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }

        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }else{
            $superAdmin = $requestData->super_admin;
            $userId = $requestData->user_id;
            
            $institutionIds = [];
            if (!$superAdmin) {
                $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $instituitionData = $InstitutionsTable->find('byAccess', ['userId' => $userId])->toArray();
                if (isset($instituitionData)) {
                    foreach ($instituitionData as $key => $value) {
                        $institutionIds[] = $value->id;
                    }
                }
                if ($institutionId == 0) {
                    $conditions[$this->aliasField('institution_id IN')] = $institutionIds;
                }
            }
        }
        
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }

        if ($areaLevelId > 1) {
           // $conditions[$this->aliasField('Areas.area_level_id')] = $areaLevelId;
        }

        $query
            ->select([
                'academic_period'         => 'AcademicPeriods.name',
                'institution_code'        => 'Institutions.code',
                'institution_name'        => 'Institutions.name',
                'education_grade'         => 'EducationGrades.name',
                'class'                   => 'InstitutionClasses.name',
                'meal_programme'          => 'MealProgrammes.name',
                'institution_class_id', 
                'MealSummary.institution_id',
                'education_grade_id'      => 'InstitutionClassGrades.education_grade_id',     // Optional
                //'meal_programmes_id',
                //'academic_period_id',
                'male_students' => $query->func()->count('DISTINCT CASE WHEN SecurityUsers.gender_id = 1 THEN SecurityUsers.id END'),
                'female_students' => $query->func()->count('DISTINCT CASE WHEN SecurityUsers.gender_id = 2 THEN SecurityUsers.id END'),
                'total_students' => $query->newExpr(
                    'COUNT(DISTINCT CASE WHEN SecurityUsers.gender_id = 1 THEN SecurityUsers.id END) + 
                    COUNT(DISTINCT CASE WHEN SecurityUsers.gender_id = 2 THEN SecurityUsers.id END)'
                )
            ])
            ->contain([
                'SecurityUsers' => ['fields' => ['id', 'gender_id']],
                'InstitutionClasses' => ['fields' => ['id', 'name']],
                'InstitutionClasses.InstitutionClassGrades.EducationGrades' => ['fields' => ['id', 'name']],
                'Institutions' => ['fields' => ['id', 'code', 'name', 'area_id']],
                'Institutions.Areas' => ['fields' => ['id', 'area_level_id']],
            ])
            ->leftJoinWith('InstitutionClasses.InstitutionClassGrades.EducationGrades') // important for JOIN
            ->leftJoinWith('Institutions.Areas') // to use Areas in WHERE
            ->INNERJoin(
                ['AcademicPeriods' => 'academic_periods'],
                ['AcademicPeriods.id = MealSummary.academic_period_id']
            )
            ->INNERJoin(
                ['MealProgrammes' => 'meal_programmes'],
                ['MealProgrammes.id = MealSummary.meal_programmes_id']
            )
            ->where($conditions)
            ->group([
                'AcademicPeriods.name',
                'Institutions.code',
                'Institutions.name',
                'EducationGrades.name',
                'InstitutionClasses.name',
                'MealProgrammes.name'
            ])
            ->order([
                'AcademicPeriods.name' => 'ASC',
                'Institutions.name' => 'ASC',
                'EducationGrades.name' => 'ASC',
                'InstitutionClasses.name' => 'ASC',
                'MealProgrammes.name' => 'ASC'
            ]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'academic_period', 
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'institution_code', 
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'institution_name', 
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'education_grade',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $newFields[] = [
            'key' => 'class',
            'field' => 'class',
            'type' => 'string',
            'label' => __('Class')
        ];

        $newFields[] = [
            'key' => 'meal_programme', 
            'field' => 'meal_programme',
            'type' => 'string',
            'label' => __('Meal Programme')
        ];

        $newFields[] = [
            'key' => 'male_students', // needed for Excel behavior
            'field' => 'male_students',
            'type' => 'string',
            'label' => __('Number of Male Students')
        ];

        $newFields[] = [
            'key' => 'female_students',
            'field' => 'female_students',
            'type' => 'string',
            'label' => __('Number of Female Students')
        ];

        $newFields[] = [
            'key' => 'total_students',
            'field' => 'total_students',
            'type' => 'string',
            'label' => __('Total Number of Students')
        ];

        $fields->exchangeArray($newFields);
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
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
//POCOR-9267 Ends