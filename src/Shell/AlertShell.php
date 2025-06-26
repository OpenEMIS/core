<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Log\Log;

class AlertShell extends Shell
{
    protected $processName;
    protected $featureName;

    public function initialize(): void
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
        // POCOR-8533 added check for frequency
        $alertRules =$this->AlertRules;
        $alerts = $this->Alerts;
        return $alertRules
            ->find()
                ->contain(['SecurityRoles'])
                ->innerJoin([$alerts->getAlias() => $alerts->getTable()],
                    $alertRules->aliasField('feature = ') . $alerts->aliasField('name'),
                )
                ->where([
                    $alertRules->aliasField('feature') => $feature,
                    $alertRules->aliasField('enabled') => 1,
                    $alerts->aliasField('frequency !=') =>  'Never'
                ])
                ->all();
    }

    public function getSystemUpdateAlertRules($feature)
    {
        // POCOR-8869
        $alertRules =$this->AlertRules;
        $alerts = $this->Alerts;
        return $alertRules
            ->find()
                ->contain(['SecurityRoles'])
                ->innerJoin([$alerts->getAlias() => $alerts->getTable()],
                    $alertRules->aliasField('feature = ') . $alerts->aliasField('name'),
                )
                ->where([
                    $alertRules->aliasField('feature') => $feature,
                    $alertRules->aliasField('enabled') => 1,
                    $alerts->aliasField('frequency =') =>  'Once'
                ])
                ->all();
    }

    public function getAlertData($threshold, $model)
    {
        //POCOR-8533 added try-catch
        try {
            return $model->getModelAlertData($threshold);
        } catch (\Exception $exception) {
            $this->out('Error in the class: ' . __CLASS__);
            $this->out('Error in the model: ' . $model->getName());
            $this->out($exception->getMessage());
            return [];
        }
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

    public function getSystemUpdateEmailList($securityRoleRecords, $institutionId = null)
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

    public function getStudentAdmissionEmailList($securityRoleRecords, $institutionId = null)
    {
        $emailList = [];

        foreach ($securityRoleRecords as $securityRolesObj) {
            $options = [
                'securityRoleId' => $securityRolesObj->id
            ];

            // all staff within securityRole and institution
            $emailListResult = $this->Users
                ->find('studentAdmissionEmailList', $options)
                ->toArray()
            ;

            // combine all email to the email list
            if (!empty($emailListResult)) {
                foreach ($emailListResult as $obj) {
                    if (!empty($obj->email)) {
                        $recipient = $obj->name . ' <' . $obj->email . '>';
                        if (!in_array($recipient, $emailList)) {
                            $emailList[] = $recipient;
                        }
                    }
                }
            }
        }

        return $emailList;
    }

    //POCOR-8341[START]
    public function getRoleAssociatedEmailList($securityRoleRecords)
    {
        $emailList = [];

        foreach ($securityRoleRecords as $securityRolesObj) {
            $securityRolesId = [
                'id' => $securityRolesObj->id
            ];
            
            $securityUserList = $this->SecurityGroupUsers
                ->find('all', $securityRolesId)
                ->toArray();
            
            foreach($securityUserList AS $emailListResultData){
                $securityUserId = [
                    'id' => $emailListResultData->security_user_id
                ];

                $emailListResult = $this->Users
                ->find('emailList', $securityUserId)
                ->toArray();
              
                if (!empty($emailListResult)) {
                    foreach ($emailListResult as $obj) {
                        if (!empty($obj->email)) {
                            $recipient = $obj->name . ' <' . $obj->email . '>';
                            if (!in_array($recipient, $emailList)) {
                                $emailList[] = $recipient;
                            }
                        }
                    }
                }
            }
        }

        return $emailList;
    }
    //POCOR-8341[END]

}
