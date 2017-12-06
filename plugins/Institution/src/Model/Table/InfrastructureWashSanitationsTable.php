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

class InfrastructureWashSanitationsTable extends AppTable {

    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_sanitations');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashSanitationTypes',   ['className' => 'Institution.InfrastructureWashSanitationTypes', 'foreign_key' => 'infrastructure_wash_sanitation_type_id']);
        $this->belongsTo('InfrastructureWashSanitationUses',   ['className' => 'Institution.InfrastructureWashSanitationUses', 'foreign_key' => 'infrastructure_wash_sanitation_use_id']);
        $this->belongsTo('InfrastructureWashSanitationQualities',   ['className' => 'Institution.InfrastructureWashSanitationQualities', 'foreign_key' => 'infrastructure_wash_sanitation_quality_id']);
        $this->belongsTo('InfrastructureWashSanitationAccessibilities',   ['className' => 'Institution.InfrastructureWashSanitationAccessibilities', 'foreign_key' => 'infrastructure_wash_sanitation_accessibility_id']);
        $this->hasMany('InfrastructureWashSanitationQuantities', ['className' => 'Institution.InfrastructureWashSanitationQuantities', 'foreign_key' => 'infrastructure_wash_sanitation_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        
        $validator
            ->add('infrastructure_wash_sanitation_male_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_sanitation_male_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_sanitation_female_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_sanitation_female_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_sanitation_mixed_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->add('infrastructure_wash_sanitation_mixed_nonfunctional', [
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
        $total_male = $entity->infrastructure_wash_sanitation_male_functional + $entity->infrastructure_wash_sanitation_male_nonfunctional;
        $total_female = $entity->infrastructure_wash_sanitation_female_functional + $entity->infrastructure_wash_sanitation_female_nonfunctional;
        $total_mixed = $entity->infrastructure_wash_sanitation_mixed_functional + $entity->infrastructure_wash_sanitation_mixed_nonfunctional;
        
        $entity->infrastructure_wash_sanitation_total_male = $total_male;
        $entity->infrastructure_wash_sanitation_total_female = $total_female;
        $entity->infrastructure_wash_sanitation_total_mixed = $total_mixed;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $SanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
        $SanitationQuantitiesTable->deleteAll(['infrastructure_wash_sanitation_id' => $entity->id]);    
        
        $data1 = $SanitationQuantitiesTable->newEntity();
        $data1->gender_id = 1;
        $data1->functional = 1;
        $data1->value = $entity->infrastructure_wash_sanitation_male_functional;
        $data1->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data1);

        $data2 = $SanitationQuantitiesTable->newEntity();
        $data2->gender_id = 1;
        $data2->functional = 0;
        $data2->value = $entity->infrastructure_wash_sanitation_male_nonfunctional;
        $data2->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data2);

        $data3 = $SanitationQuantitiesTable->newEntity();
        $data3->gender_id = 2;
        $data3->functional = 1;
        $data3->value = $entity->infrastructure_wash_sanitation_female_functional;
        $data3->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data3);

        $data4 = $SanitationQuantitiesTable->newEntity();
        $data4->gender_id = 2;
        $data4->functional = 0;
        $data4->value = $entity->infrastructure_wash_sanitation_female_nonfunctional;
        $data4->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data4);

        $data5 = $SanitationQuantitiesTable->newEntity();
        $data5->gender_id = 3;
        $data5->functional = 1;
        $data5->value = $entity->infrastructure_wash_sanitation_mixed_functional;
        $data5->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data5);

        $data6 = $SanitationQuantitiesTable->newEntity();
        $data6->gender_id = 3;
        $data6->functional = 0;
        $data6->value = $entity->infrastructure_wash_sanitation_mixed_nonfunctional;
        $data6->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data6);
    }

    public function findView(Query $query, array $options)
    {
        $query->contain(['InfrastructureWashSanitationQuantities']);
        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['InfrastructureWashSanitationQuantities']);
        return $query;
    }
}
