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
use Exception;
use RuntimeException;

class StudentMealsTable extends ControllerActionTable
{

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('MealBenefit', ['className' => 'Meal.MealBenefits', 'foreignKey' => 'meal_benefit_id']);
        $this->belongsTo('MealReceived', ['className' => 'Meal.MealReceived', 'foreignKey' => 'meal_received_id']);
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

    /**
     * @param Query $query
     * @param array $options
     * @return array|Query|mixed
     * refactured for POCOR-7908
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function findClassStudentsWithMeal(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $mealProgramId = $options['meal_program_id'];
        $institutionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $weekId = $options['week_id'];
        $weekStartDay = $options['week_start_day'];
        $weekEndDay = $options['week_end_day'];
        $day = $options['day_id'];
        $ID = $options['id'];
        $studentID = $options['student_id'];
        $query = $this->getMealsMainQuery($query,
            $academicPeriodId,
            $institutionId,
            $institutionClassId,
            $studentID,
            $ID);
        $InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
        $StudentMealMarkedRecords = TableRegistry::get('Meal.StudentMealMarkedRecords');
        $MealProgrammes = $InstitutionMealStudents->MealProgrammes;
        $MealBenefit = $InstitutionMealStudents->MealBenefit;
        $MealReceived = $InstitutionMealStudents->MealReceived;
        $default_meal_receive_id = $this->getDefaultMealReceiveID();
        if ($day != -1) {
            $query = $query
                ->leftJoin([
                    $StudentMealMarkedRecords->alias() => $StudentMealMarkedRecords->table()],
                    [$StudentMealMarkedRecords->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                        $StudentMealMarkedRecords->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                        $StudentMealMarkedRecords->aliasField('meal_programmes_id = ') . $mealProgramId,
                        $StudentMealMarkedRecords->aliasField("date = '") . $day . "'",
                    ])->select([
                    'marked_meal_id' => $StudentMealMarkedRecords->aliasField('id'),
                    'marked_meal_program_id' => $StudentMealMarkedRecords->aliasField('meal_programmes_id'),
                    'marked_meal_benefit_id' => $StudentMealMarkedRecords->aliasField('meal_benefit_id'),
                    'marked_meal_date' => $StudentMealMarkedRecords->aliasField('date'),
                ])
                ->leftJoin([
                    $InstitutionMealStudents->alias() => $InstitutionMealStudents->table()],
                    [$InstitutionMealStudents->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                        $InstitutionMealStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                        $InstitutionMealStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                        $InstitutionMealStudents->aliasField('meal_programmes_id = ') . $mealProgramId,
                        $InstitutionMealStudents->aliasField("date = '") . $day . "'",
                    ])
                ->leftJoin([$MealProgrammes->alias() => $MealProgrammes->table()], [
                    $MealProgrammes->aliasField('id =') . $InstitutionMealStudents->aliasField('meal_programmes_id')
                ])
                ->leftJoin([$MealReceived->alias() => $MealReceived->table()], [
                    $MealReceived->aliasField('id =') . $InstitutionMealStudents->aliasField('meal_received_id')
                ])
                ->leftJoin([$MealBenefit->alias() => $MealBenefit->table()], [
                    $MealBenefit->aliasField('id =') . $InstitutionMealStudents->aliasField('meal_benefit_id')
                ])
                ->leftJoin([$MealBenefit->alias() => $MealBenefit->table()], [
                    $MealBenefit->aliasField('id =') . $InstitutionMealStudents->aliasField('meal_benefit_id')
                ])
                ->select([
                    'institution_meal_student_id' => $InstitutionMealStudents->aliasField('id'),
                    'meal_program_id' => $InstitutionMealStudents->aliasField('meal_programmes_id'),
                    'meal_program_name' => $MealProgrammes->aliasField('name'),
                    'meal_benefit_id' => $InstitutionMealStudents->aliasField('meal_benefit_id'),
                    'meal_benefit_name' => $MealBenefit->aliasField('name'),
                    'meal_received_id' => $InstitutionMealStudents->aliasField('meal_received_id'),
                    'meal_received_name' => $MealReceived->aliasField('name'),
                    'meal_paid' => $InstitutionMealStudents->aliasField('paid'),
                    'meal_date' => $InstitutionMealStudents->aliasField('date'),
                ])
                ->group([$this->aliasField('student_id')]);

            $query = $this->getDailyMealData($query, $default_meal_receive_id);

        }

        if ($day == -1) {
            $findDay[] = $weekStartDay;
            $findDay[] = $weekEndDay;
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
                ->matching($this->StudentStatuses->alias(), function ($q) {
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
                $InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
                $StudentMealMarkedRecords = TableRegistry::get('Meal.StudentMealMarkedRecords');

                $result = $InstitutionMealStudents
                    ->find()
                    ->contain(['MealBenefit', 'MealReceived'])
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
                        $StudentMealMarkedRecords->aliasField('meal_programmes_id = ') => $mealProgramId,
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
                            if ($studentId == $entityStudentId && $entityDateFormat == $date) {
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

    /**
     * POCOR-7908
     * @param Query $query
     * @param array $options
     * @return array|Query|mixed
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
        public function findClassStudentsWithMealSave(Query $query, array $options)
    {
        $arrayStudents = $this->find('classStudentsWithMeal', $options)->toArray();
        if (sizeof($arrayStudents) == 0) {
            return $this->findClassStudentsWithMeal($query, $options);
        }

        $institutionId = $options['institution_id'];
        $mealProgramId = $options['meal_program_id'];
        $institutionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $day = $options['day_id'];

        $firstStudent = $arrayStudents[0];
        $isMarked = $firstStudent->marked_meal_id;
        $StudentMealMarkedRecords = TableRegistry::get('Meal.StudentMealMarkedRecords');
        $InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');

        if (empty($isMarked)) {
            $result = $this->markDay($institutionId,
                $institutionClassId,
                $mealProgramId,
                $academicPeriodId,
                $day,
                $StudentMealMarkedRecords);
            $this->log($result, 'debug');
        }
        foreach ($arrayStudents as $student) {
            $isMealReceived = $student->meal_received_id;
            $defaultMealReceiveId = $student->default_meal_receive_id;
            if (empty($isMealReceived)) {
                $studentID = $student->student_id;
                $data = [
                    'institution_id' => $institutionId,
                    'institution_class_id' => $institutionClassId,
                    'meal_programmes_id' => $mealProgramId,
                    'academic_period_id' => $academicPeriodId,
                    'meal_received_id' => $defaultMealReceiveId,
                    'student_id' => $studentID,
                    'date' => $day,
                    'meal_benefit_id' => null
                ];
                try {
                    $entity = $InstitutionMealStudents->newEntity($data);
                    $InstitutionMealStudents->save($entity);
                } catch (\Exception $exception) {
                    $data = ['error' => $exception->getMessage()];
                    echo json_encode($data);
                    die;
                }
            }
        }
        return $this->findClassStudentsWithMeal($query, $options);
    }

    /**
     * POCOR-7908
     * @param Query $query
     * @param array $options
     * @return array|Query|mixed
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function findClassStudentWithMealSave(Query $query, array $options)
    {

        $InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
        $id = $options['institution_meal_student_id'];
        $mealReceivedId = $options['meal_received_id'];
        $mealBenefitId = $options['meal_benefit_id'];
        $onlyChangeBenefit = $options['only_change_benefit'];

        try {
            $entity = $InstitutionMealStudents->get($id);
            if (isset($mealReceivedId)) {
                $entity->meal_received_id = $mealReceivedId;
            }
            if (isset($mealBenefitId)) {
                $entity->meal_benefit_id = $mealBenefitId;
            }
            if (isset($onlyChangeBenefit)) {
                $entity->only_change_benefit = $onlyChangeBenefit;
            }
            $result = $InstitutionMealStudents->save($entity);
            $this->log($result, 'debug');
        } catch (\Exception $exception) {
            $data = ['error' => $exception->getMessage()];
            echo json_encode($data);
            die;
        }
        return $this->findClassStudentsWithMeal($query, $options);
    }

    public function onExcelGetBenefit(Event $event, Entity $entity)
    {

        $InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
        $StudentMealMarkedRecords = TableRegistry::get('Meal.StudentMealMarkedRecords');


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
            ->contain(['MealBenefit', 'MealReceived'])
            ->select([
                $InstitutionMealStudents->aliasField('meal_received_id'),
                'MealReceived.name',
                $InstitutionMealStudents->aliasField('meal_benefit_id'),
                'MealBenefit.name'
            ])
            ->where($conditions)
            ->first();
        if (isset($benefit) && !empty($benefit)) {
            if ($benefit->meal_received_id != 1) {
                $benefit = '';
            } else {
                $benefit = $benefit->meal_benefit->name;
            }

        } else if (!empty($benefit)) {
            $isMarkedRecords = $StudentMealMarkedRecords
                ->find()
                ->contain(['MealBenefit'])
                ->select([
                    $StudentMealMarkedRecords->aliasField('meal_benefit_id'),
                    'MealBenefit.name'
                ])
                ->where([
                    $StudentMealMarkedRecords->aliasField('academic_period_id = ') => $entity->academic_period_id,
                    $StudentMealMarkedRecords->aliasField('institution_class_id = ') => $entity->institution_class_id,
                    $StudentMealMarkedRecords->aliasField('date = ') => $entity->institution_student_meal['date'],
                    $StudentMealMarkedRecords->aliasField('institution_id = ') => $entity->institution_id,
                ])
                ->first();
            if (!empty($isMarkedRecords->meal_benefit_id)) {
                if ($benefit->meal_received_id != 1) {
                    $benefit = '';
                } else {
                    $benefit = $isMarkedRecords->meal_benefit->name;
                }
            }
        } else {
            $benefit = '';
        }
        return $benefit;
    }

    public function onExcelGetMealReceived(Event $event, Entity $entity)
    {
        $InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
        $StudentMealMarkedRecords = TableRegistry::get('Meal.StudentMealMarkedRecords');

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
            ->contain(['MealBenefit', 'MealReceived'])
            ->select([
                $InstitutionMealStudents->aliasField('meal_received_id'),
                'MealReceived.name',
                $InstitutionMealStudents->aliasField('meal_benefit_id'),
                'MealBenefit.name'
            ])
            ->where($conditions)
            ->first();
        if (empty($mealReceived)) {
            $isMarkedRecords = $StudentMealMarkedRecords
                ->find()
                ->contain(['MealBenefit'])
                ->select([
                    $StudentMealMarkedRecords->aliasField('meal_benefit_id'),
                    'MealBenefit.name'
                ])
                ->where([
                    $StudentMealMarkedRecords->aliasField('academic_period_id = ') => $entity->academic_period_id,
                    $StudentMealMarkedRecords->aliasField('institution_class_id = ') => $entity->institution_class_id,
                    $StudentMealMarkedRecords->aliasField('date = ') => $entity->institution_student_meal['date'],
                    $StudentMealMarkedRecords->aliasField('institution_id = ') => $entity->institution_id,
                ])
                ->first();
            //START:POCOR-6681
            // if (empty($mealReceived) && empty($isMarkedRecords)) {
            if (empty($mealReceived)) {
                $mealReceived = "None";
            } else {
                $mealReceived = "Received";
            }
            //END:POCOR-6681
        } else {
            $mealReceived = $mealReceived->meal_received->name;
        }
        return $mealReceived;
    }


    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        ini_set("memory_limit", "-1");

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $classId = !empty($this->request->query['institution_class_id']) ? $this->request->query['institution_class_id'] : 0;
        $weekId = $this->request->query['week_id'];
        $weekStartDay = $this->request->query['week_start_day'];
        $weekEndDay = $this->request->query['week_end_day'];
        $dayId = $this->request->query['day_id'];

        $InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');


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

    public function onExcelGetName(Event $event, Entity $entity)
    {

        $fname = ($entity->user->first_name != null) ? $entity->user->first_name : '';
        $Mname = ($entity->user->middle_name != null) ? $entity->user->middle_name : '';
        $Tname = ($entity->user->third_name != null) ? $entity->user->third_name : '';
        $Lname = ($entity->user->last_name != null) ? $entity->user->last_name : '';
        $fullname = $fname . " " . $Mname . " " . $Tname . " " . $Lname;
        return $fullname;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $day_id = $this->request->query('day_id');
        $newArray[] = [
            'key' => 'StudentMeals.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID'
        ];

        $newArray[] = [
            'key' => 'StudentMeals.name',
            'field' => 'name',
            'type' => 'string',
            'label' => 'Full Name'
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
        $filter_key = array('StudentMeals.id', 'StudentMeals.student_id', 'StudentMeals.institution_class_id', 'StudentMeals.academic_period_id', 'StudentMeals.student_status_id', 'InstitutionMealStudents.meal_benefit');

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

    /**
     * POCOR-7908
     * @param Query $query
     * @param $academicPeriodId
     * @param $institutionId
     * @param $institutionClassId
     * @param null $studentID
     * @param null $ID
     * @return array|Query
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getMealsMainQuery(Query $query,
                                       $academicPeriodId,
                                       $institutionId,
                                       $institutionClassId,
                                       $studentID = null,
                                       $ID=null)
    {
        $where = [
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('institution_class_id') => $institutionClassId,
            $this->aliasField('institution_id') => $institutionId
        ];
        if (!empty($studentID)) {
            if (intval($studentID) > 0) {
                $where[$this->aliasField('student_id')] = $studentID;
            }
        }
        if (!empty($ID)) {
            if (intval($ID) > 0) {
                $where[$this->aliasField('id')] = $ID;
            }
        }
        $query = $query
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
            ->matching($this->StudentStatuses->alias(), function ($q) {
                return $q->where([
                    $this->StudentStatuses->aliasField('code') => 'CURRENT'
                ]);
            })
            ->where($where)
            ->order([
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('last_name')
            ]);
        return $query;
    }

    /**
     * POCOR-7908
     * @param Query $query
     * @param $default_meal_receive_id
     * @return mixed
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getDailyMealData(Query $query, $default_meal_receive_id)
    {
        $query = $query->formatResults(function (ResultSetInterface $results) use (
            $default_meal_receive_id
        ) {
            return $results->map(function ($row) use (
                $default_meal_receive_id
            ) {
                $row->default_meal_receive_id = $default_meal_receive_id;
                return $row;
            });
        });
        return $query;
    }

    /**
     * POCOR-7908
     * @return mixed
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getDefaultMealReceiveID()
    {
        $ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
        $configItemData = $ConfigItemsTable->find('all', ['conditions' => ['code' => 'DefaultDeliveryStatus']])->first();
        $DefaultDeliveryStatus = $configItemData->value;
        $MealReceivedTable = TableRegistry::get('Meal.MealReceived');
        $mealReceivedData = $MealReceivedTable->find('all')->where(['name' => $DefaultDeliveryStatus])->first();
        $default_meal_receive_id = $mealReceivedData->id;
        return $default_meal_receive_id;
    }

    /**
     * POCOR-7908
     * @param $institutionId
     * @param $institutionClassId
     * @param $mealProgramId
     * @param $academicPeriodId
     * @param $day
     * @param \Cake\ORM\Table $StudentMealMarkedRecords
     * @return bool|\Cake\Datasource\EntityInterface|mixed
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function markDay($institutionId, $institutionClassId, $mealProgramId, $academicPeriodId, $day, \Cake\ORM\Table $StudentMealMarkedRecords)
    {
        $data = ['institution_id' => $institutionId,
            'institution_class_id' => $institutionClassId,
            'meal_programmes_id' => $mealProgramId,
            'academic_period_id' => $academicPeriodId,
            'date' => $day,
            'meal_benefit_id' => null];
        $entity = $StudentMealMarkedRecords->newEntity($data);
        $result = $StudentMealMarkedRecords->save($entity);
        return $result;
    }

}
