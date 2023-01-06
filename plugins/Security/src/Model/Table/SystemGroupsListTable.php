<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;
use Cake\Utility\Text;


class SystemGroupsListTable extends ControllerActionTable
{
    use MessagesTrait;
    use HtmlTrait;

    public function initialize(array $config)
    {
        $this->table('security_group_users');
        parent::initialize($config);

        // $this->belongsToMany('Users', [
        //     'className' => 'Security.Users',
        //     'joinTable' => 'security_group_users',
        //     'foreignKey' => 'security_group_id',
        //     'targetForeignKey' => 'security_user_id',
        //     'through' => 'Security.SecurityGroupUsers',
        //     'dependent' => true
        // ]);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles', 'foreignKey' => 'security_role_id']);

        $this->toggle('search', false);
        $this->toggle('add', false);
        $this->toggle('view', false);
        $this->toggle('edit', false);

        $this->setDeleteStrategy('restrict');
    }

    
    public function beforeAction(Event $event, ArrayObject $extra)
    {
       // echo "<pre>"; print_r($extra['indexButtons']['remove']); die();
        unset($extra['indexButtons']['remove']);
        $this->field('security_group_id', [
            'visible' => false]);
        $this->field('security_user_id', [
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]]);      
        $this->field('security_role_id', ['source_model' => 'Security.SecurityRoles']);

        $this->setFieldOrder([
            'security_user_id','security_role_id'
        ]);

        $toolbarButtons = $extra['toolbarButtons'];
        $extra['toolbarButtons']['back'] = [
            'url' => [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => 'SystemGroups',
                '0' => 'index',
            ],
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Back')
            ]
        ];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', [
            'visible' => true]);
        $this->setFieldOrder(['openemis_no','security_user_id', 'security_role_id']);
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userGroupId = $this->request->query['userGroupId']; 
        $query->contain(['Users','SecurityRoles'])
        ->where([$this->aliasField('security_group_id')=>$userGroupId])
        ->order([$this->aliasField('created DESC')]);

    }


    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $userGroupId = $this->request->query['userGroupId'];    
        $entity->security_group_id = $userGroupId;
    }
    
}
