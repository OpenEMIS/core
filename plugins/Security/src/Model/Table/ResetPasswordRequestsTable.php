<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class ResetPasswordRequestsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('reset_password_requests');
        parent::initialize($config);
    }
}
