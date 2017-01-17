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

        $feature = $AlertTypes->getAlertTypeDetailsByAlias($alias)['feature'];
        $placeholder = $AlertTypes->getAlertTypeDetailsByAlias($alias)['placeholder'];

        $AlertTypesData = $AlertTypes->find()
            ->contain(['SecurityRoles'])
            ->where(['feature' => $feature])
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

                                    //for placeholder
                                    $studentName = $Users->get($studentId)->first_name . ' ' . $Users->get($studentId)->last_name;
                                    $staffName = $Users->get($emailListKey)->first_name . ' ' . $Users->get($emailListKey)->last_name;
                                    $institutionName = $Institutions->get($institutionId)->name;

                                    $messageArray = explode(" ", $AlertTypesObj->message);
                                    $subjectArray = explode(" ", $AlertTypesObj->subject);







        //         $stringReplace = [];

        // foreach ($placeholder as $placeholderKey => $placeholderObj) {
        //     if (in_array($placeholderKey, $messageArray) || in_array($placeholderKey, $subjectArray)) {
        //         pr('placeholder '. $placeholderKey);
        //         switch ($placeholderKey) {
        //             case '{student.name}':
        //                 // $message = str_replace($placeholderKey, $studentName, $AlertTypesObj->message);
        //                 // $subject = str_replace($placeholderKey, $studentName, $AlertTypesObj->subject);
        //                 $stringReplace['{student.name}'] = $studentName;
        //                 break;

        //             case '{staff.name}':
        //                 // $message = str_replace($placeholderKey, $staffName, $AlertTypesObj->message);
        //                 // $subject = str_replace($placeholderKey, $staffName, $AlertTypesObj->subject);
        //                 $stringReplace['{staff.name}'] = $staffName;
        //                 break;

        //             case '{institution.name}':
        //                 // $message = str_replace($placeholderKey, $institutionName, $AlertTypesObj->message);
        //                 // $subject = str_replace($placeholderKey, $institutionName, $AlertTypesObj->subject);
        //                 $stringReplace['{institution.name}'] = $institutionName;
        //                 break;

        //             case '{threshold.value}':
        //                 // $message = str_replace($placeholderKey, $threshold, $AlertTypesObj->message);
        //                 // $subject = str_replace($placeholderKey, $threshold, $AlertTypesObj->subject);
        //                 $stringReplace['{threshold.value}'] = $threshold;
        //                 break;
        //         }
        //     }
        // }
        // pr($placeholder);
        // pr($stringReplace);
        // $message = str_replace($placeholder, [$stringReplace], $AlertTypesObj->message);




        // pr('herer to explode the message and find the placeholder');
        // // pr($AlertTypesObj->message);
        // pr($messageArray);
        // pr($placeholder);
        // pr($emailListKey);
        // pr($emailListObj);
        // pr($staffName);
        // pr($institutionName);
        // pr($threshold);
        pr($message);
        // pr($subject);
        die;
                                    // $message = str_replace($placeholder, [$studentName], $AlertTypesObj->message);
                                    $this->updateAlertLog($AlertTypesObj, $emailListObj, $subject, $message);
                                }

                                // trigger shell
                                $this->triggerSendingAlertShell('SendingAlert');
                            } else {
                                // user no email.
                                $this->updateAlertLog($AlertTypesObj, 'No Email', 'No Email', 'No Email');
                            }
                        }
                    } else {
                        // no security role means no email.
                        $this->updateAlertLog($AlertTypesObj, 'No Security Role', 'No Security Role', 'No Security Role');
                    }
                }
            }
        }
    }

    public function updateAlertLog($AlertTypesObj, $emailListObj, $subject=null, $message=null)
    {
        $today = Time::now();
        $todayDate = Date::now();

        $alertLogsResults = $this->find()
            ->where([
                $this->aliasField('method') => $AlertTypesObj->method,
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
