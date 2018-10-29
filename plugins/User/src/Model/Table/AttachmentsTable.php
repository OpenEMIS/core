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
    public function initialize(array $config)
    {
        $this->table('user_attachments');
        parent::initialize($config);

        $this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all', 'useDefaultName' => true]);
        $this->addBehavior('User.SetupTab');

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

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
            $this->behaviors()->get('ControllerAction')->config([
                'actions' => [
                    'download' => ['show' => true] //to show download on toolbar
                ]
            ]);
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['type' => 'binary', 'visible' => ['edit' => true]]);

        $this->field('security_roles', [
            'type' => 'chosenSelect',
            'placeholder' => __('Add specific role to share or leave empty to share to All')
        ]);

        $this->setFieldOrder([
            'name', 'description', 'date_on_file', 'file_content', 'security_roles'
        ]);
    }

/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_type');
        $this->field('created', ['visible' => true]);
        $this->field('created_user_id', ['visible' => true]);

        $this->setFieldOrder([
            'name', 'description', 'file_type', 'date_on_file', 'security_roles', 'created_user_id', 'created'
        ]);
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
                    [$AttachmentsRoles->alias() => $AttachmentsRoles->table()],
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

/******************************************************************************************************************
**
** edit action logics
**
******************************************************************************************************************/
    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
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
            $buttons['download']['url']['action'] = $this->alias;
            $buttons['download']['url'][0] = 'download';
            $buttons['download']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
        }

        return $buttons;
    }
}
