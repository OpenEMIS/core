<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class InstitutionContactsTable extends ControllerActionTable {
    public function initialize(array $config): void
    { 
        $this->setTable('institutions');
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
        // $this->belongsTo('SecurityGroups', ['className' => 'Security.SystemGroups']);

        $this->excludeDefaultValidations(['area_id', 'institution_provider_id', 'institution_locality_id', 'institution_type_id', 'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_gender_id','area_administrative_id']); //POCOR-6826

        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->addBehavior('Excel', ['excludes' => ['name','alternative_name','code','address','postal_code','contact_person','date_opened','year_opened','date_closed','year_closed','longitude','latitude','logo_name','logo_content','shift_type','classification','area_id','area_administrative_id','institution_locality_id','institution_type_id','institution_ownership_id','institution_status_id','institution_sector_id','institution_provider_id','institution_gender_id','security_group_id'], 'pages' => ['view']]); 
        $this->addBehavior('Institution.InstitutionTab');     
    
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->allowEmpty('email')
            ->add('email', [
                    'ruleValidEmail' => [
                        'rule' => 'email',
                        'message' => 'Invalid email address'
                    ]
                ])

            ->allowEmpty('telephone')
            // ->add('telephone', 'ruleCustomTelephone', [
            //         'rule' => ['validateCustomPattern', 'institution_telephone'],
            //         'provider' => 'table',
            //         'last' => true
            //     ])

            ->add('telephone', 'ruleContactNumberPattern', [
                'rule' => ['validateContactNumberPattern', 'validate_contact_person_telephone'],
                'provider' => 'table',
                'last' => true
            ])

            /*->allowEmpty('fax')
            ->add('fax', 'ruleCustomFax', [
                    'rule' => ['validateCustomPattern', 'institution_fax'],
                    'provider' => 'table',
                    'last' => true
                ])*/
            ;
        return $validator;
    }

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {
         $Navigation->substituteCrumb('Contacts', 'Contacts (Institution)');
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $institutionId = null;
        $institutionParam = $this->request->getAttribute('params')['id'];
        if ($institutionParam !== null) {
            $institutionId = $this->paramsDecode($institutionParam);
            $institutionId = $institutionId['id'];
        } else {
            $institutionId = $this->getInstitutionID();
        }

        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $entity = $Institutions->get($institutionId);
        $institutionName = $entity->name;
       

        $this->controller->set('contentHeader', $institutionName. ' - ' .__('Contacts (Institution)'));

        $this->setFieldVisible(['view', 'edit'], [
            'telephone', 'email', 'website'
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

        if ($this->request->getParam('pass.1') !=null) {
            $passId = $this->paramsDecode($this->request->getParam('pass.1'));
            $passId = $passId['id'];
            $id = $this->getInstitutionID();
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

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
        // no index page
        $url = $this->url('view');
        return $this->controller->redirect($url);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'telephone':
                return __('Telephone');
            case 'email':
                return __('Email');
            case 'website':
                return __('Website');
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
