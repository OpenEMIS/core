<?php
namespace Student\Model\Table;

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

class GuardianUserTable extends UserTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Guardian.afterSave'] = 'guardianAfterSave';
        return $events;
    }

    public function guardianAfterSave(Event $event, $guardian)
    {
        if ($guardian->isNew()) {
            $this->updateAll(['is_guardian' => 1], ['id' => $guardian->guardian_id]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $sessionKey = 'Student.Guardians.new';

        if ($this->Session->check($sessionKey)) {
            $guardianData = $this->Session->read($sessionKey);
            $guardianRelationId = $guardianData['guardian_relation_id']; 

            $GuardianRelationsTable = TableRegistry::get('Student.GuardianRelations');
            $guardianRelationEntity = $GuardianRelationsTable
                ->find()
                ->matching('Genders')
                ->where([$GuardianRelationsTable->aliasField('id') => $guardianRelationId])
                ->first();

            if($guardianRelationEntity) {
                $entity->gender_id = $guardianRelationEntity->gender_id;
                $this->field('gender_id', ['type' => 'readonly', 'attr' => ['value' => $guardianRelationEntity->_matchingData['Genders']->name]]);
            }
        }
    }

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
            $action = 'Guardians';

            if ($controller == 'Directories') { //this is for Directories/StudentGuardians/ (adding guardian for student through directories)
                $action = 'StudentGuardians';
            }

            $redirect = ['plugin' => $this->controller->plugin, 'controller' => $controller, 'action' => $action, 'index'];

            return $this->controller->redirect($redirect);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);

        unset($extra['toolbarButtons']['back']);

        if ($extra['toolbarButtons']->offsetExists('export')) {
            unset($extra['toolbarButtons']['export']);
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->request->query['user_type'] = UserTable::GUARDIAN;

        //parent::hideOtherInformationSection($this->controller->name, $this->action);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getStudentGuardianTabElements($options);
        $this->controller->set('tabElements', $tabElements);

        $this->field('user_type', ['type' => 'hidden', 'value' => UserTable::GUARDIAN]);
        $this->field('nationality_id', ['visible' => 'false']);
        $this->field('identity_type_id', ['visible' => 'false']);
        $this->field('identity_number', ['visible' => 'false']);

        $this->field('username', ['visible' => true, 'after' => 'other_information_section']);
        $this->field('password', ['visible' => true, 'after' => 'username']);

        $backUrl = $this->controller->getStudentGuardianTabElements();
        $extra['toolbarButtons']['back']['url']['action'] = $backUrl['Guardians']['url']['action'];
        $extra['toolbarButtons']['back']['url'][0] = 'add';

        $this->controller->set('selectedAction', 'Guardians');
    }

    public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $openemisNo = $this->getUniqueOpenemisId();

            $attr['value'] = $openemisNo;
            $attr['attr']['value'] = $openemisNo;
        }

        return $attr;
    }

    public function onUpdateFieldUsername(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $openemisNo = $this->getUniqueOpenemisId();

            $attr['value'] = $openemisNo;
            $attr['attr']['value'] = $openemisNo;
        }

        return $attr;
    }

    public function onUpdateFieldPassword(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            // Read the number of length of password from system config
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $password = $ConfigItems->getAutoGeneratedPassword();

            $attr['value'] = $password;
            $attr['attr']['value'] = $password;

            // setting the tooltip message
            $tooltipMessagePassword = $this->getMessage('Users.tooltip_message_password');

            $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
            $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label
            $attr['attr']['label']['text'] = __(Inflector::humanize($attr['field'])) . $this->tooltipMessage($tooltipMessagePassword);
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
        $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];

        $tabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('General')]
        ];
        $action = 'Guardians';
        $actionUser = $this->alias();
        if ($this->controller->name == 'Directories') {
            $action = 'StudentGuardians';
            $actionUser = 'StudentGuardianUser';
        }

        $encodedParam = $this->request->params['pass'][1];
        $ids = $this->paramsDecode($encodedParam);

        $guardianId = $ids['id'];
        $studentGuardiansId = $ids['StudentGuardians.id'];

        $tabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $studentGuardiansId])]);
        $tabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $entity->id, 'StudentGuardians.id' => $studentGuardiansId])]);
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
