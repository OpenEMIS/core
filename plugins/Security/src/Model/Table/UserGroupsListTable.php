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

class UserGroupsListTable extends ControllerActionTable
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

        // $this->belongsToMany('Roles', [
        //     'className' => 'Security.SecurityRoles',
        //     'joinTable' => 'security_group_users',
        //     'foreignKey' => 'security_group_id',
        //     'targetForeignKey' => 'security_role_id',
        //     'through' => 'Security.SecurityGroupUsers',
        //     'dependent' => true
        // ]);

        // $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {

        $this->field('security_group_id', [
            'visible' => false]);
        $this->field('security_user_id', [
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]]);      
        $this->field('security_role_id', ['source_model' => 'Security.SecurityRoles']);

        $this->setFieldOrder([
            'security_user_id','security_role_id'
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['security_user_id', 'security_role_id']);
    }

    
}
