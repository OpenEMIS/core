<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class InstitutionTransportProvidersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('InstitutionBuses', ['className' => 'Institution.InstitutionBuses', 'dependent' => true, 'cascadeCallbacks' => true]);
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
			->add('name', 'ruleUnique', [
                'rule' => [
                    'validateUnique', [
                        'scope' => 'institution_id'
                    ]
                ],
				'provider' => 'table'
			]);
    }

    public function findView(Query $query, array $options)
    {
        $query->contain([
            'InstitutionBuses' => [
                'TransportStatuses',
                'sort' => [
                    'InstitutionBuses.plate_number' => 'ASC'
                ]
            ]
        ]);

        return $query;
    }

    public function findOptionList(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : 0;
        $query->where(['institution_id' => $institutionId]);
        
        return parent::findOptionList($query, $options);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment',['visible' => false]);
    }
    public function changeUtilitiesHeader($model, $modelAlias, $userType)
    {
        echo $model->alias();die;
        $session = $this->request->session();
        $institutionId = 0;
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        if (!empty($institutionId)) {
            if($this->request->param('action') == 'InstitutionTransportProviders') {
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Providers');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Providers'));
                $this->set('contentHeader', $header);

            }
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query->where(['InstitutionTransportProviders.institution_id' =>  $institutionId]);
    }
}
