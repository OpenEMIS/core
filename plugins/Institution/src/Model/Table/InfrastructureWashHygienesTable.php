<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Validation\Validator;

class InfrastructureWashHygienesTable extends AppTable {

    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_hygienes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashHygieneTypes',   ['className' => 'Institution.InfrastructureWashHygieneTypes', 'foreign_key' => 'infrastructure_wash_hygiene_type_id']);
        $this->belongsTo('InfrastructureWashHygieneSoapashAvailabilities',   ['className' => 'Institution.InfrastructureWashHygieneSoapashAvailabilities', 'foreign_key' => 'infrastructure_wash_hygiene_use_id']);
        $this->belongsTo('InfrastructureWashHygieneEducations',   ['className' => 'Institution.InfrastructureWashHygieneEducations', 'foreign_key' => 'infrastructure_wash_hygiene_education_id']);
        $this->hasMany('InfrastructureWashHygieneQuantities', ['className' => 'Institution.InfrastructureWashHygieneQuantities', 'foreign_key' => 'infrastructure_wash_hygiene_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        
        $validator
            ->add('infrastructure_wash_hygiene_male_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_hygiene_male_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_hygiene_female_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_hygiene_female_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_hygiene_mixed_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_hygiene_mixed_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ;

        return $validator;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $total_male = $entity->infrastructure_wash_hygiene_male_functional + $entity->infrastructure_wash_hygiene_male_nonfunctional;
        $total_female = $entity->infrastructure_wash_hygiene_female_functional + $entity->infrastructure_wash_hygiene_female_nonfunctional;
        $total_mixed = $entity->infrastructure_wash_hygiene_mixed_functional + $entity->infrastructure_wash_hygiene_mixed_nonfunctional;
        
        $entity->infrastructure_wash_hygiene_total_male = $total_male;
        $entity->infrastructure_wash_hygiene_total_female = $total_female;
        $entity->infrastructure_wash_hygiene_total_mixed = $total_mixed;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $HygieneQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashHygieneQuantities');
        $HygieneQuantitiesTable->deleteAll(['infrastructure_wash_hygiene_id' => $entity->id]);    
        
        $data1 = $HygieneQuantitiesTable->newEntity();
        $data1->gender_id = 1;
        $data1->functional = 1;
        $data1->value = $entity->infrastructure_wash_hygiene_male_functional;
        $data1->infrastructure_wash_hygiene_id = $entity->id;
        $HygieneQuantitiesTable->save($data1);

        $data2 = $HygieneQuantitiesTable->newEntity();
        $data2->gender_id = 1;
        $data2->functional = 0;
        $data2->value = $entity->infrastructure_wash_hygiene_male_nonfunctional;
        $data2->infrastructure_wash_hygiene_id = $entity->id;
        $HygieneQuantitiesTable->save($data2);

        $data3 = $HygieneQuantitiesTable->newEntity();
        $data3->gender_id = 2;
        $data3->functional = 1;
        $data3->value = $entity->infrastructure_wash_hygiene_female_functional;
        $data3->infrastructure_wash_hygiene_id = $entity->id;
        $HygieneQuantitiesTable->save($data3);

        $data4 = $HygieneQuantitiesTable->newEntity();
        $data4->gender_id = 2;
        $data4->functional = 0;
        $data4->value = $entity->infrastructure_wash_hygiene_female_nonfunctional;
        $data4->infrastructure_wash_hygiene_id = $entity->id;
        $HygieneQuantitiesTable->save($data4);

        $data5 = $HygieneQuantitiesTable->newEntity();
        $data5->gender_id = 3;
        $data5->functional = 1;
        $data5->value = $entity->infrastructure_wash_hygiene_mixed_functional;
        $data5->infrastructure_wash_hygiene_id = $entity->id;
        $HygieneQuantitiesTable->save($data5);

        $data6 = $HygieneQuantitiesTable->newEntity();
        $data6->gender_id = 3;
        $data6->functional = 0;
        $data6->value = $entity->infrastructure_wash_hygiene_mixed_nonfunctional;
        $data6->infrastructure_wash_hygiene_id = $entity->id;
        $HygieneQuantitiesTable->save($data6);
    }

    public function findView(Query $query, array $options)
    {
        $query->contain(['InfrastructureWashHygieneQuantities']);
        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['InfrastructureWashHygieneQuantities']);
        return $query;
    }
}
