<?php
namespace System\Model\Table;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Utility\Text;
use Cake\Log\Log;
use Throwable;

use App\Model\Table\AppTable;

class SystemErrorsTable extends AppTable
{
    public function initialize(array $config): void
    {

        parent::initialize($config);
        $this->belongsTo('CreatedUser', ['className' => 'Security.Users', 'foreignKey' => 'created_user_id']);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.onGetAllowedActions'] = 'onGetAllowedActions';
        return $events;
    }

    public function onGetAllowedActions(Event $event)
    {
        return ['index'];
    }

    public function insertError(Throwable $ex)
    {
        $msg = $ex->getMessage();
        $trace = $ex->getTraceAsString();
        $file = $ex->getFile();
        $line = $ex->getLine();
        $code = $ex->getCode();
        $serverInfo = json_encode($_SERVER);

        $clientIp = $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if($code == 0) { //POCOR-8676
            $code = 404;
        }
        $entity = $this->newEntity([
            'id' => Text::uuid(),
            'code' => $code,
            'error_message' => $msg,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_url' => $_SERVER['REQUEST_URI'],
            'referrer_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'client_ip' => $clientIp,
            'client_browser' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'triggered_from' => $file . ' (Line: ' . $line . ')',
            'stack_trace' => $trace,
            'server_info' => $serverInfo
        ]);

        $this->save($entity);
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        $query->order(['created' => 'desc']);
    }
}
