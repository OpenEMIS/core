<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class AttachmentsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_attachments');
        parent::initialize($config);

        $this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all', 'useDefaultName' => true]);
        $this->addBehavior('User.SetupTab'); //POCOR-6756

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        //START:POCOR-5067
        $this->belongsTo('StaffAttachmentTypes', ['className' => 'Staff.StaffAttachmentTypes', 'foreignKey' => 'staff_attachment_type_id']);
        $this->belongsTo('StudentAttachmentTypes', ['className' => 'Student.StudentAttachmentTypes', 'foreignKey' => 'student_attachment_type_id']);
        //END:POCOR-5067
        $this->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'user_attachments_roles',
            'foreignKey' => 'user_attachment_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'User.AttachmentsRoles',
            'dependent' => true
        ]);

                //change behaviour config
        if ($this->behaviors()->has('ControllerAction')) {
            $controllerActionBehavior = $this->behaviors()->get('ControllerAction');
            $controllerActionBehavior->setConfig(['actions' => ['download' => ['show' => true]]]);
        }
    }

    //START:POCOR-5067
    public function validationDefault(Validator $validator): Validator
    {
        $UserTable = TableRegistry::getTableLocator()->get('User.Users');
        $userId = $this->getUserID();
        $user = $UserTable->find()->where(['id'=>$userId])->first();
        if($user->is_staff == 1){
            $validator->setProvider('custom', $this)->requirePresence('staff_attachment_type_id', 'create')->notEmpty('staff_attachment_type_id');
        }elseif($user->is_student == 1){
            $validator->setProvider('custom', $this)->requirePresence('student_attachment_type_id', 'create')->notEmpty('student_attachment_type_id');
        }
        return $validator;
    }
    //END:POCOR-5067

    public function beforeAction(Event $event, ArrayObject $extra)
    { 
        
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['type' => 'binary', 'visible' => ['edit' => true]]);

        $this->field('security_roles', [
            'type' => 'chosenSelect',
            'placeholder' => __('Add specific role to share or leave empty to share to All')
        ]);

        $this->field('security_roles', ['attr' => ['label' => __('Shared')]]);

    }

/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('description', ['visible' => false]);//POCOR-5067
        $this->field('file_type', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('date_on_file', ['visible' => true]);

        $this->field('name', ['visible' => true]);
        $this->field('created', ['visible' => true]);
        $this->field('created_user_id', ['visible' => true]);
        $this->field('security_roles', ['attr' => ['label' => __('Shared')]]);
        $UserTable = TableRegistry::getTableLocator()->get('User.Users');
        $queryString = $this->ControllerAction->getQueryString();
        // $user = $UserTable->find()->where(['id'=>$queryString['security_user_id']])->first(); // POCOR-7485
        if($user->is_staff == 1){
            $this->field('staff_attachment_type_id',  ['attr' => ['label' => __('Type')],'visible' => true]);
            $this->field('student_attachment_type_id', ['visible' => false]);
            $this->setFieldOrder([
                'name','staff_attachment_type_id','file_content','date_on_file','security_roles','created_user_id','created'
            ]);
        }elseif($user->is_student == 1){
            $this->field('student_attachment_type_id', [['attr' => ['label' => __('Type')]],'visible' => true]);
            $this->field('staff_attachment_type_id', ['visible' => false]);
            $this->setFieldOrder([
                'name','student_attachment_type_id','file_content','date_on_file','security_roles','created_user_id','created'
            ]);
        }

        // Start POCOR-5188
		if($this->request->getParam('controller') == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Attachments','Students - General');       
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
		}else if($this->request->getParam('controller') == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Attachments','Staff - General');       
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
		}elseif($this->request->getParam('controller') == 'Directories'){ 
			$is_manual_exist = $this->getManualUrl('Directory','Attachments','General');       
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

		}elseif($this->request->getParam('controller') == 'Profiles'){ 
            $is_manual_exist = $this->getManualUrl('Personal','Attachments','General');       
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //if not super admin then get the security role for filtering purpose
        if (!$this->AccessControl->isAdmin()) {
            $AttachmentsRoles = TableRegistry::get('User.AttachmentsRoles');
            $userId = $this->Auth->user('id');

            $securityRoles = $this->AccessControl->getRolesByUser($userId)->toArray();
            $securityRoleIds = [];
            foreach ($securityRoles as $key => $value) {
                $securityRoleIds[] = $value->security_role_id;
            }

            $OR = [
                [$AttachmentsRoles->aliasField('id IS NULL')]
            ];

            if (!empty($securityRoleIds)) {
               $OR[] = [$AttachmentsRoles->aliasField('security_role_id IN') => $securityRoleIds];
            }

            $query
                ->leftJoin(
                    [$AttachmentsRoles->getAlias() => $AttachmentsRoles->getTable()],
                    [$AttachmentsRoles->aliasField('user_attachment_id = ') . $this->aliasField('id')]
                )
                ->where([
                    'OR' => [
                        'OR' => $OR,
                        $this->aliasField('created_user_id') => $userId //show to the creator
                    ]
                ])
                ->distinct();
        }

        $query->contain(['SecurityRoles']);
    }
    //START:POCOR-5067
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'student_attachment_type_id':
                return __('Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
   // End:POCOR-5067
/******************************************************************************************************************
**
** edit action logics
**
******************************************************************************************************************/
    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //START:POCOR-5067
        $this->field('security_roles', ['attr' => ['label' => __('Shared')]]);
        $this->field('modified_user_id', ['attr' => ['label' => __('Modified By')]]);
        $this->field('modified', ['attr' => ['label' => __('Modified On')]]);
        $this->field('created_user_id', ['attr' => ['label' => __('Created By')]]);
        $this->field('created', ['attr' => ['label' => __('Created On')]]);
        $this->field('student_attachment_type_id', ['attr' => ['label' => __('Type')]]);

        $UserTable = TableRegistry::get('User.Users');
        $userId = $this->getUserID();
        $user = $UserTable->find()->where(['id'=>$userId])->first();

        if($user->is_staff == 1){
            $this->field('student_attachment_type_id', ['visible' => false]);
            $this->field('staff_attachment_type_id', ['attr' => ['label' => __('Type')],'visible' => true]);
            $this->setFieldOrder([
                'name', 'staff_attachment_type_id','description',  'date_on_file','file_content'
            ]);
        }elseif($user->is_student == 1){
            $this->field('student_attachment_type_id', ['attr' => ['label' => __('Type')],'visible' => true]);
            $this->field('staff_attachment_type_id', ['visible' => false]);
            
            $this->setFieldOrder([
                'name', 'student_attachment_type_id','description',  'date_on_file','file_content'
            ]);
        }
        //END:POCOR-5067
        $query->contain(['SecurityRoles']);
        
        
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['date_on_file']['visible'] = true;
    }

/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
    public function onGetFileType(Event $event, Entity $entity)
    {
        return $this->getFileTypeForView($entity->file_name);
    }

    public function onUpdateFieldSecurityRoles(Event $event, array $attr, $action, ServerRequest $request){
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = TableRegistry::get('Security.SecurityRoles')->getSystemRolesList();
        }

        return $attr;
    }

