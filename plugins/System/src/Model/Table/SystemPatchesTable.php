<?php
namespace System\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;

class SystemPatchesTable extends AppTable {
    public function initialize(array $config) {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.onGetAllowedActions'] = 'onGetAllowedActions';
        return $events;
    }

    public function onGetAllowedActions(Event $event)
    {
        return ['index'];
    }
}
