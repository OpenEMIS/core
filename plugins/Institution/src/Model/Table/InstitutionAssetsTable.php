<?php
namespace Institution\Model\Table;

use ArrayObject;
 
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;

use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionAssetsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $academicPeriodOptions = [];
    private $accessibilityOptions = [];
    private $purposeOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AssetStatuses', ['className' => 'Institution.AssetStatuses']);
        $this->belongsTo('AssetTypes', ['className' => 'Institution.AssetTypes']);
        $this->belongsTo('AssetConditions', ['className' => 'Institution.AssetConditions']);

        // POCOR-6152 export button <vikas.rathore@mail.valuecoders.com>
        $this->addBehavior('Excel',[
            'excludes' => ['academic_period_id', 'id'],
            'pages' => ['index'],
        ]);
        // POCOR-6152 export button <vikas.rathore@mail.valuecoders.com>
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['academic_period_id', 'institution_id']]],
                'provider' => 'table'
            ]);
    }

    // POCOR-6152 set breadcrumb header <vikas.rathore@mail.valuecoders.com>
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionAssets';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Assets','Details');       
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
    // POCOR-6152 set breadcrumb header <vikas.rathore@mail.valuecoders.com>

    // setting up  fields and filter POCOR-6152
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // POCOR-6152 set academic period filter <vikas.rathore@mail.valuecoders.com>
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);
        // set academic period filter <vikas.rathore@mail.valuecoders.com>

        // set asset types filter POCOR-6152
        $assetTypes = $this->AssetTypes
            ->find('optionList', ['defaultOption' => false])
            ->find('visible')
            ->find('order')
            ->toArray();
            
        $assetTypeOptions = ['' => __('All Types')] + $assetTypes;
        $extra['selectedAssetType'] = $this->request->query('asset_type_id'); 
        // set asset types filter POCOR-6152
        
        // set Accessibilities filter POCOR-6152
        $this->accessibilityOptions = $this->getSelectOptions($this->aliasField('accessibility'));
        
        $accessibilityOptions = ['' => __('All Accessibilities')] + $this->accessibilityOptions;
        $extra['selectedAccessibility'] = $this->request->query('accessibility'); 
        // set Accessibilities filter POCOR-6152

        $extra['elements']['control'] = [
            'name' => 'Institution.Assets/controls',
            'data' => [
                'academicPeriodOptions'=> $academicPeriodOptions,
                'selectedAcademicPeriodOptions'=> $extra['selectedAcademicPeriodOptions'],
                'assetTypeOptions' => $assetTypeOptions,
                'selectedAssetType' => $extra['selectedAssetType'],
                'accessibilityOptions' => $accessibilityOptions,
                'selectedAccessibility' => $extra['selectedAccessibility']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // POCOR-6152 end

        $this->field('Academic_period', ['visible' => true,  'attr' => ['label' => __('Academic Period')]]);
        $this->field('code', ['visible' => true, 'attr' => ['label' => __('Code')]]);
        $this->field('name', ['visible' => true, 'attr' => ['label' => __('Name')]]);
        $this->field('purpose', ['visible' => true, 'attr' => ['label' => __('Purpose')]]);
        $this->field('type', ['visible' => true, 'attr' => ['label' => __('Type')]]);
        $this->field('condition', ['visible' => true, 'attr' => ['label' => __('Condition')]]);
        $this->field('accessibility', ['visible' => true, 'attr' => ['label' => __('Accessibility')]]);
        $this->field('Status');

        $this->field('asset_type_id', ['visible' => false, 'attr' => ['label' => __('Type')]]);
        $this->field('asset_condition_id', ['visible' => false, 'attr' => ['label' => __('Condition')]]);
        $this->field('asset_status_id', ['visible' => false, 'attr' => ['label' => __('Status')]]);
    }
    // setting up  fields and filter POCOR-6152

    // get selected academic period  POCOR-6152
    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (isset($request->query) && array_key_exists('academic_period_id', $request->query)) {
                $selectedAcademicPeriod = $request->query['academic_period_id'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    } 
    // get selected academic period POCOR-6152

    // setting up query for index POCOR-6152 start
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriod = ($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent() ;
        $assetType = ($this->request->query('asset_type_id')) ? $this->request->query('asset_type_id') : 0;
        $accessibility = $this->request->query('accessibility');

        $query->select([
            $this->aliasField('id'), 
            $this->aliasField('academic_period_id'), 
            $this->aliasField('code'), 
            $this->aliasField('name'), 
            $this->aliasField('accessibility'),
            $this->aliasField('purpose'), 
            $this->aliasField('institution_id'), 
            'Academic_period' => $this->AcademicPeriods->aliasField('name'), 
            'type' => $this->AssetTypes->aliasField('name'), 
            'condition' =>$this->AssetConditions->aliasField('name'),
            'Status' => $this->AssetStatuses->aliasField('name'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'), 
            $this->aliasField('created_user_id'),
            $this->aliasField('created')
        ])
        ->innerJoin([$this->AcademicPeriods->alias() => $this->AcademicPeriods->table()], [
            [$this->AcademicPeriods->aliasField('id = '). $this->aliasField('academic_period_id')],
        ])
        ->innerJoin([$this->AssetTypes->alias() => $this->AssetTypes->table()], [
            [$this->AssetTypes->aliasField('id = '). $this->aliasField('asset_type_id')],
        ])
        ->innerJoin([$this->AssetConditions->alias() => $this->AssetConditions->table()], [
            [$this->AssetConditions->aliasField('id = '). $this->aliasField('asset_condition_id')],
        ])
        ->innerJoin([$this->AssetStatuses->alias() => $this->AssetStatuses->table()], [
            [$this->AssetStatuses->aliasField('id = '). $this->aliasField('asset_status_id')],
        ])
        ->where([
            $this->aliasField('academic_period_id') => $academicPeriod,
        ]);

        if($assetType > 0){
            $query->where([
                $this->aliasField('asset_type_id') => $assetType
            ]);
        }
        if($accessibility != ""){
            $query->where([
                $this->aliasField('accessibility') => $accessibility
            ]);
        }
        
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if($row->purpose == 1){
                    $row['purpose'] = 'Teaching';
                }else{
                    $row['purpose'] = 'Non-Teaching';
                }
                
                if($row->accessibility == 1){
                    $row['accessibility'] = 'Accessible';
                }else{
                    $row['accessibility'] = 'Not Accessible';
                }

                return $row;
            });
        });
    }
    // setting up query for index POCOR-6152 ends

    // POCOR-6152 Export Functionality 
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $academicPeriod = ($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent() ;
        $assetType = ($this->request->query('asset_type_id')) ? $this->request->query('asset_type_id') : 0;
        $accessibility = $this->request->query('accessibility');

        $query->select([
            $this->aliasField('id'), 
            $this->aliasField('academic_period_id'), 
            $this->aliasField('code'), 
            $this->aliasField('name'), 
            $this->aliasField('accessibility'),
            $this->aliasField('purpose'), 
            $this->aliasField('institution_id'), 
            'Academic_period' => $this->AcademicPeriods->aliasField('name'), 
            'type' => $this->AssetTypes->aliasField('name'), 
            'condition' =>$this->AssetConditions->aliasField('name'),
            'Status' => $this->AssetStatuses->aliasField('name'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'), 
            $this->aliasField('created_user_id'),
            $this->aliasField('created')
        ])
        ->innerJoin([$this->AcademicPeriods->alias() => $this->AcademicPeriods->table()], [
            [$this->AcademicPeriods->aliasField('id = '). $this->aliasField('academic_period_id')],
        ])
        ->innerJoin([$this->AssetTypes->alias() => $this->AssetTypes->table()], [
            [$this->AssetTypes->aliasField('id = '). $this->aliasField('asset_type_id')],
        ])
        ->innerJoin([$this->AssetConditions->alias() => $this->AssetConditions->table()], [
            [$this->AssetConditions->aliasField('id = '). $this->aliasField('asset_condition_id')],
        ])
        ->innerJoin([$this->AssetStatuses->alias() => $this->AssetStatuses->table()], [
            [$this->AssetStatuses->aliasField('id = '). $this->aliasField('asset_status_id')],
        ])
        ->where([
            $this->aliasField('academic_period_id') => $academicPeriod,
        ]);

        if($assetType > 0){
            $query->where([
                $this->aliasField('asset_type_id') => $assetType
            ]);
        }
        if($accessibility != ""){
            $query->where([
                $this->aliasField('accessibility') => $accessibility
            ]);
        }
        
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if($row->purpose == 1){
                    $row['purpose'] = 'Teaching';
                }else{
                    $row['purpose'] = 'Non-Teaching';
                }
                
                if($row->accessibility == 1){
                    $row['accessibility'] = 'Accessible';
                }else{
                    $row['accessibility'] = 'Not Accessible';
                }

                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'AcademicPeriods.name',
            'field' => 'Academic_period',
            'type'  => 'string',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key'   => 'InstitutionAssets.code',
            'field' => 'code',
            'type'  => 'string',
            'label' => __('Code')
        ];

        $extraField[] = [
            'key'   => 'InstitutionAssets.name',
            'field' => 'name',
            'type'  => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key'   => '',
            'field' => 'purpose',
            'type'  => 'string',
            'label' => __('Purpose')
        ];

        $extraField[] = [
            'key'   => 'AssetTypes.name',
            'field' => 'type',
            'type'  => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key'   => 'AssetConditions.name',
            'field' => 'condition',
            'type'  => 'string',
            'label' => __('Condition')
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'accessibility',
            'type'  => 'string',
            'label' => __('Accessibility')
        ];
        $extraField[] = [
            'key'   => 'AssetStatuses.name',
            'field' => 'Status',
            'type'  => 'string',
            'label' => __('Status')
        ];

        $fields->exchangeArray($extraField);
    }
    // POCOR-6152 Export Functionality

    // set up fields in add page POCOR-6152
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        // academic period field POCOR-6152
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['default'] = $this->AcademicPeriods->getCurrent();
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);
        // academic period field POCOR-6152

        // purpose fields POCOR-6152
        $this->purposeOptions = $this->getSelectOptions($this->aliasField('purpose'));
        $this->fields['purpose']['type'] = 'select';
        $this->fields['purpose']['options'] = $this->purposeOptions;
        $this->field('purpose', ['after' => 'name','attr' => ['label' => __('Purpose')]]);
        // purpose fields POCOR-6152

        // asset type field POCOR-6152
        $this->fields['asset_type_id']['type'] = 'select';
        $this->field('asset_type_id', ['after' => 'purpose','attr' => ['label' => __('Type')]]);
        // asset type field POCOR-6152

        // condition Field POCOR-6152
        $this->fields['asset_condition_id']['type'] = 'select';
        $this->field('asset_condition_id', ['after' => 'asset_type_id','attr' => ['label' => __('Condition')]]);
        // condition Field POCOR-6152

        // Accessibility field POCOR-6152
        $this->accessibilityOptions = $this->getSelectOptions($this->aliasField('accessibility'));
        $this->fields['accessibility']['type'] = 'select';
        $this->fields['accessibility']['options'] = $this->accessibilityOptions;
        $this->field('accessibility', ['after' => 'asset_condition_id','attr' => ['label' => __('Accessibility')]]);
        // Accessibility field POCOR-6152

        // status field POCOR-6152
        $this->fields['asset_status_id']['type'] = 'select';
        $this->field('asset_status_id', ['after' => 'accessibility','attr' => ['label' => __('Status')]]);
        // status field POCOR-6152
    }
    // set up fields in add page POCOR-6152

    // view page POCOR-6152
    public function viewAfterAction(Event $event,Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }
    // view page POCOR-6152

    // setup fields for view and edit POCOR-6152
    public function setupFields(Entity $entity, ArrayObject $extra)
    { 
        // academic field view
        if($entity->academic_period_id){
            $entity['academic_periods'] = $entity->academic_period->name;
        }
        $this->field('academic_periods',['before' => 'code','attr' => ['label' => __('Academic Period')],'visible' => ['view' => true]]);
        // purpose field view
        if($enity->purpose == 0){
            $entity['purpose'] = 'Non-Teaching';
        }else{
            $entity['purpose'] = 'Teaching';
        }
        $this->field('purpose',['after' => 'name','visible' => ['view' => true]]);
        // type field view
        $this->field('asset_type_id',['after' => 'purpose','attr' => ['label' => __('Type')],'visible' => ['view' => true]]);
        //condition field view
        $this->field('asset_condition_id',['after' => 'asset_type_id','attr' => ['label' => __('Condition')],'visible' => ['view' => true]]);
        //Accessibility field view
        if($entity->accessibility){
            $entity['accessibility'] = 'Accessible';
        }else{
            $entity['accessibility'] = 'Not Accessible';
        }
        $this->field('accessibility',['after' => 'asset_condition_id','attr' => ['label' => __('Accessibility')],'visible' => ['view' => true]]);
        // status field view
        $this->field('asset_status_id',['after' => 'accessibility','attr' => ['label' => __('Status')],'visible' => ['view' => true]]);
    }
    // setup fields for view and edit POCOR-6152
}
