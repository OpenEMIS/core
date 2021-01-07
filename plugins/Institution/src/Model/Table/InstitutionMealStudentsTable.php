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
        $this->belongsTo('MealBenefit', ['className' => 'Meal.MealBenefits', 'foreignKey' =>'benefit_type_id']); 

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view', 'add']
        ]);
    }

     public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
    	$InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
    	$classId = $entity->institution_class_id;
        $academicPeriodId = $entity->academic_period_id;
        $mealProgrammesId = $entity->meal_programmes_id;
        $date = $entity->date;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $benefitTypeId = $entity->benefit_type_id;
        $paid = $entity->paid;

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
        	        	
              $InstitutionMealStudents
                ->updateAll(['benefit_type_id' => $benefitTypeId,'paid' => $paid],['id' => $mealEntity->id]);
                $event->stopPropagation();
       			 return;
                
            } else {
                $mealEntity = $InstitutionMealStudents->newEntity();
            }

       
    }


}
