<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class AttachmentsTable extends ControllerActionTable 
{
	public function initialize(array $config) {
		$this->table('user_attachments');
		parent::initialize($config);

        $this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all', 'useDefaultName' => true]);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		
		$this->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'user_attachments_roles',
            'foreignKey' => 'user_attachment_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'User.AttachmentRoles',
            'dependent' => true
        ]);

        //change behaviour config
        if ($this->behaviors()->has('ControllerAction')) {
            $this->behaviors()->get('ControllerAction')->config([
                'actions' => [
                    'download' => ['show' => true] //to show download on toolbar
                ]
            ]);
        }
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
        // pr($this->associations());
		$this->field('security_user_id',	['type' => 'hidden', 'visible' => ['edit' => true]]);

		$this->field('modified',			['visible' => ['view' => true]]);
		$this->field('modified_user_id',	['visible' => ['view' => true]]);
		$this->field('created', 			['type' => 'datetime', 'visible' => ['index'=>true, 'view'=>true]]);
		$this->field('created_user_id',		['visible' => ['view' => true]]);

		$this->field('file_name',			['visible' => false]);
		$this->field('file_content',		['type' => 'binary', 'visible' => ['edit' => true]]);
		$this->field('date_on_file',		['type' => 'date', 'visible' => true]);

		$this->field('name',				['type' => 'string', 'visible' => true]);
		$this->field('description',			['type' => 'text', 'visible' => true]);

		$this->field('file_type',			['type' => 'string', 'visible' => ['index'=>true]]);

		$this->field('security_roles', [
			'type' => 'chosenSelect',
			'placeholder' => __('Add specific role to share or leave empty to share to All')
		]);

        $this->setFieldOrder([
            'name', 'description', 'file_content', 'date_on_file', 'security_roles'
        ]);
	}

/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra) 
    {
		$this->setFieldOrder([
			'name', 'description', 'file_type', 'date_on_file', 'security_roles', 'created'
		]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //if not super admin then get the security role for filtering purpose
        if (!$this->AccessControl->isAdmin()) {
            $securityRole = $this->AccessControl->getRolesByUser($this->Auth->user('id'))->toArray();
            $securityRole = $securityRole[0]['security_role_id'];

            $query->leftJoin(
                    ['AttachmentRoles' => 'user_attachments_roles'],
                    ['AttachmentRoles.user_attachment_id = ' . $this->aliasField('id')])
                ->where([
                    'OR' => [
                        ['AttachmentRoles.id IS NULL'],
                        ['AttachmentRoles.security_role_id' => $securityRole]
                    ]
                ]);
        }

        $query->contain(['SecurityRoles']);
    }

	private function setupTabElements() {
		$options = [
			'userRole' => '',
		];

		switch ($this->controller->name) {
			case 'Students':
				$options['userRole'] = 'Students';
				break;
			case 'Staff':
				$options['userRole'] = 'Staff';
				break;
		}

		$tabElements = $this->controller->getUserTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$this->setupTabElements();
	}


/******************************************************************************************************************
**
** view action logics
**
******************************************************************************************************************/
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
    {
    	$this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
    	if (!empty($entity->modified_user_id)) {
	    	$this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
	    }
        
        return $entity;
    }


/******************************************************************************************************************
**
** edit action logics
**
******************************************************************************************************************/
    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$query->contain(['SecurityRoles']);
	}
	
    public function editBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['date_on_file']['visible'] = false;
    }

/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetFileType(Event $event, Entity $entity) {
        return $this->getFileTypeForView($entity->file_name);
	}

    public function onUpdateFieldSecurityRoles(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Security.SecurityRoles')->getSystemRolesList();
		}

		return $attr;
	}
/******************************************************************************************************************
**
** adding download button to index page
**
******************************************************************************************************************/
	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) 
    {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        
        $downloadAccess = $this->AccessControl->check([$this->controller->name, 'Attachments', 'download']);

        if ($downloadAccess) {
            $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

            $buttons['download']['label'] = '<i class="kd-download"></i>' . __('Download');
            $buttons['download']['attr'] = $indexAttr;
            $buttons['download']['url']['action'] = $this->alias.'/download';
            $buttons['download']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
        }

        return $buttons;
	}
}
