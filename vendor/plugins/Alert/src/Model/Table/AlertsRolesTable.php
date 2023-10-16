<?php
namespace Alert\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\AppTable;

class AlertsRolesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AlertRules', ['className' => 'Alert.AlertRules']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);

        $this->addBehavior('CompositeKey');
    }
}
