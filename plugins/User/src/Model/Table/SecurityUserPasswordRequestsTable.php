<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class SecurityUserPasswordRequestsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_user_password_requests');
        parent::initialize($config);
    }
}
