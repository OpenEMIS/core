<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
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

    public function initialize(array $config): void
    {
        $this->setTable('security_group_users');
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

        $this->toggle('search', true);
        $this->toggle('add', false);
        $this->toggle('view', false);
        $this->toggle('edit', false);

        $this->setDeleteStrategy('restrict');
    }


    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
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

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('openemis_no', [
            'visible' => true]);
        $this->setFieldOrder(['openemis_no','security_user_id', 'security_role_id']);
    }
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userGroupId = $this->request->getQuery('userGroupId');
        $query->contain(['Users','SecurityRoles'])
        ->where([$this->aliasField('security_group_id')=>$userGroupId])
        ->order([$this->aliasField('created DESC')]);

        //POCOR-7175 start
        $queryParams = $this->request->getQuery();
        $search = $this->getSearchKey();

        // CUSTOM SEACH -
        $extra['auto_search'] = false; // it will append an AND
        if (!empty($search)) {
            $query->find('byUserNameRole', ['search' => $search]);
        }
        //POCOR-7175 end

    }


    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $userGroupId = $this->request->query['userGroupId'];
        $entity->security_group_id = $userGroupId;
    }

    //POCOR-7175
    public function findByUserNameRole(Query $query, array $options)
    {
        if (!empty($options['search'])) {
            $search = $options['search'];

            $query
                ->contain(['Users', 'SecurityRoles']) // defined associations POCOR-9242
                ->where([
                    'OR' => [
                        'Users.openemis_no LIKE' => '%' . $search . '%',
                        'Users.first_name LIKE' => '%' . $search . '%',
                        'Users.last_name LIKE' => '%' . $search . '%',
                        'Users.middle_name LIKE' => '%' . $search . '%',
                        'Users.third_name LIKE' => '%' . $search . '%',
                        'SecurityRoles.name LIKE' => '%' . $search . '%'
                    ]
                ]);
        }

        return $query;
    }


}
