<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class InstitutionContactsTable extends ControllerActionTable {
    public function initialize(array $config)
    { 
        $this->table('institutions');
        parent::initialize($config);
        /**
         * fieldOption tables
         */
        $this->belongsTo('Localities', ['className' => 'Institution.Localities', 'foreignKey' => 'institution_locality_id']);
        $this->belongsTo('Types', ['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
        $this->belongsTo('Ownerships', ['className' => 'Institution.Ownerships', 'foreignKey' => 'institution_ownership_id']);
        $this->belongsTo('Statuses', ['className' => 'Institution.Statuses', 'foreignKey' => 'institution_status_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);
        $this->belongsTo('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Genders', ['className' => 'Institution.Genders', 'foreignKey' => 'institution_gender_id']);
        /**
         * end fieldOption tables
         */

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('SecurityGroups', ['className' => 'Security.SystemGroups']);

        $this->excludeDefaultValidations(['area_id', 'institution_provider_id', 'institution_locality_id', 'institution_type_id', 'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_gender_id','area_administrative_id']); //POCOR-6826

        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->addBehavior('Excel', ['excludes' => ['name','alternative_name','code','address','postal_code','contact_person','date_opened','year_opened','date_closed','year_closed','longitude','latitude','logo_name','logo_content','shift_type','classification','area_id','area_administrative_id','institution_locality_id','institution_type_id','institution_ownership_id','institution_status_id','institution_sector_id','institution_provider_id','institution_gender_id','security_group_id'], 'pages' => ['view']]);    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('email')
            ->add('email', [
                    'ruleValidEmail' => [
                        'rule' => 'email'
                    ]
                ])

            ->allowEmpty('telephone')
            ->add('telephone', 'ruleCustomTelephone', [
                    'rule' => ['validateCustomPattern', 'institution_telephone'],
                    'provider' => 'table',
                    'last' => true
                ])

            ->allowEmpty('fax')
            ->add('fax', 'ruleCustomFax', [
                    'rule' => ['validateCustomPattern', 'institution_fax'],
                    'provider' => 'table',
                    'last' => true
                ])
            ;
        return $validator;
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
         $Navigation->substituteCrumb('Contacts', 'Contacts (Institution)');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $Institutions = TableRegistry::get('Institution.Institutions');
        $entity = $Institutions->get($institutionId);
        $institutionName = $entity->name;

        $this->controller->set('contentHeader', $institutionName. ' - ' .__('Contacts (Institution)'));

        $this->setFieldVisible(['view', 'edit'], [
            'telephone', 'fax', 'email', 'website'
        ]);

        // no index page
        if (isset($extra['toolbarButtons']['list'])) {
            unset($extra['toolbarButtons']['list']);
        }

        if ($this->action == 'view') {
            if (isset($extra['toolbarButtons']['back'])) {
                unset($extra['toolbarButtons']['back']);
            }
        }

        // prevent users from manually accessing other insitution's pages
        if (isset($this->request->pass[1])) {
            $passId = $this->paramsDecode($this->request->pass[1])['id'];
            $id = $this->Session->read('Institution.Institutions.id');
            if ($passId != $id) {
                $url = $this->url('view');
                $url[1] = $this->paramsEncode(['id' => $id]);
                $this->controller->redirect($url);
            }
        }


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Contacts - Institution','General');       
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

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        // no index page
        $url = $this->url('view');
        return $this->controller->redirect($url);
    }

}
