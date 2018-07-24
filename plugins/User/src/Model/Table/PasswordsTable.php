<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Auth\DefaultPasswordHasher;

class PasswordsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $this->addBehavior('User.Password', [
            'field' => 'password'
        ]);
    }
}
