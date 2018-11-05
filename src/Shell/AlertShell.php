<?php
namespace App\Shell;

use Cake\Console\Shell;

class AlertShell extends Shell
{
    protected $processName;
    protected $featureName;

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Alert.Alerts');
        $this->loadModel('Alert.AlertRules');
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Institution.Institutions');
        $this->loadModel('Security.Users');
        $this->loadModel('Security.SecurityGroupUsers');

        // to get institution
        $this->loadModel('Staff.StaffStatuses');
        $this->loadModel('Institution.Staff');

        $class = basename(str_replace('\\', '/', get_class($this)));

        $this->processName = str_replace('Shell', '', $class);
        $this->featureName = str_replace('Alert', '', $this->processName);
    }

    public function getAlertRules($feature)
    {
        return $this->AlertRules->find()
                ->contain(['SecurityRoles'])
                ->where([
                    'feature' => $feature,
                    'enabled' => 1
                ])
                ->all();
    }

    public function getAlertData($threshold, $model)
    {
        return $model->getModelAlertData($threshold);
    }

    public function getEmailList($securityRoleRecords, $institutionId = null)
    {
        $emailList = [];

        foreach ($securityRoleRecords as $securityRolesObj) {
            $options = [
                'securityRoleId' => $securityRolesObj->id
            ];

            if (!is_null($institutionId)) {
                $options['institutionId'] = $institutionId;
            }

            // all staff within securityRole and institution
            $emailListResult = $this->SecurityGroupUsers
                ->find('emailList', $options)
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

        return $emailList;
    }
}
