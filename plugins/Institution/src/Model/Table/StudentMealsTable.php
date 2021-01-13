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
        $this->belongsTo('MealBenefit', ['className' => 'Meal.MealBenefits', 'foreignKey' =>'benefit_type_id']); 
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

        if ($day != -1) {      
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
          
        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) use ($InstitutionMealStudents, $findDay, $attendancePeriodId, $subjectId, $educationGradeId) {
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
                        ];
                
                $areasData = $InstitutionMealStudents
                            ->find()
                            ->contain('MealBenefit')
                            ->select([
                                $InstitutionMealStudents->aliasField('date'),
                                $InstitutionMealStudents->aliasField('paid'),
                                $InstitutionMealStudents->aliasField('benefit_type_id'),
                                'MealBenefit.name'
                            ])
                            ->where($conditions)
                            ->first();
                 
                 $data = [
                    'date' => $areasData->date,
                    'paid' => $areasData->paid,                    
                    'meal_benefit_id' => $areasData->benefit_type_id,
                    'meal_benefit' => $areasData->meal_benefit->name
                ];  
                            
                $row->institution_student_meal = $data;
                return $row;
            });
        });
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
                        ];

        $benefit = '';
        $benefit = $InstitutionMealStudents
                            ->find()
                            ->contain('MealBenefit')
                         
                            ->select([
                                $InstitutionMealStudents->aliasField('benefit_type_id'),
                                'meal_benefit' => 'MealBenefit.name'
                            ])
                            ->where($conditions)
                            ->first();
                            if (!empty($benefit)) {
                                $benefit = $benefit->meal_benefit;
                            }
                            else{
                                $benefit = "Null";
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
                        ];

        $mealReceived = '';
        $mealReceived = $InstitutionMealStudents
                            ->find()
                            ->contain('MealBenefit')
                         
                            ->select([
                                $InstitutionMealStudents->aliasField('benefit_type_id'),
                                'meal_benefit' => 'MealBenefit.name'
                            ])
                            ->where($conditions)
                            ->first();
                            if (!empty($mealReceived)) {
                                $mealReceived = "Paid";
                            }
                            else{
                                $mealReceived = "Free";
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
                ->select(['name' => 'Users.first_name',
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
        //echo "<pre>";
        ///print_r($this->_absenceData); die();
    }
    
}
