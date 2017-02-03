<?php
namespace System\Model\Table;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Network\Request;
use Cake\Network\Http\Client;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class SystemErrorsTable extends ControllerActionTable {
    public function initialize(array $config) {
        parent::initialize($config);

        $this->belongsTo('CreatedUser', ['className' => 'Security.Users', 'foreignKey' => 'created_user_id']);

        $this->toggle('view', false);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
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

    public function insertError(Exception $ex)
    {
        $msg = $ex->getMessage();
        $trace = $ex->getTraceAsString();
        $file = $ex->getFile();
        $line = $ex->getLine();

        $entity = $this->newEntity([
            'id' => Text::uuid(),
            'error_message' => $msg,
            'request_url' => $_SERVER['REQUEST_URI'],
            'referrer_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'client_ip' => $_SERVER['REMOTE_ADDR'],
            'client_browser' => $_SERVER['HTTP_USER_AGENT'],
            'triggered_from' => $file . ' (Line: ' . $line . ')',
            'stack_trace' => $trace
        ]);

        $this->save($entity);
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        $query->order(['created' => 'desc']);
    }
}
