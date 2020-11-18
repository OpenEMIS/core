<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
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

    public function initialize(array $config) {
        $this->table('security_users');
        $this->entityClass('User.User');
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

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Auth.User.id']);

        $this->toggle('index', false);
        $this->toggle('search', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ;
        $BaseUsers = TableRegistry::get('User.Users');
        return $BaseUsers->setUserValidation($validator, $this);
    }
    

    // POCOR-5684
    public function onGetIdentityNumber(Event $event, Entity $entity){

        // Case 1: if user has only one identity, show the same, 
        // Case 2: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.
        // Case 3: if user has more than one identity (no one is linked to nationality), show the first

        $users_ids = TableRegistry::get('user_identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->all();
        
        $users_ids = TableRegistry::get('user_identities');
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
            $nationalities = TableRegistry::get('nationalities');
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
                $users_ids = TableRegistry::get('user_identities');
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
    public function onGetIdentityTypeID(Event $event, Entity $entity)
    {
        $users_ids = TableRegistry::get('user_identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->all();
        
        $users_ids = TableRegistry::get('user_identities');
        $user_id_data = $users_ids->find()
        ->select(['number', 'identity_type_id'])
        ->where([                
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->first();

        if(count($user_identities) == 1){
            // Case 1
            $users_id_type = TableRegistry::get('identity_types');
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
            $nationalities = TableRegistry::get('nationalities');
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
                $users_ids = TableRegistry::get('user_identities');
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
                $users_id_type = TableRegistry::get('identity_types');
                $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                ])
                ->first();
                return $entity->identity_type_id = $user_id_name->name;
            }else{
                // Case 3 - returning value, return again from Case 1
                $users_id_type = TableRegistry::get('identity_types');
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

    // POCOR-5684
    // public function onGetIdentityNumber(Event $event, Entity $entity){

    //     // Case 1: if user has only one identity, show the same, 
    //     // Case 2: if user has more than one identity (no one is linked to nationality), show the first
    //     // Case 3: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.

    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_identities = $users_ids->find()
    //     ->select(['number','nationality_id'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->all();
        
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['number'])
    //     ->where([                
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();

    //     if(count($user_identities) == 1){
    //         // Case 1
    //         return $entity->identity_number = $user_id_data->number;
    //     }else{
    //         // Case 2
    //         // check if any user identity, that has nationality ID
    //         $users_ids = TableRegistry::get('user_identities');
    //         $user_identity = $users_ids->find('all',
    //             [
    //                 'fields' => [
    //                     'number',
    //                     'nationality_id',
    //                     'security_user_id'
    //                 ],
    //                 'conditions' => [
    //                     'security_user_id' => $entity->id,
    //                     'nationality_id !=' => 'NULL'
    //                 ]
    //             ]
    //         )->first();
    //         if($user_identity != NULL){
    //             // This is case 2 returning
    //             return $entity->identity_number = $user_identity->number;
    //         }else{
    //             // Get and store all nationalities of the user and store the nationality IDs in an array,
    //             $users_nationality = TableRegistry::get('user_nationalities');
    //             $nationalities = $users_nationality->find()
    //             ->select(['nationality_id','preferred','security_user_id'])
    //             ->where([
    //                 $users_nationality->aliasField('security_user_id') => $entity->id,
    //             ])
    //             ->all();
    //             $nat_ids = [];
    //             foreach ($nationalities as $nat) {
    //                 array_push($nat_ids, $nat->nationality_id);
    //             }
    //             // then for each Nat ID in the array, check if any NAT ID has default Identity and show that Identity
    //             $default_ids = [];
    //             foreach ($nat_ids as $nat_id){
    //                 $nationality = TableRegistry::get('nationalities');
    //                 $default_nationality = $nationality->find('all',
    //                     [
    //                         'fields' => [
    //                             'id',
    //                             'identity_type_id',
    //                             'name'
    //                         ],
    //                         'conditions' => [
    //                             'id' => $nat_id,
    //                             'identity_type_id !=' => 'NULL'
    //                         ]
    //                     ]
    //                 )->first();
    //                 if($default_nationality != NULL){
    //                     array_push($default_ids, $default_nationality->identity_type_id);
    //                 }
    //             }
    //             if(count($default_ids)  == 0){
    //                 // return again from Case 1
    //                 return $entity->identity_number = $user_id_data->number;
    //             }else{
    //                 // Case 3
    //                 // check if any user identity is related to default id from the array
    //                 foreach ($default_ids as $def_id) {
    //                     $user_identity = $users_ids->find('all',
    //                         [
    //                             'fields' => [
    //                                 'number',
    //                                 'nationality_id',
    //                                 'security_user_id',
    //                                 'identity_type_id'
    //                             ],
    //                             'conditions' => [
    //                                 'security_user_id' => $entity->id,
    //                                 'identity_type_id' => $def_id
    //                             ]
    //                         ]
    //                     )->first();
    //                     if($user_identity == null){
    //                         return $entity->identity_number = $user_id_data->number;
    //                     }else{
    //                         return $entity->identity_number = $user_identity->number;
    //                     }
    //                 }
    //             }
    //         }
    //     }
        
    // }

    // POCOR-5684
    // public function onGetIdentityTypeID(Event $event, Entity $entity)
    // {
    //     // Case 1: if user has only one identity, show the same, 
    //     // Case 2: if user has more than one identity (no one is linked to nationality), show the first
    //     // Case 3: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.

    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_identities = $users_ids->find()
    //     ->select(['number','nationality_id'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->all();
        
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['identity_type_id'])
    //     ->where([                
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();
    //     if(count($user_identities) == 1){
    //         // Case 1
    //         // Get Identity Type Name
            
    //         $users_id_type = TableRegistry::get('identity_types');
    //         $user_id_name = $users_id_type->find()
    //         ->select(['name'])
    //         ->where([
    //             $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
    //         ])
    //         ->first();
    //         return $entity->identity_type_id = $user_id_name->name;
    //     }else{
    //         // Case 2
    //         // check if any user identity, that has nationality ID
    //         $users_ids = TableRegistry::get('user_identities');
    //         $user_identity = $users_ids->find('all',
    //             [
    //                 'fields' => [
    //                     'number',
    //                     'nationality_id',
    //                     'security_user_id'
    //                 ],
    //                 'conditions' => [
    //                     'security_user_id' => $entity->id,
    //                     'nationality_id !=' => 'NULL'
    //                 ]
    //             ]
    //         )->first();
    //         if($user_identity != NULL){
    //             // This is case 2 returning
    //             // return $entity->identity_number = $user_identity->number;
    //             $users_id_type = TableRegistry::get('identity_types');
    //             $user_id_name = $users_id_type->find()
    //             ->select(['name'])
    //             ->where([
    //                 $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
    //             ])
    //             ->first();
    //             return $entity->identity_type_id = $user_id_name->name;
    //         }else{
    //             // Get and store all nationalities of the user and store the nationality IDs in an array,
    //             $users_nationality = TableRegistry::get('user_nationalities');
    //             $nationalities = $users_nationality->find()
    //             ->select(['nationality_id','preferred','security_user_id'])
    //             ->where([
    //                 $users_nationality->aliasField('security_user_id') => $entity->id,
    //             ])
    //             ->all();
    //             $nat_ids = [];
    //             foreach ($nationalities as $nat) {
    //                 array_push($nat_ids, $nat->nationality_id);
    //             }
    //             // then for each Nat ID in the array, check if any NAT ID has default Identity and show that Identity
    //             $default_ids = [];
    //             foreach ($nat_ids as $nat_id){
    //                 $nationality = TableRegistry::get('nationalities');
    //                 $default_nationality = $nationality->find('all',
    //                     [
    //                         'fields' => [
    //                             'id',
    //                             'identity_type_id',
    //                             'name'
    //                         ],
    //                         'conditions' => [
    //                             'id' => $nat_id,
    //                             'identity_type_id !=' => 'NULL'
    //                         ]
    //                     ]
    //                 )->first();
    //                 if($default_nationality != NULL){
    //                     array_push($default_ids, $default_nationality->identity_type_id);
    //                 }
    //             }
    //             if(count($default_ids)  == 0){
    //                 // return again from Case 1
    //                 // return $entity->identity_number = $user_id_data->number;
    //                 $users_id_type = TableRegistry::get('identity_types');
    //                         $user_id_name = $users_id_type->find()
    //                         ->select(['name'])
    //                         ->where([
    //                             $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
    //                         ])
    //                         ->first();
    //                         return $entity->identity_type_id = $user_id_name->name;
    //             }else{
    //                 // Case 3
    //                 // check if any user identity is related to default id from the array
    //                 foreach ($default_ids as $def_id) {
    //                     $user_identity = $users_ids->find('all',
    //                         [
    //                             'fields' => [
    //                                 'number',
    //                                 'nationality_id',
    //                                 'security_user_id',
    //                                 'identity_type_id'
    //                             ],
    //                             'conditions' => [
    //                                 'security_user_id' => $entity->id,
    //                                 'identity_type_id' => $def_id
    //                             ]
    //                         ]
    //                     )->first();
    //                     if($user_identity == null){
    //                         // return $entity->identity_number = $user_id_data->number;
    //                         $users_id_type = TableRegistry::get('identity_types');
    //                         $user_id_name = $users_id_type->find()
    //                         ->select(['name'])
    //                         ->where([
    //                             $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
    //                         ])
    //                         ->first();
    //                         return $entity->identity_type_id = $user_id_name->name;
    //                     }else{
    //                         // return $entity->identity_number = $user_identity->number;
    //                         $users_id_type = TableRegistry::get('identity_types');
    //                         $user_id_name = $users_id_type->find()
    //                         ->select(['name'])
    //                         ->where([
    //                             $users_id_type->aliasField('id') => $user_identity->identity_type_id,
    //                         ])
    //                         ->first();
    //                         return $entity->identity_type_id = $user_id_name->name;
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities',
            'MainIdentityTypes',
            'Genders'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Remove back toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['back']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setupTabElements($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // remove the list toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        if (array_key_exists('list', $toolbarButtonsArray)) {
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

    private function setupTabElements($entity) {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

        $options = [
            // 'userRole' => 'Student',
            // 'action' => $this->action,
            // 'id' => $id,
            // 'userId' => $entity->id
        ];

        $tabElements = $this->controller->getUserTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
