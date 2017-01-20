<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
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

    public function shellDataProcess($shellData, $alias)
    {
        $AlertRules = TableRegistry::get('Alert.AlertRules');
        $Users = TableRegistry::get('Security.Users');
        $Institutions = TableRegistry::get('Institution.Institutions');

        $feature = $AlertRules->getAlertTypeDetailsByAlias($alias)['feature'];
        $placeholder = $AlertRules->getAlertTypeDetailsByAlias($alias)['placeholder'];

        $AlertRulesData = $AlertRules->find()
            ->contain(['SecurityRoles'])
            ->where([
                'feature' => $feature,
                'enabled' => 1
            ])
            ->all();

        foreach ($AlertRulesData as $AlertRulesKey => $AlertRulesObj) {
            $thresholdType = $AlertRules->getAlertTypeDetailsByAlias($alias)['threshold']['type'];
            $threshold = $AlertRulesObj->threshold;

            if ($thresholdType == 'integer') {
                foreach ($shellData as $institutionId => $shellDataObj) { // institutionId is the key
                    foreach ($shellDataObj as $studentId => $dataObj) { // studentId is the key
                        if ($dataObj >= $threshold) { // to check if fulfilled the condition of the alert
                            if (!empty($AlertRulesObj['security_roles'])) { //check if the alertRule have security role
                                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

                                foreach ($AlertRulesObj['security_roles'] as $securityRolesKey => $securityRolesObj) {
                                    $securityRoleId = $securityRolesObj->id;
                                    $emailList = $SecurityGroupUsers->getEmailListByRoles($securityRoleId, $institutionId);

                                    if (!empty($emailList)) {
                                        foreach ($emailList as $emailListKey => $emailListObj) {
                                            //for placeholder string replacement
                                            $studentName = $Users->get($studentId)->first_name . ' ' . $Users->get($studentId)->last_name;
                                            $staffName = $Users->get($emailListKey)->first_name . ' ' . $Users->get($emailListKey)->last_name;
                                            $institutionName = $Institutions->get($institutionId)->name;

                                            // subject and message for alert email
                                            $subject = $this->replaceMessage($AlertRulesObj->subject, $studentName, $staffName, $institutionName, $threshold);
                                            $message = $this->replaceMessage($AlertRulesObj->message, $studentName, $staffName, $institutionName, $threshold);

                                            $this->insertAlertLog($AlertRulesObj, $emailListObj, $subject, $message);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // trigger shell
        $this->triggerSendingAlertShell('SendingAlert');
    }

    public function insertAlertLog($AlertRulesObj, $emailListObj, $subject=null, $message=null)
    {
        $today = Time::now();
        $todayDate = Date::now();

        $alertLogsResults = $this->find()
            ->where([
                $this->aliasField('method') => $AlertRulesObj->method,
                $this->aliasField('destination') => $emailListObj,
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
                'method' => $AlertRulesObj->method,
                'destination' => $emailListObj,
                'status' => 0,
                'subject' => $subject,
                'message' => $message
            ]);
            $this->save($entity);
        }
    }

    public function replaceMessage($message, $studentName, $staffName, $institutionName, $threshold)
    {
        // Regex to get the string within {} and put it as an array
        preg_match_all('/{([^}]*)}/', $message, $stringArray);

        $stringReplace = [];
        //messageArray[0] are all the place holder in the message
        foreach ($stringArray[0] as $stringArrayKey => $stringArrayObj) {
            switch ($stringArrayObj) {
                case '{student.name}':
                    $stringReplace [] = $studentName;
                    break;

                case '{staff.name}':
                    $stringReplace [] = $staffName;
                    break;

                case '{institution.name}':
                    $stringReplace [] = $institutionName;
                    break;

                case '{threshold.value}':
                    $stringReplace [] = $threshold;
                    break;

                default:
                    $stringReplace [] = $stringArrayObj;
                    break;
            }
        }

        $message = str_replace($stringArray[0], $stringReplace, $message);

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

    public function triggerSendingAlertShell($shellName)
    {
        $args = '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
