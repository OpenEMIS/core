<?php
namespace Institution\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Transport\Model\Table\TransportStatusesTable as TransportStatuses;

class InstitutionBusesTable extends ControllerActionTable
{
    public function initialize(array $config)
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

        $this->displayField('plate_number');
        $this->addBehavior('Excel', [
            'excludes' => ['comment', 'institution_id'],
            'pages' => ['index'],
            'autoFields' => false
        ]); 
        
    }

	public function validationDefault(Validator $validator)
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // POCOR-6168 start
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');

        // provider filter
        $transportProviders = $this->InstitutionTransportProviders
        ->find('optionList', [
            'defaultOption' => false,
            'institution_id' => $institutionId
        ])
        ->toArray();
        $transportProviderOptions = [-1 => __('All Providers')] + $transportProviders;
        $extra['transportProviders'] = $this->request->query('provider');  
        // provider filter end
          
        // Transport Statuses
        $transportStatuses = $this->TransportStatuses
        ->find('optionList', ['defaultOption' => false])
        ->toArray();

        $transportStatusOptions = [-1 => __('All Statuses')] + $transportStatuses;
        $extra['transportStatuses'] = $this->request->query('status');    
        // Transport Statuses end

        $extra['elements']['control'] = [
            'name' => 'Institution.Transport/controls',
            'data' => [
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
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $transportProviderId = $this->request->query('provider');
        $transportStatusId = $this->request->query('status');
        
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
    public function onExcelBeforeQuery(Event $event, ArrayObject $extra, Query $query)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $transportProviderId = $this->request->query('provider');
        $transportStatusId = $this->request->query('status');

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

   public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_transport_provider_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('bus_type_id', ['type' => 'select', 'after' => 'institution_transport_provider_id']);
        $this->field('transport_status_id', ['type' => 'select', 'after' => 'bus_type_id']);
        /*$modelAlias = 'InstitutionBuses';
        $userType = '';
        $this->controller->changePageHeader($this, $modelAlias, $userType);*/
    } 

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
         
         switch ($field) {
            case 'institution_transport_provider_id':
                return __('Provider');
            case 'transport_status_id': 
                return __('Status');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public  function onUpdateStatus(){
    }

}
