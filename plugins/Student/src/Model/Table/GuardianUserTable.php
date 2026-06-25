<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Directory\Model\Table\DirectoriesTable as UserTable;

class GuardianUserTable extends UserTable {
    public function initialize(array $config):void
    {
        parent::initialize($config);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Guardians' =>['student_id', 'institution_id']
            ]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Guardian.afterSave'] = 'guardianAfterSave';
        return $events;
    }

    public function guardianAfterSave(EventInterface $event, $guardian)
    {
        if ($guardian->isNew()) {
            $this->updateAll(['is_guardian' => 1], ['id' => $guardian->guardian_id]);
        }
    }

    public function addOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $sessionKey = 'Student.Guardians.new';
        if ($this->Session->check($sessionKey)) {
            $guardianData = $this->Session->read($sessionKey);

            if (isset($guardianData['guardian_relation_id'])) {
                $entity->guardian_relation_id = $guardianData['guardian_relation_id'];
            }
        }
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (!$entity->errors()) {
            $sessionKey = 'Student.Guardians.new';
            if ($this->Session->check($sessionKey)) {
                $guardianData = $this->Session->read($sessionKey);
                $guardianData['guardian_id'] = $entity->id;

                $Guardians = TableRegistry::getTableLocator()->get('Student.Guardians');
                $Guardians->save($Guardians->newEntity($guardianData));
                $this->Session->delete($sessionKey);
            }
            $event->stopPropagation();

            $controller = $this->controller->getName();
            $action = 'Guardians';

            if ($controller == 'Directories') { //this is for Directories/StudentGuardians/ (adding guardian for student through directories)
                $action = 'StudentGuardians';
            }

            $redirect = ['plugin' => $this->controller->getPlugin(), 'controller' => $controller, 'action' => $action, 'index'];

            return $this->controller->redirect($redirect);
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);

        unset($extra['toolbarButtons']['back']);

        if ($extra['toolbarButtons']->offsetExists('export')) {
            unset($extra['toolbarButtons']['export']);
        }
    }

    //POCOR-7982
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if(!empty($entity->date_of_death)){ //POCOR-8059
            if(isset($entity->dod_range)){
                $event->stopPropagation();
                $this->Alert->warning('general.dodmsg' , ['reset' => true]);
                $url = $this->url('edit');
                return $this->controller->redirect($url);
            }
        }
    }
    //POCOR-7982

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        // MUST set user_type to request query before call parent's beforeAction
       // $this->request->query['user_type'] = UserTable::GUARDIAN;
        $this->request = $this->request->withQueryParams(
            array_merge($this->request->getQueryParams(), ['user_type' => UserTable::GUARDIAN])
        );
        parent::beforeAction($event, $extra);
        //parent::hideOtherInformationSection($this->controller->getName(), $this->action);
    }

    // POCOR-5684
    public function onGetIdentityNumber(EventInterface $event, Entity $entity){

        $users_ids = TableRegistry::getTableLocator()->get('User.Identities');
        $user_identities = $users_ids->find()
        ->select(['number','nationality_id'])
        ->where([
            $users_ids->aliasField('security_user_id') => $entity->id,
        ])
        ->all();

        $users_ids = TableRegistry::getTableLocator()->get('User.Identities');
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

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);

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

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        parent::addAfterAction($event, $entity, $extra);

        $options['type'] = 'student';
        $tabElements = $this->controller->getStudentGuardianTabElements($options);
        $this->controller->set('tabElements', $tabElements);

        $this->field('guardian_relation_id', ['type' => 'hidden']);
        $this->field('gender_id', ['after' => 'preferred_name', 'entity' => $entity]);
        $this->field('user_type', ['type' => 'hidden', 'value' => UserTable::GUARDIAN]);

        $backUrl = $this->controller->getStudentGuardianTabElements();
        $extra['toolbarButtons']['back']['url']['action'] = $backUrl['Guardians']['url']['action'];
        $extra['toolbarButtons']['back']['url'][0] = 'add';

        $this->controller->set('selectedAction', 'Guardians');
    }

    public function onUpdateFieldGenderId(EventInterface $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $entity = $attr['entity'];

            if ($entity->has('guardian_relation_id')) {
                $GuardianRelationsTable = TableRegistry::getTableLocator()->get('Student.GuardianRelations');
                $guardianRelationEntity = $GuardianRelationsTable
                    ->find()
                    ->matching('Genders')
                    ->where([$GuardianRelationsTable->aliasField('id') => $entity->guardian_relation_id])
                    ->first();

                if ($guardianRelationEntity) {
                    $attr['type'] = 'readonly';
                    $attr['value'] = $guardianRelationEntity->gender_id;
                    $attr['attr']['value'] = $guardianRelationEntity->_matchingData['Genders']->name;
                }
            }
        }

        return $attr;
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    private function setupTabElements($entity)
    {
        $guardianId = $entity->id;

        if ($this->controller->getName() == 'Directories') {
            $tabElements = $this->controller->getUserTabElements(['id' => $guardianId, 'userRole' => 'Guardians']);
        } elseif ($this->controller->getName() == 'Students') {
            $tabElements = $this->getGuardianTabElements(['id' => $guardianId, 'userRole' => 'Guardians']);
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }
}
