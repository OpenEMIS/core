<?php

namespace Meal\Model\Table;

use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Date;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;

class MealInstitutionProgrammesTable extends ControllerActionTable
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('MealProgrammeTypes', ['className' => 'Meal.MealProgrammeTypes', 'foreignKey' => 'type']);
        $this->belongsTo('MealTargetTypes', ['className' => 'Meal.MealTargetTypes', 'foreignKey' => 'targeting']);
        $this->belongsTo('MealImplementers', ['className' => 'Meal.MealImplementers', 'foreignKey' => 'implementer']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
        $this->belongsToMany('MealNutritions', [
            'className' => 'Meal.MealNutritions',
            'joinTable' => 'meal_nutritional_records',
            'foreignKey' => 'meal_programme_id',
            'targetForeignKey' => 'nutritional_content_id',
            'through' => 'Meal.MealNutritionalRecords',
            'dependent' => true
        ]);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

    }

    public function findMealInstitutionProgrammes(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        //SATRT: POCOR-6609
        // $academic_year =  explode("-", $options['academic_period_id']);
        // $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        // $academicPeriodId = $AcademicPeriods
        //             ->find()
        //             ->where([
        //                 $AcademicPeriods->aliasField('start_year') => $academic_year[0]
        //             ])
        //             ->extract('id')
        //             ->first();
        if (empty($options['academic_period_id'])) {
            // $academic_period_id = $this->AcademicPeriods->getCurrent();
            $arrayStudent = $this->AcademicPeriods->find()
                ->matching('InstitutionClasses')
                ->where([
                    'InstitutionClasses.institution_id' => $institutionId
                ])
                ->extract('id')
                ->toArray();
            $academic_period_id = end($arrayStudent);
        } else {
            $academic_period_id = $options['academic_period_id'];
            // $academic_year =  explode("-", $options['academic_period_id']);
            // $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            // $academicPeriodId = $AcademicPeriods
            //         ->find()
            //         ->where([
            //             $AcademicPeriods->aliasField('start_year') => $academic_year[0]
            //         ])
            //         ->extract('id')
            //         ->first();
            // // echo "<pre>";print_r($academicPeriodId);die;
            // $academic_period_id = $academicPeriodId;
        }
        //END: POCOR-6609
        $MealProgrammes = TableRegistry::get('Meal.MealProgrammes');
        $query
            ->select([
                $this->aliasField('meal_programme_id'),
                'name' => $MealProgrammes->aliasField('name'),
                'id' => $MealProgrammes->aliasField('id')
            ])
            ->innerJoin(
                [$MealProgrammes->alias() => $MealProgrammes->table()], [
                    $this->aliasField('meal_programme_id = ') . $MealProgrammes->aliasField('id')
                ]
            )
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $MealProgrammes->aliasField('academic_period_id') => $academic_period_id,
            ]);
        // echo "<pre>";print($query->toArray());die;
        // $row = $query->toArray();

        // $results = $MealProgrammes
        //             ->find()
        //             ->where([
        //                 $MealProgrammes->aliasField('id') => $row[0]->meal_programmes_id
        //             ])
        //             ->first();
        // $results = $results->toArray();
    }

    /**
     * POCOR-7908
     * @param Query $query
     * @param array $options
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function findMealInstitutionPrograms(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $MealPrograms = TableRegistry::get('Meal.MealProgrammes');
        $query
            ->select([
                $this->aliasField('meal_programme_id'),
                'name' => $MealPrograms->aliasField('name'),
                'id' => $MealPrograms->aliasField('id')
            ])
            ->innerJoin(
                [$MealPrograms->alias() => $MealPrograms->table()], [
                    $this->aliasField('meal_programme_id = ') . $MealPrograms->aliasField('id')
                ]
            )
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $MealPrograms->aliasField('academic_period_id') => $academicPeriodId,
            ])
                ->orderAsc($MealPrograms->aliasField('code'));
    }

    public function findMealReceivedOptions(Query $query, array $options)
    {

        $receiveOptions = [
            ['id' => 1, 'name' => 'ok', 'default' => false],
            ['id' => 2, 'name' => 'nope', 'default' => true],
            ['id' => 3, 'name' => 'hope', 'default' => false],
            ];
        $query
            ->select(['id'])
            ->limit(1)
            ->formatResults(function (ResultSetInterface $results) use ($receiveOptions) {
                return $receiveOptions;
            });
    }

}
