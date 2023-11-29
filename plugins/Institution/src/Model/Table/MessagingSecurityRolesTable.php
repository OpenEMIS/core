<?php

namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\Core\Exception\Exception;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\Utility\Security;

/**
 * POCOR-7458 (to develop messaging  functionality)
 * <author>megha.gupta@mail.valuecoders.com</author>
 */
class MessagingSecurityRolesTable extends ControllerActionTable{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Messaging', ['className' => 'Institution.Messaging','foreignKey'=>"message_id"]);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles', 'foreignKey' => "security_role_id"]);
    }
}