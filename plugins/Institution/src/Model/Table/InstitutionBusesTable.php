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
        $this->field('comment',['visible' => false]);
        
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionBuses';
        $userType = '';
        $this->controller->changePageHeader($this, $modelAlias, $userType);
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

}
