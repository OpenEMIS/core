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
use Cake\Utility\Security;
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

        // checksum hash($subject,$message)
        $checksum = Security::hash($subject . ',' . $message, 'sha256');

        // to update and add new records into the alert_logs
        if ($this->exists(['checksum' => $checksum])) {
            $record = $this->find()
                ->where(['checksum' => $checksum])
                ->first();

            if ($record->status == 0) {
                $this->save($record);
            }
        } else {
            $entity = $this->newEntity([
                'method' => $alertRule->method,
                'destination' => $email,
                'status' => 0,
                'subject' => $subject,
                'message' => $message,
                'checksum' => $checksum
            ]);
            $this->save($entity);
        }
    }

    public function replaceMessage($feature, $message, $vars)
    {
        $format = '${%s}';
        $strArray = explode('${', $message);
        array_shift($strArray); // first element will not contain the placeholder

        $AlertRules = TableRegistry::get('Alert.AlertRules');

        $alertTypeDetails = $AlertRules->getAlertTypeDetailsByFeature($feature);
        $availablePlaceholder = $alertTypeDetails[$feature]['placeholder'];

        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos !== false) {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);

                if (array_key_exists('${' . $placeholder . '}', $availablePlaceholder)) {
                    $value = Hash::get($vars, $placeholder);
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
        $this->field('checksum', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message', ['visible' => false]);
        $this->field('destination', ['visible' => false]);
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
