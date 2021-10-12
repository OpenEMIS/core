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
use App\Model\Table\ControllerActionTable;

class InfrastructureWashSanitationsTable extends ControllerActionTable {

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

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
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
            ->allowEmpty('infrastructure_wash_sanitation_male_functional')
            ->add('infrastructure_wash_sanitation_male_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_sanitation_male_nonfunctional')
            ->add('infrastructure_wash_sanitation_female_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_sanitation_female_functional')
            ->add('infrastructure_wash_sanitation_female_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_sanitation_female_nonfunctional')
            ->add('infrastructure_wash_sanitation_mixed_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_sanitation_mixed_functional')
            ->add('infrastructure_wash_sanitation_mixed_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_sanitation_mixed_nonfunctional')
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashSanitations';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('infrastructure_wash_sanitation_type_id');
        $this->field('infrastructure_wash_sanitation_use_id');
        $this->field('infrastructure_wash_sanitation_total_male');
        $this->field('infrastructure_wash_sanitation_total_female');
        $this->field('infrastructure_wash_sanitation_total_mixed');
        $this->field('infrastructure_wash_sanitation_quality_id');
        $this->field('infrastructure_wash_sanitation_accessibility_id');
        $this->field('academic_period_id', ['visible' => false]);


        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Risks/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'infrastructure_wash_sanitation_type_id':
                return __('Type');
            case 'infrastructure_wash_sanitation_use_id':
                return __('Use');
            case 'infrastructure_wash_sanitation_total_male':
                return __('Total Male');
            case 'infrastructure_wash_sanitation_total_female':
                return __('Total Female');
            case 'infrastructure_wash_sanitation_total_mixed':
                return __('Total Mixed');
            case 'infrastructure_wash_sanitation_quality_id':
                return __('Quality');
            case 'infrastructure_wash_sanitation_accessibility_id':
                return __('Accessibility');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
        ->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $SanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->fields['infrastructure_wash_sanitation_type_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['infrastructure_wash_sanitation_use_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_use_id', ['attr' => ['label' => __('Use')]]);

        $this->field('infrastructure_wash_sanitation_male_functional', ['type' => 'integer','attr' => ['label' => __('Male (Functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_sanitation_male_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Male (Non-functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_sanitation_female_functional', ['type' => 'integer','attr' => ['label' => __('Female (Functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_sanitation_female_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Female (Non-functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_sanitation_mixed_functional', ['type' => 'integer','attr' => ['label' => __('Mixed (Functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_sanitation_mixed_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Mixed (Non-functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_sanitation_total_male', ['visible' => false]);
        $this->field('infrastructure_wash_sanitation_total_female', ['visible' => false]);
        $this->field('infrastructure_wash_sanitation_total_mixed', ['visible' => false]);

        $this->fields['infrastructure_wash_sanitation_quality_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_quality_id', ['attr' => ['label' => __('Quality')]]);

        $this->fields['infrastructure_wash_sanitation_accessibility_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_accessibility_id', ['attr' => ['label' => __('Accessibility')]]);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra){

        $Data = $this->getData();
        $quantity = $this->getSanitationQuantity($Data);
        $this->field('infrastructure_wash_sanitation_total_male', ['visible' => false]);
        $this->field('infrastructure_wash_sanitation_total_female', ['visible' => false]);
        $this->field('infrastructure_wash_sanitation_total_mixed', ['visible' => false]);
        //$this->fields['quantities']['type'] = 'table';
        $this->field('academic_period_id');
        $this->field('infrastructure_wash_sanitation_type_id');
        $this->field('infrastructure_wash_sanitation_use_id');
        $this->field('quantities', [
            'type' => 'table',
            'headers' => [__('Gender'), __('Functional'),__('Non-functional')],
            'cells' => $quantity,
            'attr' => ['label' =>  __('Quantity')]
        ]);

    }

    public function getData(){
        $InfrastructureWashSanitationQuantities = TableRegistry::get('InfrastructureWashSanitationQuantities');
        $sanatationQuantitiesIdArr = $this->paramsDecode($this->request->params['pass'][1]);
        $sanatationId = $sanatationQuantitiesIdArr['id'];
        $sanitationQualitiesData = $InfrastructureWashSanitationQuantities->find()
        ->select([
            'gender_id' => 'gender_id',
            'functional' => 'functional',
            'value' => 'value'
        ])
        ->where([
            $InfrastructureWashSanitationQuantities->aliasField('infrastructure_wash_sanitation_id = ').$sanatationId
        ])
       ->toArray();
        return $sanitationQualitiesData;
    }

    private function getSanitationQuantity($entity)
    {
        $rows = [];
        foreach ($entity as $obj) {
            if ($obj['gender_id'] == 1 && $obj['functional'] == 1 ) {
                $male_functional = $obj['value'];
            }
            elseif ($obj['gender_id'] == 1 && $obj['functional'] == 0 ) {
                $male_nonfunctional = $obj['value'];
            }
            elseif ($obj['gender_id'] == 2 && $obj['functional'] == 1 ) {
                $female_functional = $obj['value'];
            }
            if ($obj['gender_id'] == 2 && $obj['functional'] == 0 ) {
                $female_nonfunctional = $obj['value'];
            }
            if ($obj['gender_id'] == 3 && $obj['functional'] == 1 ) {
                $mixed_functional = $obj['value'];
            }
            if ($obj['gender_id'] == 3 && $obj['functional'] == 0 ) {
                $mixed_nonfunctional = $obj['value'];
            }
        }

        $rows[] = ['gender' => 'Male', 'functional' => $male_functional, 'nonfunctional' => $male_nonfunctional];
        $rows[] = ['gender' => 'Female', 'functional' => $female_functional, 'nonfunctional' => $female_nonfunctional];
        $rows[] = ['gender' => 'Mixed', 'functional' => $mixed_functional, 'nonfunctional' => $mixed_nonfunctional];
        return $rows;
    }

    // POCOR-6146 start
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            "key" => "InfrastructureWashSanitations.infrastructure_wash_sanitation_type_id",
            "field" => "infrastructure_wash_sanitation_type_id",
            "type" => "integer",
            "label" => "Type"
        ];

        $extraField[] = [
            "key" => "InfrastructureWashSanitations.infrastructure_wash_sanitation_use_id",
            "field" => "infrastructure_wash_sanitation_use_id",
            "type" => "integer",
            "label" => "Use"
        ];

        $extraField[] = [
            "key" => "InfrastructureWashSanitations.infrastructure_wash_sanitation_total_male",
            "field" => "infrastructure_wash_sanitation_total_male",
            "type" => "integer",
            "label" => "Total Male"
        ];

        $extraField[] = [
            "key" => "InfrastructureWashSanitations.infrastructure_wash_sanitation_total_female",
            "field" => "infrastructure_wash_sanitation_total_female",
            "type" => "integer",
            "label" => "Total Female"
        ];

        $extraField[] = [
            "key" => "InfrastructureWashSanitations.infrastructure_wash_sanitation_total_mixed",
            "field" => "infrastructure_wash_sanitation_total_mixed",
            "type" => "integer",
            "label" => "Total Mixed"
        ];
        
        $extraField[] = [
            "key" => "InfrastructureWashSanitations.infrastructure_wash_sanitation_quality_id",
            "field" => "infrastructure_wash_sanitation_quality_id",
            "type" => "integer",
            "label" => "Quality"
        ];

        $extraField[] = [
            "key" => "InfrastructureWashSanitations.infrastructure_wash_sanitation_accessibility_id",
            "field" => "infrastructure_wash_sanitation_accessibility_id",
            "type" => "integer",
            "label" => "Accessibility"
        ];

        $fields->exchangeArray($extraField);
    }
    // POCOR-6146 start

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $query
        ->Where([
            $this->aliasField('institution_id = ').$institutionId,
            $this->aliasField('academic_period_id = ').$selectedAcademicPeriod
        ]);
    }

}
