<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\I18n\Time;

class InstitutionMealStudentsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_meal_students');
        parent::initialize($config); 
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);    
        $this->belongsTo('MealBenefit', ['className' => 'Meal.MealBenefits', 'foreignKey' =>'meal_benefit_id']); 
        $this->belongsTo('MealReceived', ['className' => 'Meal.MealReceived', 'foreignKey' =>'meal_received_id']);
        $this->belongsTo('MealProgrammes', ['className' => 'Meal.MealProgrammes', 'foreignKey' =>'meal_programmes_id']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view', 'add']
        ]);
    }

     public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
    	$InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
        $MealBenefit = TableRegistry::get('Meal.MealBenefit');
    	$classId = $entity->institution_class_id;
        $academicPeriodId = $entity->academic_period_id;
        $mealProgrammesId = $entity->meal_programmes_id;
        $date = $entity->date;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $benefitTypeId = $entity->meal_benefit_id;
        $paid = $entity->paid;
        $mealReceived = $entity->meal_received_id;

    	$conditions = [
	        $InstitutionMealStudents->aliasField('academic_period_id = ') => $academicPeriodId,
	        $InstitutionMealStudents->aliasField('institution_class_id = ') => $classId,
	        $InstitutionMealStudents->aliasField('student_id = ') => $studentId,
	        $InstitutionMealStudents->aliasField('institution_id = ') => $institutionId,
	        $InstitutionMealStudents->aliasField('meal_programmes_id = ') => $mealProgrammesId,
	        $InstitutionMealStudents->aliasField('date = ') => $date,
        ];

        $data = $InstitutionMealStudents
        ->find()
        ->where($conditions)
        ->all();
       
        if (!$data->isEmpty()) {
        	$mealEntity = $data->first();

            if ($mealReceived == "2" || $mealReceived == "3") {
                 $data = $InstitutionMealStudents
                ->updateAll(['meal_benefit_id' => NULL,'meal_received_id' => $mealReceived],['id' => $mealEntity->id]);
                $event->stopPropagation();
                 return $data;
            }
            //START:POCOR-6681
            // if ($mealReceived == "1" && empty($benefitTypeId)) {
            if ($mealReceived == "1") {
                if(!isset($benefitTypeId) || empty($benefitTypeId) || $benefitTypeId == null){
                    $MealBenefitData = $MealBenefit->find()->where([
                        'default' => 1
                    ])->first();
                    $MealbenifitId = $MealBenefitData->id;
                }else{
                    $MealbenifitId = $benefitTypeId;
                }
            //END:POCOR-6681
                $InstitutionMealStudents
                ->updateAll(['meal_benefit_id' => $MealbenifitId ,'meal_received_id' => $mealReceived],['id' => $mealEntity->id]);
                $event->stopPropagation();
                 return;
            }
            else{
                if(!isset($benefitTypeId) || empty($benefitTypeId) || $benefitTypeId == null){
                    $MealBenefitData = $MealBenefit->find()->where([
                        'default' => 1
                    ])->first();
                    $MealbenifitId = $MealBenefitData->id;
                }else{
                    $MealbenifitId = $benefitTypeId;
                }
                 $InstitutionMealStudents
                ->updateAll(['meal_benefit_id' => $MealbenifitId,'paid' => $paid,'meal_received_id' => $mealReceived],['id' => $mealEntity->id]);
                $event->stopPropagation();
                 return;
            }
        	        	
             
                
            } else {
                $entity->meal_received_id = $mealReceived;
                $mealEntity = $InstitutionMealStudents->newEntity();
            }

       
    }


}
