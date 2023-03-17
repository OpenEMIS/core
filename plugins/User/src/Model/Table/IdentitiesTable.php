<?php
namespace User\Model\Table;

use Exception;
use DateTime;
use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;

class IdentitiesTable extends ControllerActionTable
{
    const ISPREFERRED = 1;

    public function initialize(array $config)
	{
		$this->table('user_identities');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
		$this->belongsTo('Nationalities', ['className' => 'FieldOption.Nationalities']);
		$this->addBehavior('Restful.RestfulAccessControl', [
        	'Students' => ['index', 'add'],
        	'Staff' => ['index', 'add']
        ]);
        $this->addBehavior('User.SetupTab');
		$this->excludeDefaultValidations(['security_user_id']);
	}

	public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Users.afterSave' => 'afterSaveUsers'
        ];

        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function afterSaveUsers(Event $event, Entity $entity)
    {
        //whichever identity type and number that came from import user, will be treat as new identity user record.
        $userIdentityEntity = $this->newEntity([
            'identity_type_id' => $entity->identity_type_id,
            'number' => $entity->identity_number,
            'security_user_id' => $entity->id,
            'created_user_id' => 1,
            'created' => new Time()
        ]);
        $this->save($userIdentityEntity);
    }

	public function beforeAction($event, ArrayObject $extra)
	{
     	$UserNationalityTable = TableRegistry::get('User.UserNationalities');
     	$users = TableRegistry::get('User.Users');
        $userId = null;
        $queryString = $this->getQueryString();
        if (isset($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        }

        /*POCOR-6396 starts*/
        if ($this->action == 'add' || $this->action == 'edit') {
        	$checkUserNationality = $UserNationalityTable->find()
        							->where([$UserNationalityTable->aliasField('security_user_id') => $userId])
        							->first();
        	if(!empty($checkUserNationality)) {
        		$usersOptions = $users->find('list',[
						    	'keyField' => '_matchingData.MainNationalities.id',
						        'valueField' => '_matchingData.MainNationalities.name'
						    ])
						    ->matching('MainNationalities')
                			->where([$users->aliasField('id') => $userId])
                			->toArray();
        		$UsersNationalityOptions =  $UserNationalityTable
						    ->find('list',[
						    	'keyField' => '_matchingData.NationalitiesLookUp.id',
						        'valueField' => '_matchingData.NationalitiesLookUp.name'
						    ])
						    ->matching('NationalitiesLookUp')
                			->where([$UserNationalityTable->aliasField('security_user_id') => $userId])
                			->toArray();
                $NationalityOptions = array_unique ($usersOptions + $UsersNationalityOptions);    
        	}
        }
        /*POCOR-6396 starts*/
		$this->fields['identity_type_id']['type'] = 'select';
		$this->fields['nationality_id']['type'] = 'select';
        $this->fields['nationality_id']['options'] = (!empty($NationalityOptions)) ? $NationalityOptions : ['' => $this->getMessage('general.select.noOptions')]; //POCOR-6396
		$this->setFieldOrder(['identity_type_id', 'nationality_id', 'number', 'issue_date', 'expiry_date', 'issue_location', 'comments']);   
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['comments']['visible'] = 'false';
		
 		// Start POCOR-5188
        if($this->request->params['controller'] == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Identities','Staff - General');       
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
        }elseif($this->request->params['controller'] == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Identities','Students - General');       
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

        }elseif($this->request->params['controller'] == 'Directories'){
            $is_manual_exist = $this->getManualUrl('Directory','Identities','General');       
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

        }elseif($this->request->params['controller'] == 'Profiles'){ 
            $is_manual_exist = $this->getManualUrl('Personal','Identities','General');       
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

        }
        // End POCOR-5188

	}

	/*POCOR-6267 Starts*/
	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    	$session = $this->request->session();
        $queryString = $this->getQueryString();
    	if (!empty($queryString['security_user_id'])) {
    		$userId = $queryString['security_user_id'];
    	} else {
    		$userId = $session->read('Student.Students.id');
    	}

    	$query->where([$this->aliasField('security_user_id') => $userId]);
    }
	/*POCOR-6267 Ends*/

	public function editOnInitialize(Event $event, Entity $entity)
	{
		// set the defaultDate to false on initialize, for the empty date.
		if (empty($entity->issue_date)) {
			$this->fields['issue_date']['default_date'] = false;
		}

		if (empty($entity->expiry_date)) {
			$this->fields['expiry_date']['default_date'] = false;
		}
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		return $validator
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			])
			->add('expiry_date',  [
            ])
            ->add('identity_type_id', 'ruleCustomIdentityType', [
                'rule' => ['validateCustomIdentityType'],
                'provider' => 'table',
            ])
            ->add('number', 'ruleCustomIdentityNumber', [
				'rule' => ['validateCustomIdentityNumber'],
				'provider' => 'table',
				'last' => true
			])
			->add('number', [
				'ruleUnique' => [
			        'rule' => ['validateUnique', ['scope' => 'identity_type_id']],
			        'provider' => 'table'
			    ]
		    ])
		    //POCOR-5987 starts
		    ->notEmpty('nationality_id');
		    //POCOR-5987 ends
	}

	public function validationAddByAssociation(Validator $validator)
	{
		$validator = $this->validationDefault($validator);
		return $validator->requirePresence('security_user_id', false);
	}

	public function validationNonMandatory(Validator $validator)
	{
		$validator = $this->validationDefault($validator);
		return $validator->allowEmpty('number');
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $extra)
	{
            if(!empty($entity->nationality_id)){
                $nationalitiesLookUp = TableRegistry::get('Nationalities')->get($entity->nationality_id);
                // if($nationalitiesLookUp->identity_type_id == $entity->identity_type_id){
                if($nationalitiesLookUp){
                    $user = TableRegistry::get('User.Users');
                    $preferredNationality = TableRegistry::get('User.UserNationalities')
                            ->find()
                            ->where(['nationality_id'=>$entity->nationality_id,
                                    'preferred' => self::ISPREFERRED
                                    ])
                            ->first();

                    $userDetail = $user->get($entity->security_user_id);  
                    if (!empty($preferredNationality)) {
                        $userDetail->nationality_id = $entity->nationality_id;
                    }
                    $userDetail->identity_type_id = $entity->identity_type_id;
                    $userDetail->identity_number = $entity->number;
                    $user->save($userDetail);  
                }      
            }try{
				$Users = TableRegistry::get('User.Users');
				//echo "<pre>";print_r($this->request);die();
				$result = $Users
						->find()
						->select(['identity_number','identity_type_id'])
						->where(['id' => $entity->security_user_id])
						->first();
				if((($result['identity_number'] == null || $result['identity_number'] == '') && ($result['identity_type_id'] == null || $result['identity_type_id'] == '') )){
					$Users->updateAll(
						['identity_number' => $entity->number, 'identity_type_id' => $entity->identity_type_id],    //field
						['id' => $entity->security_user_id] //condition
					);
				}
			}catch (\Exception $e) {
			}
            
            $listeners = [
                TableRegistry::get('User.Users')
            ];
            $this->dispatchEventToModels('Model.UserIdentities.onChange', [$entity], $this, $listeners);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $extra)
	{
            $listeners = [
                TableRegistry::get('User.Users')
            ];
            $this->dispatchEventToModels('Model.UserIdentities.onChange', [$entity], $this, $listeners);
	}

	public function getLatestDefaultIdentityNo($userId) 
	{
        //check identity type that ties to the nationality
		$UserNationalityTable = TableRegistry::get('User.UserNationalities');

        $nationalityId = null;
		$identityType = $UserNationalityTable
			->find()
			->matching('NationalitiesLookUp')
			->select(['nationality_id', 'identityTypeId' => 'NationalitiesLookUp.identity_type_id'])
			->where([
				'security_user_id' => $userId,
                'preferred' => self::ISPREFERRED
			])
            ->first();

		//get the latest record according to identity type
        $result = null;
        if ($identityType) {
            $nationalityId = $identityType->nationality_id;
			$result = $this
				->find()
				->where([
					$this->aliasField('security_user_id') => $userId,
					$this->aliasField('identity_type_id') => $identityType->identityTypeId
				])
                ->order('created DESC')
				->first();
		}

		if (!empty($result)) {
			return ['nationality_id' => $nationalityId, 'identity_type_id' => $result->identity_type_id, 'identity_no' => $result->number];
		} else {
			return ['nationality_id' => $nationalityId, 'identity_type_id' => null, 'identity_no' => null];
		}
	}
}