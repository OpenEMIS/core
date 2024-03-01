<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

use App\Model\Table\AppTable;

class InfrastructureUtilityElectricitiesTable extends ControllerActionTable
{
    private $infrastructureTabsData = [0 => "Electricity", 1 => "Internet", 2 => "Telephone"];
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_utility_electricities');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityElectricityTypes',   ['className' => 'Institution.UtilityElectricityTypes', 'foreign_key' => 'utility_electricity_type_id']);
        $this->belongsTo('UtilityElectricityConditions',   ['className' => 'Institution.UtilityElectricityConditions', 'foreign_key' => 'utility_electricity_condition_id']);

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['comment', 'academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InfrastructureUtilityElectricities'=>['id']]
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureUtilityElectricities';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('utility_electricity_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('utility_electricity_condition_id', ['attr' => ['label' => __('Condition')]]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('comment',['visible' => false]);


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
        $is_manual_exist = $this->getManualUrl('Institutions','Infrastructure Utility Electricity','Details');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];
    
            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'comment':
                return __('Comment');
            case 'utility_electricity_type_id':
                return __('Type');
            case 'utility_electricity_condition_id':
                return __('Condition');
            case 'academic_period_id':
                return __('Academic Period');
            case 'utility_electricity_condition_id':
                return __('Condition');
            case 'modified_user_id':
                return __('Modified By');
            case 'modified':
                return __('Modified On');
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created On');
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

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->fields['utility_electricity_type_id']['type'] = 'select';
        $this->field('utility_electricity_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['utility_electricity_condition_id']['type'] = 'select';
        $this->field('utility_electricity_condition_id', ['attr' => ['label' => __('Condition')]]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $infrastructureTabsData = $this->infrastructureTabsData;
        $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $institutionStudentId = $settings['id'];

        foreach ($infrastructureTabsData as $key => $val)
        {
            $tabsName = $val;
            $sheets[] = ['sheetData' => ['infrastructure_tabs_type' => $val], 'name' => $tabsName, 'table' => $this, 'query' => $this->find()
            /* ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],[
                        $this->aliasField('id = ').$InstitutionStudents->aliasField('student_id')
                    ])
                    ->where([
                        $InstitutionStudents->aliasField('student_id = ').$institutionStudentId,
                    ]) */
            , 'orientation' => 'landscape'];
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    { 
        $sheetData = $settings['sheet']['sheetData'];
        $infrastructureType = $sheetData['infrastructure_tabs_type'];

        $extraField=[];
       // print_r($infrastructureType); exit;
        if ($infrastructureType == 'Electricity')
        {
    
            $extraField[] = [
                'key'   => 'utility_electricity_type_id',
                'field' => 'utility_electricity_type_id',
                'type'  => 'string',
                'label' => __('Type')
            ];

            $extraField[] = [
                'key'   => 'utility_electricity_condition_id',
                'field' => 'utility_electricity_condition_id',
                'type'  => 'string',
                'label' => __('Condition')
            ];
        }
        if ($infrastructureType == 'Internet')
        {
            $extraField[] = [
                'key'   => '',
                'field' => 'utility_internet_type_name',
                'type'  => 'string',
                'label' => __('Type')
            ];

            $extraField[] = [
                'key'   => '',
                'field' => 'utility_internet_conditions_name',
                'type'  => 'string',
                'label' => __('Condition')
            ];

            $extraField[] = [
                'key'   => '',
                'field' => 'internet_purpose_new',
                'type'  => 'string',
                'label' => __('Purpose')
            ];

            $extraField[] = [
                'key'   => '',
                'field' => 'utility_internet_bandwidths_name',
                'type'  => 'string',
                'label' => __('Bandwidth')
            ];
        }
        if ($infrastructureType == 'Telephone')
        {

             $extraField[] = [
                'key'   => '',
                'field' => 'utility_telephone_type_name',
                'type'  => 'string',
                'label' => __('Type')
            ];

            $extraField[] = [
                'key'   => '',
                'field' => 'utility_telephone_conditions_name',
                'type'  => 'string',
                'label' => __('Condition')
            ];
        }
        $fields->exchangeArray($extraField);

    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
//print_r($query->sql()); exit;
        $sheetData = $settings['sheet']['sheetData'];
        $infrastructureType = $sheetData['infrastructure_tabs_type'];
        $academicPeriod = $this->request->getQuery('academic_period_id');
        
        $institutionId  = $this->getInstitutionID();
        //$institutionId  = $this->Session->read('Institution.Institutions.id');
        $newFields = [];
        if ($infrastructureType == 'Electricity')
        {
            if (empty($academicPeriod)) {
                $academicPeriod = $this->AcademicPeriods->getCurrent();
            }
            $query->where([$this->aliasField('academic_period_id') => $academicPeriod,$this->aliasField('institution_id')=>$institutionId])
            ->orderDesc($this->aliasField('created'));
        }
        if ($infrastructureType == 'Internet')
        {
            if (empty($academicPeriod)) {
                $academicPeriod = $this->AcademicPeriods->getCurrent();
            }
            //print_r($academicPeriod); exit;
            $infrastructureUtilityInternets = TableRegistry::get('Institution.InfrastructureUtilityInternets');
            $utilityInternetConditions = TableRegistry::get('Institution.UtilityInternetConditions');
            $utilityInternetTypes = TableRegistry::get('Institution.UtilityInternetTypes');
            $utilityInternetBandwidths = TableRegistry::get('Institution.UtilityInternetBandwidths');
            $res=$query
             ->select(['utility_internet_type_name' => $utilityInternetTypes->aliasField('name'),
                'utility_internet_conditions_name' => $utilityInternetConditions->aliasField('name'),
                'internet_purpose_new' => "(CASE WHEN internet_purpose = 1 THEN 'Teaching'
                ELSE 'Non-Teaching' END)",
                'utility_internet_bandwidths_name'=>$utilityInternetBandwidths->aliasField('name')
            ])->LeftJoin([$infrastructureUtilityInternets->getAlias() => $infrastructureUtilityInternets->getTable() ], [$infrastructureUtilityInternets->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id'),$infrastructureUtilityInternets->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') ])
             ->LeftJoin([$utilityInternetConditions->getAlias() => $utilityInternetConditions->getTable() ], [$infrastructureUtilityInternets->aliasField('utility_internet_condition_id') . ' = ' . $utilityInternetConditions->aliasField('id') ])
             ->LeftJoin([$utilityInternetTypes->getAlias() => $utilityInternetTypes->getTable() ], [$infrastructureUtilityInternets->aliasField('utility_internet_type_id') . ' = ' . $utilityInternetTypes->aliasField('id') ])
             ->LeftJoin([$utilityInternetBandwidths->getAlias() => $utilityInternetBandwidths->getTable() ], [$infrastructureUtilityInternets->aliasField('utility_internet_bandwidth_id') . ' = ' . $utilityInternetBandwidths->aliasField('id') ])
            ->where([$infrastructureUtilityInternets->aliasField('academic_period_id') => $academicPeriod,$infrastructureUtilityInternets->aliasField('institution_id')=>$institutionId])
            ->group($utilityInternetTypes->aliasField('name'))
            ->orderDesc($infrastructureUtilityInternets->aliasField('created'));

        }
        if ($infrastructureType == 'Telephone')
        {
            if (empty($academicPeriod)) {
                $academicPeriod = $this->AcademicPeriods->getCurrent();
            }
            //print_r($academicPeriod); exit;
            $infrastructureUtilityTelephones = TableRegistry::get('Institution.InfrastructureUtilityTelephones');
            $utilityTelephoneConditions = TableRegistry::get('Institution.UtilityTelephoneConditions');
            $utilityTelephoneTypes = TableRegistry::get('Institution.UtilityTelephoneTypes');
            $query
             ->select(['utility_telephone_type_name' => $utilityTelephoneTypes->aliasField('name'),
                'utility_telephone_conditions_name' => $utilityTelephoneConditions->aliasField('name')
            ])->LeftJoin([$infrastructureUtilityTelephones->getAlias() => $infrastructureUtilityTelephones->getTable() ], [$infrastructureUtilityTelephones->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id'),$infrastructureUtilityTelephones->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') ])
             ->LeftJoin([$utilityTelephoneConditions->getAlias() => $utilityTelephoneConditions->getTable() ], [$infrastructureUtilityTelephones->aliasField('utility_telephone_condition_id') . ' = ' . $utilityTelephoneConditions->aliasField('id') ])
             ->LeftJoin([$utilityTelephoneTypes->getAlias() => $utilityTelephoneTypes->getTable() ], [$infrastructureUtilityTelephones->aliasField('utility_telephone_type_id') . ' = ' . $utilityTelephoneTypes->aliasField('id') ])
            ->where([$infrastructureUtilityTelephones->aliasField('academic_period_id') => $academicPeriod,$infrastructureUtilityTelephones->aliasField('institution_id')=>$institutionId])
            ->group($utilityTelephoneTypes->aliasField('name'))
            ->orderDesc($infrastructureUtilityTelephones->aliasField('created'));
            //print_r($res); exit;

        }
    }
}
