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
        //POCOR-7908:start
        $meal_received_id = $entity->meal_received_id;
        $meal_benefit_id = $entity->meal_benefit_id;
        if($meal_received_id > 1){
            //do nothing if you do nothing or get nothing
            $entity->meal_benefit_id = null;
            return;
        }
        if ($meal_received_id == 1) {
            if(!isset($meal_benefit_id) || empty($meal_benefit_id) || $meal_benefit_id == null){
                $MealBenefit = TableRegistry::get('Meal.MealBenefit');
                $MealBenefitData = $MealBenefit->find()->where([
                    'default' => 1
                ])->first();
                $MealbenifitId = $MealBenefitData->id;
            }else{
                $MealbenifitId = $meal_benefit_id;
            }
            //END:POCOR-6681
            $entity->meal_benefit_id = $MealbenifitId;
            return;
        }
        //POCOR-7908 the flow does not depend on the meals provided
//        if(isset($only_change_benefit)){
//            //do nothing if you do nothing or get nothing
//            return;
//        }
        //POCOR-7908:end
    	$InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
        $institution_meal_programmes = TableRegistry::get('institution_meal_programmes');
        $MealBenefit = TableRegistry::get('Meal.MealBenefit');
    	$classId = $entity->institution_class_id;
        $academicPeriodId = $entity->academic_period_id;
        $mealProgrammesId = $entity->meal_programmes_id;
        $date = $entity->date;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $meal_benefit_id = $entity->meal_benefit_id;
        $paid = $entity->paid;

        //POCOR-6959
        $institution_meal_programmes_data = $institution_meal_programmes->find()
                                ->where([
                                    $institution_meal_programmes->aliasField('academic_period_id') => $entity->academic_period_id,
                                    $institution_meal_programmes->aliasField('institution_id') => $institutionId,
                                    $institution_meal_programmes->aliasField('meal_programmes_id') => $mealProgrammesId,
                                    $institution_meal_programmes->aliasField('date_received') => $date
                                    ])->first();
        if(!empty($institution_meal_programmes_data)){
            $InstitutionMealStudentsData = $this->find('all')
                                                ->where([
                                                    $this->aliasField('academic_period_id') => $entity->academic_period_id,
                                                    $this->aliasField('institution_id') => $institutionId,
                                                    $this->aliasField('meal_programmes_id') => $mealProgrammesId,
                                                    $this->aliasField('date') => $date,
                                                    $this->aliasField('meal_received_id') => $meal_received_id //POCOR-7908
                                                    ])->toArray();
    
            $institution_meal_programmes_data = $institution_meal_programmes->find()
                                ->where([
                                    $institution_meal_programmes->aliasField('academic_period_id') => $entity->academic_period_id,
                                    $institution_meal_programmes->aliasField('institution_id') => $institutionId,
                                    $institution_meal_programmes->aliasField('meal_programmes_id') => $mealProgrammesId,
                                    $institution_meal_programmes->aliasField('date_received') => $date
                                    ])->first();
            if(count($InstitutionMealStudentsData) >= $institution_meal_programmes_data->quantity_received){
                //POCOR-7908:start
                $data = ['error' => 'Count of provided meals is less then count of students'];
                echo json_encode($data);
                die;
                //POCOR-7908:end
            }else{
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
        
                    if ($meal_received_id == "2" || $meal_received_id == "3") {
                        //POCOR-7908:start
                         $data = $InstitutionMealStudents
                        ->updateAll([
                            'meal_benefit_id' => NULL,
                            'meal_received_id' => $meal_received_id],
                            ['id' => $mealEntity->id]);
                        //POCOR-7908:end
                        $event->stopPropagation();
                         return $data;
                    }
                    //START:POCOR-6681
                    // if ($mealReceived == "1" && empty($benefitTypeId)) {
                    if ($meal_received_id == "1") {
                        if(!isset($meal_benefit_id) || empty($meal_benefit_id) || $meal_benefit_id == null){
                            $MealBenefitData = $MealBenefit->find()->where([
                                'default' => 1
                            ])->first();
                            $MealbenifitId = $MealBenefitData->id;
                        }else{
                            $MealbenifitId = $meal_benefit_id;
                        }
                    //END:POCOR-6681
                        $InstitutionMealStudents
                        ->updateAll(['meal_benefit_id' => $MealbenifitId,
                            'meal_received_id' => $meal_received_id],
                            ['id' => $mealEntity->id]);
                        $event->stopPropagation();
                         return;
                    }
                    else{
                        if(!isset($meal_benefit_id) || empty($meal_benefit_id) || $meal_benefit_id == null){
                            $MealBenefitData = $MealBenefit->find()->where([
                                'default' => 1
                            ])->first();
                            $MealbenifitId = $MealBenefitData->id;
                        }else{
                            $MealbenifitId = $meal_benefit_id;
                        }
                         $InstitutionMealStudents
                        ->updateAll(['meal_benefit_id' => $meal_benefit_id,'paid' => $paid,'meal_received_id' => $meal_received_id],['id' => $mealEntity->id]);
                        $event->stopPropagation();
                         return;
                    }
                    } else {
                        if(!isset($meal_benefit_id) || empty($meal_benefit_id) || $meal_benefit_id == null){
                            $MealBenefitData = $MealBenefit->find()->where([
                                'default' => 1
                            ])->first();
                            $MealbenifitId = $MealBenefitData->id;
                        }else{
                            $MealbenifitId = $meal_benefit_id;
                        }
                        $entity->meal_received_id = $meal_received_id;
                        $entity->meal_benefit_id =  $MealbenifitId;
                        $mealEntity = $InstitutionMealStudents->newEntity();
                    }
            }
        }else{
            //POCOR-7908:start
            $data = ['error' => 'No meals provided for this day!'];
            echo json_encode($data);
            die;
            //POCOR-7908:end
        }
    }


}
