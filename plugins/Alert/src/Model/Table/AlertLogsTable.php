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

        $AlertTypes = TableRegistry::get('Alert.AlertTypes');
        $Users = TableRegistry::get('Security.Users');
        $AlertModel = TableRegistry::get($alertKey);
        $alias = $AlertModel->alias();

        $studentId = $afterSaveOrDeleteEntity->student_id;
        $institutionId = $afterSaveOrDeleteEntity->institution_id;

        $studentName = $Users->get($studentId)->first_name . ' ' . $Users->get($studentId)->last_name;

        // to get the academicPeriodId
        if (isset($afterSaveOrDeleteEntity->academic_period_id)) {
            $academicPeriodId = $afterSaveOrDeleteEntity->academic_period_id;
        } else {
            // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId, model also have different date
            $academicPeriodId = $this->getAcademicPeriodId($AlertModel, $afterSaveOrDeleteEntity);
        }

        $isAlert = true;

        $code = $AlertTypes->getAlertTypeDetailsByAlias($alias)['code'];
        $placeholder = $AlertTypes->getAlertTypeDetailsByAlias($alias)['placeholder'];

        $AlertTypesData = $AlertTypes->find()
            ->contain(['SecurityRoles'])
            ->where(['code' => $code])
            ->all();

        foreach ($AlertTypesData as $AlertTypesKey => $AlertTypesObj) {
            $thresholdType = $AlertTypes->getAlertTypeDetailsByAlias($alias)['threshold']['type'];
            $threshold = $AlertTypesObj->threshold;
            $valueIndex = $AlertModel->getValueIndex($institutionId, $studentId, $academicPeriodId, $isAlert);

            if ($thresholdType == 'integer') {
                if ($valueIndex >= $threshold) { // to check if fulfilled the condition of the alert
                    if (!empty($AlertTypesObj['security_roles'])) {
                        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

                        foreach ($AlertTypesObj['security_roles'] as $securityRolesKey => $securityRolesObj) {
                            $securityRoleId = $securityRolesObj->id;
                            $emailList = $SecurityGroupUsers->getEmailListByRoles($securityRoleId, $institutionId);

                            if (!empty($emailList)) {
                                foreach ($emailList as $emailListKey => $emailListObj) {
                                    $message = str_replace($placeholder, [$studentName], $AlertTypesObj->message);
                                    $this->updateAlertLog($AlertTypesObj, $emailListObj, $message);
                                }

                                // trigger shell
                                $this->triggerSendingAlertShell('SendingAlert');
                            } else {
                                // user no email.
                                $this->updateAlertLog($AlertTypesObj, 'No Email', 'No Email');
                            }
                        }
                    } else {
                        // no security role means no email.
                        $this->updateAlertLog($AlertTypesObj, 'No Security Role', 'No Security Role');
                    }
                }
            }
        }
    }

    public function updateAlertLog($AlertTypesObj, $emailListObj, $message=null)
    {
        $today = Time::now();
        $todayDate = Date::now();

        $alertLogsResults = $this->find()
            ->where([
                $this->aliasField('method') => $AlertTypesObj->method,
                $this->aliasField('destination') => $emailListObj,
                $this->aliasField('subject') => $AlertTypesObj->subject,
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
                'method' => $AlertTypesObj->method,
                'destination' => $emailListObj,
                'status' => 0,
                'subject' => $AlertTypesObj->subject,
                'message' => $message
            ]);
            $this->save($entity);
        }

        // $this->save($entity);
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
