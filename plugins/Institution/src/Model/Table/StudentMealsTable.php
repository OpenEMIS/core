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
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
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
            return $results->map(function ($row) {
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
    
}
