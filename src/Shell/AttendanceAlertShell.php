<?php
namespace App\Shell;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Mailer\Email;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class AttendanceAlertShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Alert.Alerts');
        $this->loadModel('Alert.AlertRules');
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Institution.Institutions');
        $this->loadModel('Institution.InstitutionStudentAbsences');
        $this->loadModel('Security.Users');
        $this->loadModel('Security.SecurityGroupUsers');
    }

    public function main()
    {
        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => 'AttendanceAlert']);

        $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        do {
            $rules = $this->AlertRules->find()
                ->contain(['SecurityRoles'])
                ->where([
                    'feature' => 'Attendance',
                    'enabled' => 1
                ])
                ->all();

            foreach ($rules as $rule) {
                $threshold = $rule->threshold;
                $data = $this->InstitutionStudentAbsences->getUnexcusedAbsenceData($threshold);

                foreach ($data as $key => $vars) {
                    if (!empty($rule['security_roles'])) { //check if the alertRule have security role

                        $emailList = [];
                        foreach ($rule['security_roles'] as $securityRolesObj) {
                            $securityRoleId = $securityRolesObj->id;
                            $institutionId = $vars['institution']['id'];
                            // all staff within securityRole and institution
                            $emailListResult = $this->SecurityGroupUsers
                                ->find('emailList', ['securityRoleId' => $securityRoleId, 'institutionId' => $institutionId])
                                ->where(['email' . ' IS NOT NULL'])
                                ->toArray()
                            ;

                            // combine all email to the email list
                            if (!empty($emailListResult)) {
                                foreach ($emailListResult as $obj) {
                                    $emailList[] = $obj->user->email;
                                }
                            }
                        }

                        $email = !empty($emailList) ? implode(', ', $emailList) : ' ';

                        // subject and message for alert email
                        $subject = $this->AlertLogs->replaceMessage($rule->subject, $vars);
                        $message = $this->AlertLogs->replaceMessage($rule->message, $vars);

                        // insert record to  the alertLog
                        $this->AlertLogs->insertAlertLog($rule, $email, $subject, $message);
                    }
                }
            }
            sleep(15); // 15 seconds

            // trigger the send email shell
            $this->AlertLogs->triggerSendingAlertShell('SendingAlert');

            $filesArray = $dir->find('AttendanceAlert.stop');
        } while (empty($filesArray));

        $this->Alerts->updateAll(['process_id' => NULL, 'modified' => Time::now()], ['process_name' => 'AttendanceAlert']);
    }
}
