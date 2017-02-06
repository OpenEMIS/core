<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Log\Log;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class AlertLogsTable extends ControllerActionTable
{
    private $statusTypes = [
        0 => 'Pending',
        1 => 'Success',
        -1 => 'Failed'
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function insertAlertLog($alertRule, $email, $subject=null, $message=null)
    {
        $today = Time::now();
        $todayDate = Date::now();

        $alertLogsResults = $this->find()
            ->where([
                $this->aliasField('method') => $alertRule->method,
                $this->aliasField('destination') => $email,
                $this->aliasField('subject') => $subject,
                $this->aliasField('message') => $message
            ])
            ->all();

        // to update and add new records into the alert_logs
        if (!$alertLogsResults->isEmpty()) {
            if ($alertLogsResults->first()->status == 0) {
                $entity = $alertLogsResults->first();
                $this->save($entity);
            }
        } else {
            $entity = $this->newEntity([
                'method' => $alertRule->method,
                'destination' => $email,
                'status' => 0,
                'subject' => $subject,
                'message' => $message
            ]);
            $this->save($entity);
        }
    }

    public function replaceMessage($message, $vars)
    {
        $format = '${%s}';

        $strArray = explode('${', $message);
        array_shift($strArray); // first element will not contain the placeholder

        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos !== false) {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);
                $value = Hash::get($vars, $placeholder);

                if (!is_null($value)) {
                    $message = str_replace($replace, $value, $message);
                }
            }
        }

        return $message;
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        return $this->statusTypes[$entity->status];
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('status', ['after' => 'message']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message', ['type' => 'hidden']);
    }

    public function triggerSendingAlertShell($shellName)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
