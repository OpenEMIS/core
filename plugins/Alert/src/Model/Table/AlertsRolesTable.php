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
        $this->belongsTo('AlertTypes', ['className' => 'Alert.AlertTypes']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }
}
