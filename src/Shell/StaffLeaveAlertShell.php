<?php
namespace App\Shell;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Mailer\Email;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class StaffLeaveAlertShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Alert.Alerts');
        $this->loadModel('Alert.AlertRules');
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Institution.Institutions');
        $this->loadModel('Security.Users');
        $this->loadModel('Security.SecurityGroupUsers');

        $this->loadModel('Institution.StaffLeave');
    }

    public function main()
    {
        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => 'StaffLeaveAlert']);

        $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        do {
            $rules = $this->AlertRules->find()
                ->contain(['SecurityRoles'])
                ->where([
                    'feature' => 'StaffLeave',
                    'enabled' => 1
                ])
                ->all();

            foreach ($rules as $rule) {
                $threshold = $rule->threshold;
                $thresholdArray = json_decode($threshold, true);

                $data = $this->StaffLeave->getStaffLeaveData($threshold);

                foreach ($data as $key => $vars) {
                    $vars['threshold'] = $thresholdArray;

                    if (!empty($rule['security_roles'])) { //check if the alertRule have security role
                        $emailList = [];
                        foreach ($rule['security_roles'] as $securityRolesObj) {
                            $securityRoleId = $securityRolesObj->id;
                            $institutionId = $vars['institution']['id'];

                            // all staff within securityRole and institution
                            $emailListResult = $this->SecurityGroupUsers
                                ->find('emailList', ['securityRoleId' => $securityRoleId, 'institutionId' => $institutionId])
                                ->toArray()
                            ;

                            // combine all email to the email list
                            if (!empty($emailListResult)) {
                                foreach ($emailListResult as $obj) {
                                    if (!empty($obj->_matchingData['Users']->email)) {
                                        $recipient = $obj->_matchingData['Users']->name . ' <' . $obj->_matchingData['Users']->email . '>';
                                        if (!in_array($recipient, $emailList)) {
                                            $emailList[] = $recipient;
                                        }
                                    }
                                }
                            }
                        }

                        $email = !empty($emailList) ? implode(', ', $emailList) : ' ';

                        // subject and message for alert email
                        $subject = $this->AlertLogs->replaceMessage('Attendance', $rule->subject, $vars);
                        $message = $this->AlertLogs->replaceMessage('Attendance', $rule->message, $vars);

                        // insert record to  the alertLog
                        $this->AlertLogs->insertAlertLog($rule->method, $rule->feature, $email, $subject, $message);
                    }
                }
            }

            $filesArray = $dir->find('StaffLeaveAlert.stop');
        } while (empty($filesArray));

        $this->Alerts->updateAll(['process_id' => NULL, 'modified' => Time::now()], ['process_name' => 'StaffLeaveAlert']);
    }
}
