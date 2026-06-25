<?php
namespace Alert\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class NoticeRolesTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->belongsTo('Notices', [
            'className' => 'Alert.Notices',
        ]);

        $this->belongsTo('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
        ]);

    }

   
}
