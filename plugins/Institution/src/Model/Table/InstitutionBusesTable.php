<?php
namespace Institution\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Transport\Model\Table\TransportStatusesTable as TransportStatuses;

class InstitutionBusesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

		$this->belongsTo('TransportStatuses', ['className' => 'Transport.TransportStatuses']);
        $this->belongsTo('BusTypes', ['className' => 'Transport.BusTypes']);
        $this->belongsTo('InstitutionTransportProviders', ['className' => 'Institution.InstitutionTransportProviders']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->belongsToMany('TransportFeatures', [
			'className' => 'Transport.TransportFeatures',
			'joinTable' => 'institution_buses_transport_features',
			'foreignKey' => 'institution_bus_id',
			'targetForeignKey' => 'transport_feature_id',
			'through' => 'Institution.InstitutionBusesTransportFeatures',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

        $this->getDisplayField('plate_number');
        $this->addBehavior('Excel', [
            'excludes' => ['comment', 'institution_id'],
            'pages' => ['index'],
            'autoFields' => false
        ]); 
        
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InstitutionBuses' =>
                ['institution_id']
            ]
        ]);
    }

	public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

		return $validator
			->add('plate_number', 'ruleUnique', [
				'rule' => 'validateUnique',
				'provider' => 'table'
			])
            ->add('capacity', [
                'notZero' => [
                    'rule' => ['comparison', '>', 0],
                    'last' => true
                ]
            ]);
    }

	public function findView(Query $query, array $options)
    {
        $query->contain(['TransportFeatures']);

        return $query;
    }

	public function findEdit(Query $query, array $options)
    {
        $query->contain(['TransportFeatures']);

        return $query;
    }

    public function findOptionList(Query $query, array $options)
    {
        $operatingStatus = TransportStatuses::OPERATING;
        $query->matching('TransportStatuses', function ($q) use ($operatingStatus) {
            return $q->where(['TransportStatuses.id' => $operatingStatus]);
        });

        return parent::findOptionList($query, $options);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // POCOR-6168 start
        $session = $this->request->getSession();
        //$institutionId  = $session->read('Institution.Institutions.id');
        $institutionId = $this->getInstitutionID();

        // provider filter
        $transportProviders = $this->InstitutionTransportProviders
        ->find('optionList', [
            'defaultOption' => false,
            'institution_id' => $institutionId
        ])
        ->toArray();
        $transportProviderOptions = [-1 => __('All Providers')] + $transportProviders;
        $extra['transportProviders'] = $this->request->getQuery('provider');  
        // provider filter end
          
        // Transport Statuses
        $transportStatuses = $this->TransportStatuses
        ->find('optionList', ['defaultOption' => false])
        ->toArray();

        $transportStatusOptions = [-1 => __('All Statuses')] + $transportStatuses;
        $extra['transportStatuses'] = $this->request->getQuery('status');    
        // Transport Statuses end

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $extra['elements']['control'] = [
            'name' => 'Institution.Transport/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'transportProviderOptions'=> $transportProviderOptions,
                'selectedtransportProvider'=> $extra['transportProviders'],
                'transportStatusOptions'=> $transportStatusOptions,
                'selectedtransportStatuses'=> $extra['transportStatuses']
            ],
            'order' => 3
        ];
        // provider control end
        
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->field('comment',['visible' => false]);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Buses','Transport');       
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

    // POCOR-6168 For Filters
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $transportProviderId = $this->request->getQuery('provider');
        $transportStatusId = $this->request->getQuery('status');
        
        if($transportProviderId > 0){
            $query
            ->where([
                $this->aliasField('institution_transport_provider_id') => $transportProviderId
            ]);
        }
        
        if($transportStatusId > 0){
            $query
            ->where([
                $this->aliasField('transport_status_id') => $transportStatusId 
            ]);
        }
    }
    // POCOR-6168 For Filters

    // POCOR-6168 For excel Filters
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $extra, Query $query)
    {
        $session = $this->request->getSession();
        //$institutionId  = $session->read('Institution.Institutions.id');
        $institutionId  = $this->getInstitutionID();
        $transportProviderId = $this->request->getQuery('provider');
        $transportStatusId = $this->request->getQuery('status');

        $query
        ->where([
            $this->aliasField('institution_id') => $institutionId
        ]);

        if($transportProviderId > 0){
            $query
            ->where([
                $this->aliasField('institution_transport_provider_id') => $transportProviderId
            ]);
        }
        
        if($transportStatusId > 0){
            $query
            ->where([
                $this->aliasField('transport_status_id') => $transportStatusId 
            ]);
        }
    }
    // POCOR-6168 For excel Filters

   public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //POCOR-8647 Start
        //$this->field('institution_transport_provider_id', ['type' => 'select', 'after' => 'comment']);
        $institutionId = $this->getInstitutionID();
        $transportProviders = $this->InstitutionTransportProviders
        ->find('optionList', [
            'defaultOption' => false,
            'institution_id' => $institutionId
        ])
        ->toArray();
        $transportProviderOptions = ['' => '-- ' . __('Select Assignee') . ' --'] + $transportProviders;
        $this->field('institution_transport_provider_id', ['type' => 'select','options' => $transportProviderOptions, 'after' => 'comment']); 
        //POCOR-8647 End
        
        $this->field('bus_type_id', ['type' => 'select', 'after' => 'institution_transport_provider_id']);
        $this->field('transport_status_id', ['type' => 'select', 'after' => 'bus_type_id']);
        /*$modelAlias = 'InstitutionBuses';
        $userType = '';
        $this->controller->changePageHeader($this, $modelAlias, $userType);*/
    } 

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
         
         switch ($field) {
            case 'institution_transport_provider_id':
                return __('Provider');
            case 'transport_status_id': 
                return __('Status');
            case 'comment': 
                return __('Comment');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public  function onUpdateStatus(){
    }

}
