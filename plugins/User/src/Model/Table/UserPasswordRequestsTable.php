<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class UserPasswordRequestsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('user_password_requests');
        parent::initialize($config);
    }
}
