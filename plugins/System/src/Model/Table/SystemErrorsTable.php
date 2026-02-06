<?php
namespace System\Model\Table;

use ArrayObject;
use Exception;

use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\Utility\Text;
use Cake\Log\Log;
use Throwable;

use App\Model\Table\AppTable;

class SystemErrorsTable extends AppTable
{
    public function initialize(array $config): void
    {
        if (file_exists(CONFIG . 'app_local.php')) { //POCOR-9203
            parent::initialize($config);
            // Remove existing CreatedUser association if it exists (from parent) and add with Security.Users
            if ($this->associations()->has('CreatedUser')) {
                $this->associations()->remove('CreatedUser');
            }
            $this->belongsTo('CreatedUser', ['className' => 'Security.Users', 'foreignKey' => 'created_user_id']);
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.onGetAllowedActions'] = 'onGetAllowedActions';
        return $events;
    }

    public function onGetAllowedActions(EventInterface $event)
    {
        return ['index'];
    }

    public function insertError(Throwable $ex)
    {
        if (file_exists(CONFIG . 'app_local.php')) { //POCOR-9203
            $msg = $ex->getMessage();
            $trace = $ex->getTraceAsString();
            $file = $ex->getFile();
            $line = $ex->getLine();
            $code = $ex->getCode();
            $serverInfo = json_encode($_SERVER);

            // Handle CLI requests where $_SERVER variables might not be set
            $clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI';
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            if($code == 0) { //POCOR-8676
                $code = 404;
            }
            
            // Handle CLI requests - request_method and request_url might not be set
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (php_sapi_name() === 'cli' ? 'CLI' : '');
            
            $entity = $this->newEntity([
                'id' => Text::uuid(),
                'code' => $code,
                'error_message' => $msg,
                'request_method' => $requestMethod,
                'request_url' => $requestUrl,
                'referrer_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
                'client_ip' => $clientIp,
                'client_browser' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'triggered_from' => $file . ' (Line: ' . $line . ')',
                'stack_trace' => $trace,
                'server_info' => $serverInfo
            ]);

            $this->save($entity);
        }
    }

    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary)
    {
        $query->order(['created' => 'desc']);
    }
}
