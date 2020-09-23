<?php

namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Log\Log;

class SecurityGroupClassesTable extends AppTable {

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
        $this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index']
        ]);
    }
}
