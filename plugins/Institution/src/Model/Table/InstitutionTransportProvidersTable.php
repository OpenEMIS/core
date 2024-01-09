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
    public function initialize(array $config): void
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

	public function validationDefault(Validator $validator): Validator
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

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Providers','Transport');       
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'name':
                return __('Name');
            case 'address':
                return __('Address');
            case 'email':
                return __('Email');
            case 'comment':
                return __('Comment');
            case 'contact_number':
                return __('Contact Number');
            case 'registration_number':
                return __('Registration Number');
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
}
