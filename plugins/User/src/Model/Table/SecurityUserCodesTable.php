<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class SecurityUserCodesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_user_codes');
        parent::initialize($config);
    }
}
