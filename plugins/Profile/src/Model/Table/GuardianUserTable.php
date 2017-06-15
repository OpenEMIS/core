<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;
use Directory\Model\Table\DirectoriesTable as UserTable;

class GuardianUserTable extends UserTable
{
    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (!$entity->errors()) {
            $sessionKey = 'Student.Guardians.new';
            if ($this->Session->check($sessionKey)) {
                $guardianData = $this->Session->read($sessionKey);
                $guardianData['guardian_id'] = $entity->id;

                $Guardians = TableRegistry::get('Student.Guardians');
                $Guardians->save($Guardians->newEntity($guardianData));
                $this->Session->delete($sessionKey);
            }
            $event->stopPropagation();

            $controller = $this->controller->name;
            $action = 'ProfileGuardians';

            $redirect = ['plugin' => $this->controller->plugin, 'controller' => $controller, 'action' => $action, 'index'];

            return $this->controller->redirect($redirect);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $Guardians = TableRegistry::get('Profile.Guardians');
        $params = $this->paramsDecode($this->request->pass[1]);
        $profileGuardianId = array_key_exists('ProfileGuardians.id', $params) ? $params['ProfileGuardians.id']: null;

        if ($entity->has('guardian_relation_id')) {
            // Update the guardian_relation table
            $Guardians->updateAll(
                ['guardian_relation_id' => $entity->guardian_relation_id],
                ['id' => $profileGuardianId]
            );
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $extra['toolbarButtons']['back']['url']['action'] = 'ProfileGuardians';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->request->query['user_type'] = UserTable::GUARDIAN;
        $this->field('guardian_relation_id', ['before' => 'openemis_no']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['nationality_id']['type'] = 'readonly';
        if (!empty($entity->main_nationality)) {
            $this->fields['nationality_id']['attr']['value'] = $entity->main_nationality->name;
        }

        $this->fields['identity_type_id']['type'] = 'readonly';
        if (!empty($entity->main_identity_type)) {
            $this->fields['identity_type_id']['attr']['value'] = $entity->main_identity_type->name;
        }

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.

        $extra['toolbarButtons']['list']['url']['action'] = 'ProfileGuardians';
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $options['type'] = 'student';

        $this->field('user_type', ['type' => 'hidden', 'value' => UserTable::GUARDIAN]);
        $this->field('nationality_id', ['visible' => 'false']);
        $this->field('identity_type_id', ['visible' => 'false']);
        $this->field('identity_number', ['visible' => 'false']);

        $extra['toolbarButtons']['back']['url']['action'] = 'ProfileGuardians';
        $extra['toolbarButtons']['back']['url'][0] = 'add';
    }

    public function onGetGuardianRelationId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $Guardians = TableRegistry::get('Profile.Guardians');
            $GuardianRelations = TableRegistry::get('Student.GuardianRelations');

            $params = $this->paramsDecode($this->request->pass[1]);
            $profileGuardianId = array_key_exists('ProfileGuardians.id', $params) ? $params['ProfileGuardians.id']: null;

            if (!is_null($profileGuardianId)) {
                $guardianRecords = $Guardians->find()
                    ->contain(['GuardianRelations'])
                    ->where([$Guardians->aliasField('id') => $profileGuardianId])
                    ->first();

                $guardianRelationName = $guardianRecords->guardian_relation->name;
            }

            return $guardianRelationName;
        }
    }

    public function onUpdateFieldGuardianRelationId(Event $event, array $attr, $action, Request $request)
    {
        $Guardians = TableRegistry::get('Profile.Guardians');
        $GuardianRelations = TableRegistry::get('Student.GuardianRelations');

        $attr['type'] = 'select';

        if (!empty($request->pass[1]) && ($action == 'add' || $action == 'edit')) {
            $params = $this->paramsDecode($request->pass[1]);

            $guardianRelationId = array_key_exists('guardian_relation_id', $params) ? $params['guardian_relation_id']: null;
            $guardianId = array_key_exists('id', $params) ? $params['id']: null;
            $profileGuardianId = array_key_exists('ProfileGuardians.id', $params) ? $params['ProfileGuardians.id']: null;

            if (is_null($guardianRelationId) && !is_null($profileGuardianId)) {
                $guardianRecords = $Guardians->find()
                    ->contain(['GuardianRelations'])
                    ->where([$Guardians->aliasField('id') => $profileGuardianId])
                    ->first();

                $guardianRelationId = $guardianRecords->guardian_relation->id;
            }

            $relationOptions = $GuardianRelations
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->toArray();

            $attr['select'] = false;
            $attr['options'] = $relationOptions;
            $attr['value'] = $guardianRelationId;
            $attr['attr']['value'] = $guardianRelationId;
        }

        return $attr;
    }
}
