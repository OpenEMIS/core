<?php
namespace Meal\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class StudentMealMarkedRecordsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('student_meal_marked_records');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('MealBenefit', ['className' => 'Meal.MealBenefits', 'foreignKey' =>'meal_benefit_id']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'add', 'edit']
        ]);
    }

    public function findMealIsMarked(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['meal_programmes_id'];        
        $day = $options['day_id'];

        return $query
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('meal_programmes_id') => $educationGradeId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('date') => $day,
            ]);
            
    }

    public function afterSaveCommit(Event $event, Entity $entity)
    {
        $MealRecords = TableRegistry::get('student_meal_marked_records');
        $MealRecords->dispatchEvent('Model.StudentMeals.afterSaveCommit', [$entity], $MealRecords);
    }
}