/******************************************************************************************************************
**
** add/Edit action page //START:POCOR-5067
**
******************************************************************************************************************/
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $UserTable = TableRegistry::getTableLocator()->get('User.Users');
        $userId = $this->getUserID();
        $user = $UserTable->find()->where(['id'=>$userId])->first();

        if($user->is_staff == 1){

            $staffAttachmentTypesTable = TableRegistry::getTableLocator()->get('Staff.StaffAttachmentTypes');
            $staffAttachmentTypeOptions = $staffAttachmentTypesTable->find('list',['keyField'=>'id','valueField'=>'name'])->toArray();
            $this->fields['staff_attachment_type_id']['type'] = 'select';
            $this->fields['staff_attachment_type_id']['default'] = '1';
            $this->fields['staff_attachment_type_id']['options'] = $staffAttachmentTypeOptions;
            $this->fields['staff_attachment_type_id']['required'] = true;
            $this->field('staff_attachment_type_id', ['required' => true,'attr' => ['label' => __('Type')]]);
            $this->field('student_attachment_type_id', ['visible' => false]);
            $this->setFieldOrder([
                'name', 'staff_attachment_type_id','description',  'date_on_file','file_content'
            ]);

        }elseif($user->is_student == 1){
            $studentAttachmentTypesTable = TableRegistry::get('student_attachment_types');
            $studentAttachmentTypeOptions = $studentAttachmentTypesTable->find('list',['keyField'=>'id','valueField'=>'name'])->toArray();
            $this->fields['student_attachment_type_id']['type'] = 'select';
            $this->fields['student_attachment_type_id']['default'] = '1';
            $this->fields['student_attachment_type_id']['options'] = $studentAttachmentTypeOptions;
            $this->fields['student_attachment_type_id']['required'] = true;
            $this->field('student_attachment_type_id', ['attr' => ['label' => __('Type')],'required']);
            $this->setFieldOrder([
                'name', 'student_attachment_type_id','description',  'date_on_file','file_content'
            ]);
            $this->field('staff_attachment_type_id', ['visible' => false]);
        }
        $this->field('security_roles', ['attr' => ['label' => __('Shared')]]);
    }
    //END:POCOR-5067

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $userId = $this->getUserID();
        $entity['security_user_id'] = $userId;
    }

    /******************************************************************************************************************
     **
     ** adding download button to index page
     **
     ******************************************************************************************************************/
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons = $this->fixProfileActionButtons($entity, $buttons);
        $downloadAccess = $this->AccessControl->check([$this->controller->getName(), 'Attachments', 'download']);
        if ($downloadAccess) {
            $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

            $buttons['download']['label'] = '<i class="kd-download"></i>' . __('Download');
            $buttons['download']['attr'] = $indexAttr;
            $buttons['download']['url']['action'] = $this->alias;
            $buttons['download']['url'][0] = 'download';
            $buttons['download']['url'][1] = $this->paramsEncode(['id' => $entity->id, 'security_user_id' => $entity->security_user_id]);
        }

        return $buttons;
    }

    /**
     * @return |null
     */
    private function getUserID()
    {
        $queryString = $this->getQueryString();
        $userId = null;
        if (!$userId && isset($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        }
        if (!$userId && isset($queryString['user_id'])) {
            $userId = $queryString['user_id'];
        }
        if (!$userId) {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        return $userId;
    }

    /**
     * @param Entity $entity
     * @param array $buttons
     * @return array
     */
    private function fixProfileActionButtons(Entity $entity, array $buttons): array
    {
        $userID = $this->getUserID();
        $actions = ['view', 'edit'];
        foreach ($actions as $action) {
            if (isset($buttons[$action])) {
                $url = $buttons[$action]['url'];
                if ($url['plugin'] == 'Profile' && $url['controller'] == 'Profiles' && $url['action'] == 'Attachments') {
                    if (isset($url[2])) {
                        unset($url[2]);
                    }
                    $queryString = $this->getQueryString();
                    $queryString['id'] = $entity->id;
                    $queryString['user_id'] = $userID;
                    $queryString['language_id'] = $entity->language_id;
                    $queryString['security_user_id'] = $userID;
                    $url[1] = $this->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                }
            }
        }
//                die('<pre>' . print_r($entity, true));
//                die('<pre>' . print_r($buttons, true));
        return $buttons;
    }

}
