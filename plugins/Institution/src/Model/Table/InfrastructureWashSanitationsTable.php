<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InfrastructureWashSanitationsTable extends ControllerActionTable {

    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_wash_sanitations');
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

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InfrastructureWashSanitations'=>['id']]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
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

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $total_male = $entity->infrastructure_wash_sanitation_male_functional + $entity->infrastructure_wash_sanitation_male_nonfunctional;
        $total_female = $entity->infrastructure_wash_sanitation_female_functional + $entity->infrastructure_wash_sanitation_female_nonfunctional;
        $total_mixed = $entity->infrastructure_wash_sanitation_mixed_functional + $entity->infrastructure_wash_sanitation_mixed_nonfunctional;

        $entity->infrastructure_wash_sanitation_total_male = $total_male;
        $entity->infrastructure_wash_sanitation_total_female = $total_female;
        $entity->infrastructure_wash_sanitation_total_mixed = $total_mixed;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $requestData)
    {
        $SanitationQuantitiesTable = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitationQuantities');
        $SanitationQuantitiesTable->deleteAll(['infrastructure_wash_sanitation_id' => $entity->id]);

        $data1 = $SanitationQuantitiesTable->newEmptyEntity();
        $data1->gender_id = 1;
        $data1->functional = 1;
        $data1->value = $entity->infrastructure_wash_sanitation_male_functional;
        $data1->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data1);

        $data2 = $SanitationQuantitiesTable->newEmptyEntity();
        $data2->gender_id = 1;
        $data2->functional = 0;
        $data2->value = $entity->infrastructure_wash_sanitation_male_nonfunctional;
        $data2->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data2);

        $data3 = $SanitationQuantitiesTable->newEmptyEntity();
        $data3->gender_id = 2;
        $data3->functional = 1;
        $data3->value = $entity->infrastructure_wash_sanitation_female_functional;
        $data3->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data3);

        $data4 = $SanitationQuantitiesTable->newEmptyEntity();
        $data4->gender_id = 2;
        $data4->functional = 0;
        $data4->value = $entity->infrastructure_wash_sanitation_female_nonfunctional;
        $data4->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data4);

        $data5 = $SanitationQuantitiesTable->newEmptyEntity();
        $data5->gender_id = 3;
        $data5->functional = 1;
        $data5->value = $entity->infrastructure_wash_sanitation_mixed_functional;
        $data5->infrastructure_wash_sanitation_id = $entity->id;
        $SanitationQuantitiesTable->save($data5);

        $data6 = $SanitationQuantitiesTable->newEmptyEntity();
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

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashSanitations';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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
        $requestQuery = $this->request->getQuery();

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Risks/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control

        // Start POCOR-5188
        $manualTable = TableRegistry::getTableLocator()->get('Institution.Manuals');
        $ManualContent =   $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Infrastructure WASH Sanitation',
                $manualTable->aliasField('module') => 'Institutions',
                $manualTable->aliasField('category') => 'Details',
                ])->first();
        
        if(!empty($ManualContent['url'])){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];
    
            $helpBtn['url'] = $ManualContent['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'academic_period_id':
                return __('Academic Period');
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
            case 'modified_user_id':
                return __('Modified User');
            case 'modified':
                return __('Modified');
            case 'created_user_id':
                return __('Created User');
            case 'created':
                return __('Created');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
        ->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $SanitationQuantitiesTable = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitationQuantities');

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->fields['infrastructure_wash_sanitation_type_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['infrastructure_wash_sanitation_use_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_use_id', ['attr' => ['label' => __('Use')]]);

        //POCOR-9594-5 --start
        // These fields aren't real columns on this table - the actual saved values
        // live in InfrastructureWashSanitationQuantities, keyed by gender_id +
        // functional. The hardcoded 'value' => 0 below applied unconditionally on
        // both add and edit, so the edit form always showed 0 regardless of what
        // was actually saved. When a real record id decodes from the URL (edit),
        // look up its saved quantities and use those as the field defaults instead.
        $quantityDefaults = [
            'infrastructure_wash_sanitation_male_functional'      => 0,
            'infrastructure_wash_sanitation_male_nonfunctional'   => 0,
            'infrastructure_wash_sanitation_female_functional'    => 0,
            'infrastructure_wash_sanitation_female_nonfunctional' => 0,
            'infrastructure_wash_sanitation_mixed_functional'     => 0,
            'infrastructure_wash_sanitation_mixed_nonfunctional'  => 0,
        ];
        //POCOR-9594-5-2: the 'id' key in this app's encoded queryString is also
        // used to carry the institution_id on Add (see e.g.
        // InstitutionTabBehavior::fixAddDeleteRedirectURL()), and navigating here
        // from an Edit page can carry that same encoded blob forward - so a
        // decoded id alone doesn't reliably mean "this is the record being
        // edited". Gate on the actual current action instead.
        $passParams = $this->request->getAttribute('params')['pass'] ?? [];
        if ($this->action === 'edit' && !empty($passParams[1])) {
            $decoded = $this->paramsDecode($passParams[1]);
            $recordId = $decoded['id'] ?? null;
            if ($recordId) {
                $quantities = $SanitationQuantitiesTable->find()
                    ->where(['infrastructure_wash_sanitation_id' => $recordId])
                    ->all();
                foreach ($quantities as $qty) {
                    if ($qty->gender_id == 1 && $qty->functional == 1) {
                        $quantityDefaults['infrastructure_wash_sanitation_male_functional'] = $qty->value;
                    } elseif ($qty->gender_id == 1 && $qty->functional == 0) {
                        $quantityDefaults['infrastructure_wash_sanitation_male_nonfunctional'] = $qty->value;
                    } elseif ($qty->gender_id == 2 && $qty->functional == 1) {
                        $quantityDefaults['infrastructure_wash_sanitation_female_functional'] = $qty->value;
                    } elseif ($qty->gender_id == 2 && $qty->functional == 0) {
                        $quantityDefaults['infrastructure_wash_sanitation_female_nonfunctional'] = $qty->value;
                    } elseif ($qty->gender_id == 3 && $qty->functional == 1) {
                        $quantityDefaults['infrastructure_wash_sanitation_mixed_functional'] = $qty->value;
                    } elseif ($qty->gender_id == 3 && $qty->functional == 0) {
                        $quantityDefaults['infrastructure_wash_sanitation_mixed_nonfunctional'] = $qty->value;
                    }
                }
            }
        }
        //POCOR-9594-5 --end

        $this->field('infrastructure_wash_sanitation_male_functional', ['type' => 'integer','attr' => ['label' => __('Male (Functional)'), 'value' => $quantityDefaults['infrastructure_wash_sanitation_male_functional']]]);

        $this->field('infrastructure_wash_sanitation_male_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Male (Non-functional)'), 'value' => $quantityDefaults['infrastructure_wash_sanitation_male_nonfunctional']]]);

        $this->field('infrastructure_wash_sanitation_female_functional', ['type' => 'integer','attr' => ['label' => __('Female (Functional)'), 'value' => $quantityDefaults['infrastructure_wash_sanitation_female_functional']]]);

        $this->field('infrastructure_wash_sanitation_female_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Female (Non-functional)'), 'value' => $quantityDefaults['infrastructure_wash_sanitation_female_nonfunctional']]]);

        $this->field('infrastructure_wash_sanitation_mixed_functional', ['type' => 'integer','attr' => ['label' => __('Mixed (Functional)'), 'value' => $quantityDefaults['infrastructure_wash_sanitation_mixed_functional']]]);

        $this->field('infrastructure_wash_sanitation_mixed_nonfunctional', ['type' => 'integer','attr' => ['label' => __('Mixed (Non-functional)'), 'value' => $quantityDefaults['infrastructure_wash_sanitation_mixed_nonfunctional']]]);

        $this->field('infrastructure_wash_sanitation_total_male', ['visible' => false]);
        $this->field('infrastructure_wash_sanitation_total_female', ['visible' => false]);
        $this->field('infrastructure_wash_sanitation_total_mixed', ['visible' => false]);

        $this->fields['infrastructure_wash_sanitation_quality_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_quality_id', ['attr' => ['label' => __('Quality')]]);

        $this->fields['infrastructure_wash_sanitation_accessibility_id']['type'] = 'select';
        $this->field('infrastructure_wash_sanitation_accessibility_id', ['attr' => ['label' => __('Accessibility')]]);
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra){

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
        $InfrastructureWashSanitationQuantities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitationQuantities');
        $sanatationQuantitiesIdArr = $this->paramsDecode($this->request->getAttribute('params')['pass'][1]);
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
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
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

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query){
        //POCOR-9594-4: $this->request->session() no longer exists in this CakePHP
        // version (only getSession() does) - calling it threw a fatal error on
        // every export attempt. $session was never actually used below anyway.
        $institutionId  = $this->getInstitutionID();
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $query
        ->Where([
            $this->aliasField('institution_id = ').$institutionId,
            $this->aliasField('academic_period_id = ').$selectedAcademicPeriod
        ]);
    }

}
