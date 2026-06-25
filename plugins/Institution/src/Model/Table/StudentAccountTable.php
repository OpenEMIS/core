<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentAccountTable extends AppTable {
    public function initialize(array $config): void {
        $this->addBehavior('Institution.InstitutionTab');
        $this->addBehavior('User.Account',
            ['userRole' => 'Students', 'isInstitution' => true, 'permission' => ['Institutions', 'StudentAccount', 'edit']]);
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        return $events;
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
        if ($action == 'view') {
                $institutionId = $this->getInstitutionID();
                $studentId = $this->getStudentID();
                $StudentTable = TableRegistry::getTableLocator()->get('Institution.Students');
                if (! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
                    if (isset($toolbarButtons['edit'])) {
                        unset($toolbarButtons['edit']);
                    }
                }
                // End PHPOE-1897
            }
        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Accounts','Students');
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $toolbarButtons['help']['url'] = $is_manual_exist['url'];
            $toolbarButtons['help']['type'] = 'button';
            $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
            $toolbarButtons['help']['attr'] = $btnAttr;
            $toolbarButtons['help']['attr']['title'] = __('Help');
        }
        // End POCOR-5188
    }

    public function onUpdateFieldUsername(EventInterface $event, array $attr, $action, ServerRequest $request) {
        $editStudentUsername = $this->AccessControl->check(['Institutions', 'StudentAccountUsername', 'edit']);

        if ($editStudentUsername) {
            $attr['type'] = 'string';
            return $attr;
        }
    }

    /**
     * POCOR-7159
     * add data in user_activities table while updating password
    */
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $userActivities = TableRegistry::getTableLocator()->get('User.UserActivities');
        $userTable = TableRegistry::getTableLocator()->get('Security.Users');
        $user = $this->Auth->user();
        $currentTimeZone = date("Y-m-d H:i:s");
        $userId = $user['id'];
        /*$newpassword = $entity->toArray();
        $setPassword = $newpassword['password'];
        $originalPassword = $entity->getOriginal('password');*/
        $newpassword = $entity->extractOriginalChanged($entity->getVisible());
        $setPassword =  $newpassword['password'];

        $securityData = $userTable->find()->where([$userTable->aliasField('id')=>$entity->id])->first()->username;
        $check = strcmp($securityData, $entity->username);
        if($check==0){
            $field = 'password';
            $old = $entity->password;
            $new = $setPassword;
        }else{
            $field = 'username';
            $old = $securityData;
            $new = $entity->username;
        }
        $data = [
                    'model' => 'StudentAccount',
                    'model_reference' => $entity->id,
                    'field' => $field,
                    'field_type' => 'string',
                    'old_value' => $old,
                    'new_value' => $new,
                    'operation' => 'edit',
                    'security_user_id' => $entity->id,
                    'created_user_id' => $userId,
                    'created' => $currentTimeZone,
                ];
        $entity = $userActivities->newEntity($data);
        $save =  $userActivities->save($entity);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'username') {
            return __('Username');
        } elseif ($field == 'last_login') {
            return __('Last Login');
        } elseif ($field == 'roles') {
            return __('Roles');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //function added to  redirect to view after successfully edit account details
    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $errors = $entity->getErrors();
        if (empty($errors)) {
            $this->Alert->success('general.edit.success', ['reset' => true]);
            $session = $this->request->getSession();
            $session->write('successAlert', 'yes');
            $action = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAccount','view',$this->request->getParam('pass.1')];
            return $this->controller->redirect($action);
        } 
    }
    public function viewBeforeAction() {    
        $session = $this->request->getSession();
        if($session->read('successAlert') === 'yes' && empty($session->read('_alert'))){
            $session->delete('successAlert');
            $this->Alert->success('general.edit.success', ['reset' => true]);
        }
    }
}
