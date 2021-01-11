<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentsEnrollmentSummaryTable extends AppTable  {
    public function initialize(array $config) {
        $this->table('institution_students');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
       
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaEducationId = $requestData->area_education_id;
        //pocor 5863 start
        $area_id_array=[];
        if(!empty($areaEducationId)){
            $Areas = TableRegistry::get('Areas');
            if($areaEducationId == -1){
                $regionAreaArr = $Areas->find()
                            ->where($conditions)
                            ->All();
            }else{
                $area_id_array[$areaEducationId] = $areaEducationId;
                $conditions = ['parent_id' => $areaEducationId];
                $regionAreaArr = $Areas->find()
                            ->where($conditions)
                            ->All();
            }
            
            if(!empty($regionAreaArr)){
                foreach ($regionAreaArr as $reg_val) {
                    $area_id_array[$reg_val->id] = $reg_val->id;
                    $conditions1 = array();
                    $conditions1 = ['parent_id' => $reg_val->id];
                    $distAreaArr = $Areas->find()
                                        ->where($conditions1)
                                        ->All();
                    if(!empty($distAreaArr)){
                        foreach ($distAreaArr as $dist_val) {
                            $area_id_array[$dist_val->id] = $dist_val->id;
                        }
                    }
                                        
                }
            }
        }
        $areaEducationId = $area_id_array;      
        //pocor 5863 ends 
        $query
            ->select([
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'academic_period_name' => 'AcademicPeriods.name',
                'gender_name' =>'Genders.name',
                'education_grade_name' => 'EducationGrades.name',
                'count'=> $this->find()->func()->count('DISTINCT '.$this->aliasField('student_id'))
                
             ])
            ->leftJoin(['Users' => 'security_users'], [
                            'Users.id = ' . $this->aliasfield('student_id')
                        ])
            ->leftJoin(['Genders' => 'genders'], [
                            'Users.gender_id = ' . 'Genders.id'
                        ])
            ->leftJoin(['InstitutionStudents' => 'institution_students'], [
                            'Users.id = ' . 'InstitutionStudents.student_id'
                        ])
            ->leftJoin(['Institutions' => 'institutions'], [
                            'InstitutionStudents.institution_id = ' . 'Institutions.id'
                        ])
            ->leftJoin(['Areas' => 'areas'], [
                            'Institutions.area_id = ' . 'Areas.id'
                        ])
            ->leftJoin(['EducationGrades' => 'education_grades'], [
                            'InstitutionStudents.education_grade_id = ' . 'EducationGrades.id'
                        ])
            ->leftJoin(['AcademicPeriods' => 'academic_periods'], [
                            'InstitutionStudents.academic_period_id = ' . 'AcademicPeriods.id'
                        ])
            //pocor 5863 start
            ->where(['Genders.id IS NOT NULL', 'AcademicPeriods.id' => $academicPeriodId, 'Areas.id IN ' =>  $areaEducationId])
            ->group(['Institutions.id', 'EducationGrades.id', 'Genders.id']);
            //pocor 5863 ends
    }
            
        
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Name')
        ];  
         

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Code')
        ];

        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];



        $extraFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];
         $extraFields[] = [
            'key' => '',
            'field' => 'count',
            'type' => 'string',
            'label' => __('Number of Students')
        ];  

        
        $newFields = $extraFields;
        
        $fields->exchangeArray($newFields);
    }

}
