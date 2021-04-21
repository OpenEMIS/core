<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;

use App\Model\Table\ControllerActionTable;

class StudentMealsTable extends ControllerActionTable
{

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('MealBenefit', ['className' => 'Meal.MealBenefits', 'foreignKey' =>'meal_benefit_id']); 
        $this->belongsTo('MealReceived', ['className' => 'Meal.MealReceived', 'foreignKey' =>'meal_received_id']); 
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_date',
                'end_date',
                'start_year',
                'end_year',
                'FTE',
                'staff_type_id',
                'staff_status_id',
                'institution_id',
                'institution_position_id',
                'security_group_user_id'
            ],
            'pages' => ['index']
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
    }

    public function findClassStudentsWithMeal(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $mealPogrammesId = $options['meal_programmes_id'];
        $institutionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $weekId = $options['week_id'];
        $weekStartDay = $options['week_start_day'];
        $weekEndDay = $options['week_end_day'];
        $day = $options['day_id'];
        $subjectId = $options['subject_id'];      

         if ($day == -1) {
            $findDay[] = $weekStartDay;
            $findDay[] = $weekEndDay;
        } else {
            $findDay = $day;
        }

        $query
        ->select([
            $this->aliasField('academic_period_id'),
            $this->aliasField('institution_class_id'),
            $this->aliasField('institution_id'),
            $this->aliasField('student_id'),
            $this->Users->aliasField('id'),
            $this->Users->aliasField('openemis_no'),
            $this->Users->aliasField('first_name'),
            $this->Users->aliasField('middle_name'),
            $this->Users->aliasField('third_name'),
            $this->Users->aliasField('last_name'),
            $this->Users->aliasField('preferred_name')
        ])
        ->contain([$this->Users->alias()])
        ->matching($this->StudentStatuses->alias(), function($q) {
            return $q->where([
                $this->StudentStatuses->aliasField('code') => 'CURRENT'
            ]);
        })
        ->where([
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('institution_class_id') => $institutionClassId,
        ])
        ->order([
            $this->Users->aliasField('first_name')
        ]);
        // if ($day == -1) {  
        if ($day != -1) {      
            //$query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            $query ->formatResults(function (ResultSetInterface $results) use ($findDay,$mealPogrammesId) {              
                 return $results->map(function ($row) use ($findDay,$mealPogrammesId) {
                    $InstitutionMealStudents =  TableRegistry::get('Institution.InstitutionMealStudents');
                    $academicPeriodId = $row->academic_period_id;
                    $institutionClassId = $row->institution_class_id;
                    $studentId = $row->student_id;
                    $institutionId = $row->institution_id;

                    $conditions = [
                                $InstitutionMealStudents->aliasField('academic_period_id = ') => $academicPeriodId,
                                $InstitutionMealStudents->aliasField('institution_class_id = ') => $institutionClassId,
                                $InstitutionMealStudents->aliasField('student_id = ') => $studentId,
                                $InstitutionMealStudents->aliasField('institution_id = ') => $institutionId,
                                $InstitutionMealStudents->aliasField('meal_programmes_id = ') => $mealPogrammesId,
                                $InstitutionMealStudents->aliasField('date = ') => $findDay,
                            ];
                    
                    $areasData = $InstitutionMealStudents
                                ->find()
                                ->contain(['MealBenefit','MealReceived'])
                                ->select([
                                    $InstitutionMealStudents->aliasField('date'),
                                    $InstitutionMealStudents->aliasField('paid'),
                                    $InstitutionMealStudents->aliasField('meal_received_id'),
                                    'MealReceived.name',
                                    $InstitutionMealStudents->aliasField('meal_benefit_id'),
                                    'MealBenefit.name'
                                ])
                                ->where($conditions)
                                ->first();

                     if (isset($areasData)) {
                         $data = [                    
                            'date' =>!empty($areasData->date) ? $areasData->date : $findDay,
                            // 'paid' => $areasData->paid,                    
                            'meal_benefit_id' => $areasData->meal_benefit_id,
                            'meal_benefit' => $areasData->meal_benefit->name,
                            'meal_received_id' => !empty($areasData->meal_received_id) ? $areasData->meal_received_id : "3",
                            'meal_received' => !empty($areasData->meal_received->name) ? $areasData->meal_received->name : "None"
                        ];  
                     }
                     else{
                        $StudentMealMarkedRecords = TableRegistry::get('Meal.StudentMealMarkedRecords');

                        $isMarkedRecords = $StudentMealMarkedRecords
                                    ->find()
                                    ->contain(['MealBenefit'])
                                    ->select([
                                        $StudentMealMarkedRecords->aliasField('date'),
                                        $StudentMealMarkedRecords->aliasField('meal_benefit_id'),
                                        'MealBenefit.name'
                                    ])

                                    ->where([
                                        $StudentMealMarkedRecords->aliasField('academic_period_id = ') => $academicPeriodId,
                                        $StudentMealMarkedRecords->aliasField('institution_class_id = ') => $institutionClassId,
                                        $StudentMealMarkedRecords->aliasField('meal_programmes_id = ') => $mealPogrammesId,
                                        $StudentMealMarkedRecords->aliasField('institution_id = ') => $institutionId,
                                        $StudentMealMarkedRecords->aliasField('date = ') => $findDay,
                                    ])
                                    ->first();

                        $data = [                    
                            'date' =>!empty($isMarkedRecords->date) ? $isMarkedRecords->date : $findDay,
                            // 'paid' => null,
                            'meal_benefit_id' => $isMarkedRecords->meal_benefit_id,
                            'meal_benefit' => $isMarkedRecords->meal_benefit->name,
                            'meal_received_id' => !empty($isMarkedRecords) ?  "1"  : null,
                            'meal_received' => !empty($isMarkedRecords) ? "Received" : "None"
                        ];
                     }
                     
                                
                    $row->institution_student_meal = $data;
                    return $row;
                });
            });
            
        }

        else{

            $AcademicPeriodsTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');

            $dayList = $AcademicPeriodsTable
                ->find('DaysForPeriodWeek', [
                    'academic_period_id' => $academicPeriodId,
                    'week_id' => $weekId,
                    'institution_id' => $institutionId,
                    'exclude_all' => true
                ])
                ->toArray();

            $studentListResult = $this
                ->find('list', [
                    'keyField' => 'student_id',
                    'valueField' => 'student_id'
                ])
                ->matching($this->StudentStatuses->alias(), function($q) {
                    return $q->where([
                        $this->StudentStatuses->aliasField('code') => 'CURRENT'
                    ]);
                })
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('institution_class_id') => $institutionClassId,
                ])
                ->all();

                if (!$studentListResult->isEmpty()) {
                    $studentList = $studentListResult->toArray();
                    $InstitutionMealStudents =  TableRegistry::get('Institution.InstitutionMealStudents');
                    $StudentMealMarkedRecords = TableRegistry::get('Meal.StudentMealMarkedRecords');

                    $result = $InstitutionMealStudents
                    ->find()
                    ->contain(['MealBenefit','MealReceived'])
                    ->select([
                        $InstitutionMealStudents->aliasField('student_id'),
                        $InstitutionMealStudents->aliasField('date'),
                        $InstitutionMealStudents->aliasField('paid'),
                        $InstitutionMealStudents->aliasField('meal_received_id'),
                        'code' => 'MealReceived.name'
                    ])
                    ->where([
                        $InstitutionMealStudents->aliasField('academic_period_id = ') => $academicPeriodId,
                        $InstitutionMealStudents->aliasField('institution_class_id = ') => $institutionClassId,                        
                        $InstitutionMealStudents->aliasField('student_id IN ') => $studentList,
                        $InstitutionMealStudents->aliasField('institution_id = ') => $institutionId,
                        'AND' => [
                            $InstitutionMealStudents->aliasField('date >= ') => $weekStartDay,
                            $InstitutionMealStudents->aliasField('date <= ') => $weekEndDay,

                        ]
                    ])
                    ->toArray();

                    $isMarkedRecords = $StudentMealMarkedRecords
                    ->find()
                    ->contain(['MealBenefit'])
                    ->select([
                        $StudentMealMarkedRecords->aliasField('date'),
                        $StudentMealMarkedRecords->aliasField('meal_benefit_id'),
                        'MealBenefit.name'
                    ])

                    ->where([
                        $StudentMealMarkedRecords->aliasField('academic_period_id = ') => $academicPeriodId,
                        $StudentMealMarkedRecords->aliasField('institution_class_id = ') => $institutionClassId,
                        $StudentMealMarkedRecords->aliasField('meal_programmes_id = ') => $mealPogrammesId,
                        $StudentMealMarkedRecords->aliasField('institution_id = ') => $institutionId,
                        'AND' => [
                            $StudentMealMarkedRecords->aliasField('date >= ') => $weekStartDay,
                            $StudentMealMarkedRecords->aliasField('date <= ') => $weekEndDay,

                        ]
                    ])
                    ->toArray();
                   
                    $studentMealsData = [];
                    foreach ($studentList as $value) {
                        $studentId = $value;
                         if (!isset($studentMealsData[$studentId])) {
                            $studentMealsData[$studentId] = [];
                         }
                         foreach ($dayList as $day) {
                            $dayId = $day['day'];
                            $date = $day['date'];

                            $keyId = 1;

                            if (!isset($studentMealsData[$studentId][$dayId])) {
                                $studentMealsData[$studentId][$dayId] = [];
                            }
                            $studentMealsData[$studentId][$dayId][$keyId] = 'None';
                                // echo "<pre>"; print_r($isMarkedRecords); die();
                                foreach ($isMarkedRecords as $entity) {

                                    $entityDate = $entity->date->format('Y-m-d');
                                    $entityPeriod = $keyId;

                                    if ($entityDate == $date && $entityPeriod == $keyId) {
                                        $studentMealsData[$studentId][$dayId][$keyId] = 'Received';
                                        break;
                                    }
                                }
                         
                                foreach ($result as $key => $entity) {
                                    $entityDateFormat = $entity->date->format('Y-m-d');
                                    $entityStudentId = $entity->student_id;
                                    if ($studentId == $entityStudentId && $entityDateFormat == $date)
                                    {
                                        $studentMealsData[$studentId][$dayId][$keyId] = $entity->code;
                                        break;
                                    }
                                }
                         }
                    }

                    $query
                    ->formatResults(function (ResultSetInterface $results) use ($studentMealsData) {
                        return $results->map(function ($row) use ($studentMealsData) {
                            $studentId = $row->student_id;
                            if (isset($studentMealsData[$studentId])) {
                                $row->week_meals = $studentMealsData[$studentId];
                            }
                            return $row;
                        });
                    });
                }
        }
        return $query;       

    }

    public function onExcelGetBenefit(Event $event, Entity $entity)
    {
        
        $InstitutionMealStudents =  TableRegistry::get('Institution.InstitutionMealStudents');

        $conditions = [
                            $InstitutionMealStudents->aliasField('academic_period_id = ') => $entity->academic_period_id,
                            $InstitutionMealStudents->aliasField('institution_class_id = ') => $entity->institution_class_id,
                            $InstitutionMealStudents->aliasField('student_id = ') => $entity->student_id,
                            $InstitutionMealStudents->aliasField('institution_id = ') => $entity->institution_id,
                            $InstitutionMealStudents->aliasField('date = ') => $entity->institution_student_meal['date'],
                        ];

        $benefit = '';
        $benefit = $InstitutionMealStudents
                            ->find()
                            ->contain(['MealBenefit','MealReceived'])
                            ->select([
                                $InstitutionMealStudents->aliasField('meal_received_id'),
                                'MealReceived.name',
                                $InstitutionMealStudents->aliasField('meal_benefit_id'),
                                'MealBenefit.name'
                            ])
                            ->where($conditions)
                            ->first();
                     
                            if ($benefit->meal_received_id == "2" || $benefit->meal_received_id == "3" || empty($benefit->meal_received_id)) {
                                 $benefit = "Null";
                                
                            }
                            else{
                                $benefit = $benefit->meal_benefit->name;
                            }
    
        return $benefit;
    } 

    public function onExcelGetMealReceived(Event $event, Entity $entity)
    {
        
        $InstitutionMealStudents =  TableRegistry::get('Institution.InstitutionMealStudents');

        $conditions = [
                            $InstitutionMealStudents->aliasField('academic_period_id = ') => $entity->academic_period_id,
                            $InstitutionMealStudents->aliasField('institution_class_id = ') => $entity->institution_class_id,
                            $InstitutionMealStudents->aliasField('student_id = ') => $entity->student_id,
                            $InstitutionMealStudents->aliasField('institution_id = ') => $entity->institution_id,
                             $InstitutionMealStudents->aliasField('date = ') => $entity->institution_student_meal['date'],
                        ];
           

        $mealReceived = '';
        $mealReceived = $InstitutionMealStudents
                            ->find()
                            ->contain(['MealBenefit','MealReceived'])
                            ->select([
                                $InstitutionMealStudents->aliasField('meal_received_id'),
                                'MealReceived.name',
                                $InstitutionMealStudents->aliasField('meal_benefit_id'),
                                'MealBenefit.name'
                            ])
                            ->where($conditions)
                            ->first();
                            if (empty($mealReceived)) {
                                $mealReceived = "None";
                            }
                            else{
                                $mealReceived = $mealReceived->meal_received->name;
                            }
                            
    
        return $mealReceived;
    } 

    
    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        ini_set("memory_limit", "-1");

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $classId = !empty($this->request->query['institution_class_id']) ? $this->request->query['institution_class_id'] : 0 ;
        $weekId = $this->request->query['week_id'];
        $weekStartDay = $this->request->query['week_start_day'];
        $weekEndDay = $this->request->query['week_end_day'];
        $dayId = $this->request->query['day_id'];

        $InstitutionMealStudents =  TableRegistry::get('Institution.InstitutionMealStudents');
               

        $sheetName = 'StudentMeals';
        $sheets[] = [
            'name' => $sheetName,
            'table' => $this,
            'query' => $this
                ->find()
                ->select([//POCOR-5941 starts
                    'name' => $this->find()->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),//POCOR-5941 ends
                    'openemis_no' => 'Users.openemis_no'
                ]),
            'institutionId' => $institutionId,
            'classId' => $classId,
            'academicPeriodId' => $this->request->query['academic_period_id'],
            'weekId' => $weekId,
            'weekStartDay' => $weekStartDay,
            'weekEndDay' => $weekEndDay,
            'dayId' => $dayId,
            'orientation' => 'landscape'
        ];
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $day_id = $this->request->query('day_id');
        $newArray[] = [
            'key' => 'StudentMeals.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'Name'
        ];

        $newArray[] = [
            'key' => 'StudentMeals.name',
            'field' => 'name',
            'type' => 'string',
            'label' => 'Name'
        ];

        $newArray[] = [
                'key' => 'StudentMeals.meal_received',
                'field' => 'mealReceived',
                'type' => 'string',
                'label' => ''
        ];

        $newArray[] = [
                'key' => 'StudentMeals.meal_benefit',
                'field' => 'benefit',
                'type' => 'string',
                'label' => ''
        ];
           


        $fields_arr = $fields->getArrayCopy();
        
        $field_show = array();
        $filter_key = array('StudentMeals.id','StudentMeals.student_id','StudentMeals.institution_class_id','StudentMeals.academic_period_id','StudentMeals.student_status_id','InstitutionMealStudents.meal_benefit');

        // foreach ($fields_arr as $field){
        //     if (in_array($field['key'], $filter_key)) {
        //         unset($field);
        //     }
        //     else {
        //         array_push($field_show,$field);
        //     }
        // }
        
        //$newFields = array_merge($newArray, $field_show);
        $fields->exchangeArray($newArray);
        $sheet = $settings['sheet'];
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        // Set data into a temporary variable
        $options['institution_id'] = $sheet['institutionId'];
        $options['institution_class_id'] = $sheet['classId'];
        $options['academic_period_id'] = $sheet['academicPeriodId'];
        $options['week_id'] = $sheet['weekId'];
        $options['week_start_day'] = $sheet['weekStartDay'];
        $options['week_end_day'] = $sheet['weekEndDay'];
        $options['day_id'] = $sheet['dayId'];
       
        $this->_absenceData = $this->findClassStudentsWithMeal($sheet['query'], $options);
    }
    
}
