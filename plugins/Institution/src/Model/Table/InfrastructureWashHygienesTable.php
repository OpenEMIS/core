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

class InfrastructureWashHygienesTable extends ControllerActionTable {

    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_hygienes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashHygieneTypes',   ['className' => 'Institution.InfrastructureWashHygieneTypes', 'foreign_key' => 'infrastructure_wash_hygiene_type_id']);
        $this->belongsTo('InfrastructureWashHygieneSoapashAvailabilities',   ['className' => 'Institution.InfrastructureWashHygieneSoapashAvailabilities', 'foreign_key' => 'infrastructure_wash_hygiene_use_id']);
        $this->belongsTo('InfrastructureWashHygieneEducations',   ['className' => 'Institution.InfrastructureWashHygieneEducations', 'foreign_key' => 'infrastructure_wash_hygiene_education_id']);
        $this->hasMany('InfrastructureWashHygieneQuantities', ['className' => 'Institution.InfrastructureWashHygieneQuantities', 'foreign_key' => 'infrastructure_wash_hygiene_id', 'dependent' => true, 'cascadeCallbacks' => true]);

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
            ->add('infrastructure_wash_hygiene_male_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_hygiene_male_functional')
            ->add('infrastructure_wash_hygiene_male_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_hygiene_male_nonfunctional')
            ->add('infrastructure_wash_hygiene_female_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_hygiene_female_functional')
            ->add('infrastructure_wash_hygiene_female_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_hygiene_female_nonfunctional')
            ->add('infrastructure_wash_hygiene_mixed_functional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_hygiene_mixed_functional')
            ->add('infrastructure_wash_hygiene_mixed_nonfunctional', [
                'rulePositive' => [
                    'rule' => ['naturalNumber', true],
                    'message' => 'This field must be a positive number'
                ]
            ])
            ->allowEmpty('infrastructure_wash_hygiene_mixed_nonfunctional')
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashHygienes';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('infrastructure_wash_hygiene_type_id');
        $this->field('infrastructure_wash_hygiene_soapash_availability_id');
        $this->field('infrastructure_wash_hygiene_education_id');
        $this->field('infrastructure_wash_hygiene_total_male');
        $this->field('infrastructure_wash_hygiene_total_female');
        $this->field('infrastructure_wash_hygiene_total_mixed');
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
            case 'infrastructure_wash_hygiene_type_id':
                return __('Type');
            case 'infrastructure_wash_hygiene_soapash_availability_id':
                return __('Soap/Ash Availability');
            case 'infrastructure_wash_hygiene_education_id':
                return __('Hygiene Education');
            case 'infrastructure_wash_hygiene_total_male':
                return __('Total Male');
            case 'infrastructure_wash_hygiene_total_female':
                return __('Total Female');
            case 'infrastructure_wash_hygiene_total_mixed':
                return __('Total Mixed');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $SanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->fields['infrastructure_wash_hygiene_type_id']['type'] = 'select';
        $this->field('infrastructure_wash_hygiene_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['infrastructure_wash_hygiene_soapash_availability_id']['type'] = 'select';
        $this->field('infrastructure_wash_hygiene_soapash_availability_id', ['attr' => ['label' => __('Soap/Ash Availability')]]);

        $this->fields['infrastructure_wash_hygiene_education_id']['type'] = 'select';
        $this->field('infrastructure_wash_hygiene_education_id', ['attr' => ['label' => __('Hygiene Education')]]);

        $this->field('infrastructure_wash_hygiene_male_functional', ['type' => 'integer','attr' => ['label' => __('Male (Functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_hygiene_male_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Male (Non-functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_hygiene_female_functional', ['type' => 'integer','attr' => ['label' => __('Female (Functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_hygiene_female_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Female (Non-functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_hygiene_mixed_functional', ['type' => 'integer','attr' => ['label' => __('Mixed (Functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_hygiene_mixed_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Mixed (Non-functional)'), 'value' => 0]]);

        $this->field('infrastructure_wash_hygiene_total_male', ['visible' => false]);
        $this->field('infrastructure_wash_hygiene_total_female', ['visible' => false]);
        $this->field('infrastructure_wash_hygiene_total_mixed', ['visible' => false]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
        ->orderDesc($this->aliasField('created'));
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra){

        $Data = $this->getData();
        $quantity = $this->getHygieneQuantity($Data);
        $this->field('infrastructure_wash_hygiene_total_male', ['visible' => false]);
        $this->field('infrastructure_wash_hygiene_total_female', ['visible' => false]);
        $this->field('infrastructure_wash_hygiene_total_mixed', ['visible' => false]);
        //$this->fields['quantities']['type'] = 'table';
        $this->field('academic_period_id');
        $this->field('infrastructure_wash_hygiene_type_id');
        $this->field('infrastructure_wash_hygiene_use_id');
        $this->field('quantities', [
            'type' => 'table',
            'headers' => [__('Gender'), __('Functional'),__('Non-functional')],
            'cells' => $quantity,
            'attr' => ['label' =>  __('Quantity')]
        ]);

    }

    public function getData(){
        $InfrastructureWashHygieneQuantities = TableRegistry::get('InfrastructureWashHygieneQuantities');
        $sanatationQuantitiesIdArr = $this->paramsDecode($this->request->params['pass'][1]);
        $sanatationId = $sanatationQuantitiesIdArr['id'];
        $sanitationQualitiesData = $InfrastructureWashHygieneQuantities->find()
        ->select([
            'gender_id' => 'gender_id',
            'functional' => 'functional',
            'value' => 'value'
        ])
        ->where([
            $InfrastructureWashHygieneQuantities->aliasField('infrastructure_wash_hygiene_id = ').$sanatationId
        ])
       ->toArray();
        return $sanitationQualitiesData;
    }

    private function getHygieneQuantity($entity)
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraField[] = [
            'key'   => 'infrastructure_wash_hygiene_type_id',
            'field' => 'infrastructure_wash_hygiene_type_id',
            'type'  => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key'   => 'infrastructure_wash_hygiene_soapash_availability_id',
            'field' => 'infrastructure_wash_hygiene_soapash_availability_id',
            'type'  => 'string',
            'label' => __('Soap/Ash Availability')
        ];

        $extraField[] = [
            'key'   => 'infrastructure_wash_hygiene_education_id',
            'field' => 'infrastructure_wash_hygiene_education_id',
            'type'  => 'string',
            'label' => __('Hygiene Education')
        ];

        $extraField[] = [
            'key'   => 'infrastructure_wash_hygiene_total_male',
            'field' => 'infrastructure_wash_hygiene_total_male',
            'type'  => 'integer',
            'label' => __('Total Male')
        ];

        $extraField[] = [
            'key'   => 'infrastructure_wash_hygiene_total_female',
            'field' => 'infrastructure_wash_hygiene_total_female',
            'type'  => 'integer',
            'label' => __('Total Female')
        ];

        $extraField[] = [
            'key'   => 'infrastructure_wash_hygiene_total_mixed',
            'field' => 'infrastructure_wash_hygiene_total_mixed',
            'type'  => 'integer',
            'label' => __('Total Mixed')
        ];

        $fields->exchangeArray($extraField);
    }
}
