<?php
namespace User\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class UserHistoriesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('user_activities');
        parent::initialize($config);

        $this->belongsTo('Users',        ['className' => 'User.Users', 'foreignKey'=>'security_user_id']);
        $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
    }
}
