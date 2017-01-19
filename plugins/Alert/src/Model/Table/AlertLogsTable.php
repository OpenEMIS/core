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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        // student absence and attendance
        $events['Model.InstitutionStudentAbsences.afterSave'] = 'afterSaveOrDelete';
        $events['Model.InstitutionStudentAbsences.afterDelete'] = 'afterSaveOrDelete';
        return $events;
    }

    public function afterSaveOrDelete(Event $mainEvent, Entity $afterSaveOrDeleteEntity)
    {
        $alertKey = $afterSaveOrDeleteEntity->source();

        $AlertRules = TableRegistry::get('Alert.AlertRules');
        $Users = TableRegistry::get('Security.Users');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $AlertModel = TableRegistry::get($alertKey);
        $alias = $AlertModel->alias();

        $studentId = $afterSaveOrDeleteEntity->student_id;
        $institutionId = $afterSaveOrDeleteEntity->institution_id;

        // to get the academicPeriodId
        if (isset($afterSaveOrDeleteEntity->academic_period_id)) {
            $academicPeriodId = $afterSaveOrDeleteEntity->academic_period_id;
        } else {
            // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId, model also have different date
            $academicPeriodId = $this->getAcademicPeriodId($AlertModel, $afterSaveOrDeleteEntity);
        }

        $isAlert = true;

        $feature = $AlertRules->getAlertTypeDetailsByAlias($alias)['feature'];
        $placeholder = $AlertRules->getAlertTypeDetailsByAlias($alias)['placeholder'];

        $AlertRulesData = $AlertRules->find()
            ->contain(['SecurityRoles'])
            ->where(['feature' => $feature])
            ->all();

        foreach ($AlertRulesData as $AlertRulesKey => $AlertRulesObj) {
            $thresholdType = $AlertRules->getAlertTypeDetailsByAlias($alias)['threshold']['type'];
            $threshold = $AlertRulesObj->threshold;
            $valueIndex = $AlertModel->getValueIndex($institutionId, $studentId, $academicPeriodId, $isAlert);

            if ($thresholdType == 'integer') {
                if ($valueIndex >= $threshold) { // to check if fulfilled the condition of the alert
                    if (!empty($AlertRulesObj['security_roles'])) {
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

                                    $this->updateAlertLog($AlertRulesObj, $emailListObj, $subject, $message);
                                }

                                // trigger shell
                                $this->triggerSendingAlertShell('SendingAlert');
                            } else {
                                // user no email.
                                $this->updateAlertLog($AlertRulesObj, 'No Email', 'No Email', 'No Email');
                            }
                        }
                    } else {
                        // no security role means no email.
                        $this->updateAlertLog($AlertRulesObj, 'No Security Role', 'No Security Role', 'No Security Role');
                    }
                }
            }
        }
    }

    public function updateAlertLog($AlertRulesObj, $emailListObj, $subject=null, $message=null)
    {
        $today = Time::now();
        $todayDate = Date::now();

        $alertLogsResults = $this->find()
            ->where([
                $this->aliasField('method') => $AlertRulesObj->method,
                $this->aliasField('destination') => $emailListObj,
                $this->aliasField('subject') => $subject,
                $this->aliasField('message') => $message,
                $this->aliasField('created') . ' >= ' => $todayDate,
                $this->aliasField('created') . ' <= ' => $today,
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

    public function getAcademicPeriodId($AlertModel, $afterSaveOrDeleteEntity)
    {
        // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId, every model also have different date
        switch ($AlertModel->alias()) {
            case 'InstitutionStudentAbsences': // have start date and end date
                $startDate = $afterSaveOrDeleteEntity->start_date;
                $endDate = $afterSaveOrDeleteEntity->end_date;
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodId = $AcademicPeriods->getAcademicPeriodId($startDate, $endDate);
                break;
        }

        return $academicPeriodId;
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
