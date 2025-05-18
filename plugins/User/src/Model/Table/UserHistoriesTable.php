<?php
namespace User\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;
use ArrayObject;

use App\Model\Table\ControllerActionTable;

class UserHistoriesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_activities');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->addBehavior('Activity');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getQueryString('security_user_id');
        $query->where([$this->aliasField('security_user_id') => $userId]);

    }

    public function beforeAction(Event $event) {
        $this->field('security_user_id', ['visible' => false]);
    }
}
