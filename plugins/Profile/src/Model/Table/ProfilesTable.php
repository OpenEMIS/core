<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class ProfilesTable extends ControllerActionTable
{
    // public $InstitutionStudent;

    // these constants are being used in AdvancedPositionSearchBehavior as well
    // remember to check AdvancedPositionSearchBehavior if these constants are being modified
    const ALL = 0;
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    const STUDENTNOTINSCHOOL = 5;
    const STAFFNOTINSCHOOL = 6;

    private $dashboardQuery;

    public function initialize(array $config): void {
        $this->setTable('security_users');
        $this->setEntityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->hasMany('Identities',        ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities',     ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts',          ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->hasMany('SpecialNeeds',      ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $this->addBehavior('User.User');
        $this->addBehavior('User.UserTab');

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Auth.User.id']);

        $this->toggle('index', false);
        $this->toggle('search', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];

        if ($this->action == 'edit') {
            $toolbarButtons = $this->addBackButton($toolbarButtons);
        }

        // Start POCOR-5188
        $toolbarButtons = $this->addManualButton($toolbarButtons);

        //This check is added to restrict users from editing profile if they don't have any roles assigned.(POCOR-9429)
        $userRoles = $this->AccessControl->getRolesByUser()->toArray();
        if (!$this->AccessControl->isAdmin() && empty($userRoles)) {
            unset($toolbarButtons['edit']);
        }
        
        // End POCOR-5188
        $extra['toolbarButtons'] = $toolbarButtons;
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ;
        $BaseUsers = TableRegistry::getTableLocator()->get('User.Users');
        return $BaseUsers->setUserValidation($validator, $this);
    }


    // POCOR-5684
    public function onGetIdentityNumber(EventInterface $event, Entity $entity){

        // Case 1: if user has only one identity, show the same,
        // Case 2: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.
        // Case 3: if user has more than one identity (no one is linked to nationality), show the first

        $users_ids = TableRegistry::getTableLocator()->get('user_identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->all();

        $users_ids = TableRegistry::getTableLocator()->get('user_identities');
        $user_id_data = $users_ids->find()
        ->select(['number'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->first();

        if(count($user_identities) == 1){
            // Case 1
            return $entity->identity_number = $user_id_data->number;
        }else{
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::getTableLocator()->get('nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::getTableLocator()->get('user_identities');
                $user_id_data_nat = $users_ids->find()
                ->select(['number'])
                ->where([
                    $users_ids->aliasField('security_user_id') => $entity->id,
                    $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                ])
                ->first();
                if($user_id_data_nat != null){
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }

            if(count($nationality_based_ids) > 0){
                // Case 2 - returning value
                return $entity->identity_number = $nationality_based_ids[0]['number'];
            }else{
                // Case 3 - returning value, return again from Case 1
                return $entity->identity_number = $user_id_data->number;
            }
        }
    }

    // POCOR-5684
    public function onGetIdentityTypeID(EventInterface $event, Entity $entity)
    {
        $users_ids = TableRegistry::getTableLocator()->get('user_identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->all();

        $users_ids = TableRegistry::getTableLocator()->get('user_identities');
        $user_id_data = $users_ids->find()
        ->select(['number', 'identity_type_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->first();

        if(count($user_identities) == 1){
            // Case 1
            $users_id_type = TableRegistry::getTableLocator()->get('identity_types');
            $user_id_name = $users_id_type->find()
            ->select(['name'])
            ->where([
                $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
            ])
            ->first();
            return $entity->identity_type_id = $user_id_name->name;
        }else{
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::getTableLocator()->get('nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::getTableLocator()->get('user_identities');
                $user_id_data_nat = $users_ids->find()
                ->select(['number','identity_type_id'])
                ->where([
                    $users_ids->aliasField('security_user_id') => $entity->id,
                    $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                ])
                ->first();
                if($user_id_data_nat != null){
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }
            if(count($nationality_based_ids) > 0){
                // Case 2 - returning value
                $users_id_type = TableRegistry::getTableLocator()->get('identity_types');
                $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                ])
                ->first();
                return $entity->identity_type_id = $user_id_name->name;
            }else{
                // Case 3 - returning value, return again from Case 1
                $users_id_type = TableRegistry::getTableLocator()->get('identity_types');
                $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
                ])
                ->first();
                return $entity->identity_type_id = $user_id_name->name;
            }
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities',
            'MainIdentityTypes',
            'Genders'
        ]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        //print_r($entity);die;
        // Remove back toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['back']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setupTabElements($entity);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // remove the list toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        if (isset($toolbarButtonsArray['list'])) {
            unset($toolbarButtonsArray['list']);
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        if ($entity->is_student) {
            $this->fields['gender_id']['type'] = 'readonly';
            $this->fields['gender_id']['attr']['value'] = $entity->has('gender') ? $entity->gender->name : '';
            $this->fields['gender_id']['value'] = $entity->has('gender') ? $entity->gender->id : '';
        }

        $this->fields['nationality_id']['type'] = 'readonly';
        if (!empty($entity->main_nationality)) {
            $this->fields['nationality_id']['attr']['value'] = $entity->main_nationality->name;
        }

        $this->fields['identity_type_id']['type'] = 'readonly';
        if (!empty($entity->main_identity_type)) {
            $this->fields['identity_type_id']['attr']['value'] = $entity->main_identity_type->name;
        }

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
    }

    private function setupTabElements() {

        $tabElements = $this->controller->getUserTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'first_name') {
            return __('First Name');
        }else if ($field == 'middle_name') {
            return __('Middle Name');
        }else if ($field == 'third_name') {
            return __('Third Name');
        }else if ($field == 'last_name') {
            return __('Last Name');
        } else if ($field == 'gender_id') {
            return  __('Gender');
        }  else if ($field == 'date_of_birth') {
            return  __('Date of Birth');
        }else if ($field == 'photo_content') {
            return  __('Photo Content');
        }else if ($field == 'details') {
            return  __('Details');
        }else if ($field == 'address') {
            return  __('Address');
        }else if ($field == 'postal_code') {
            return  __('Postal Code');
        }else if ($field == 'address_area_id') {
            return  __('Address Area');
        }else if ($field == 'birthplace_area_id') {
            return  __('Birthplace Area');
        }else if ($field == 'birthplace_area_id') {
            return  __('Birthplace Area');
        }
        else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * @param $toolbarButtons
     * @return mixed
     */
    private function addBackButton($toolbarButtons)
    {
        $queryString = $this->getQueryString();
        $queryString = $this->paramsEncode($queryString);
        if ($toolbarButtons->offsetExists('back')) {
            $toolbarButtons['back']['url'][0] = 'view';
            $toolbarButtons['back']['url'][1] = $queryString;
            $toolbarButtons['back']['url']['action'] = 'Personal';
            $toolbarButtons['back']['attr']['title'] = 'Back';
        }
        return $toolbarButtons;
    }

    /**
     * @param $toolbarButtons
     * @return mixed
     */

    private function addManualButton($toolbarButtons)
    {
        $is_manual_exist = $this->getManualUrl('Personal', 'Overview', 'General');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $toolbarButtons['help']['url'] = $is_manual_exist['url'];
            $toolbarButtons['help']['type'] = 'button';
            $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
            $toolbarButtons['help']['attr'] = $btnAttr;
            $toolbarButtons['help']['attr']['title'] = __('Help');
        }
        return $toolbarButtons;
    }


}
