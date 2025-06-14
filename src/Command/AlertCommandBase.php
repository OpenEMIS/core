<?php
namespace App\Command;

use Cake\Console\Command;
use Cake\ORM\Locator\LocatorAwareTrait;
use App\Command\Traits\AlertProcessingTrait;

abstract class AlertCommandBase extends Command
{
    use LocatorAwareTrait;
    use AlertProcessingTrait;

    protected string $processName = '';
    protected string $featureName = '';

    public function initialize(): void
    {
        $this->loadModel('Alert.Alerts');
        $this->loadModel('Alert.AlertRules');
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Institution.Institutions');
        $this->loadModel('Security.Users');
        $this->loadModel('Security.SecurityGroupUsers');
        $this->loadModel('Staff.StaffStatuses');
        $this->loadModel('Institution.Staff');

        $class = basename(str_replace('\\', '/', static::class));
        $this->processName = str_replace('Command', '', $class);
        $this->featureName = str_replace('Alert', '', $this->processName);

    }

    public function getAlertRules($feature)
    {
        $alerts = $this->Alerts;
        $alertRules = $this->AlertRules;

        return $alertRules
            ->find()
            ->contain(['SecurityRoles'])
            ->innerJoin(
                [$alerts->getAlias() => $alerts->getTable()],
                $alertRules->aliasField('feature = ') . $alerts->aliasField('name')
            )
            ->where([
                $alertRules->aliasField('feature') => $feature,
                $alertRules->aliasField('enabled') => 1,
                $alerts->aliasField('frequency !=') => 'Never'
            ])
            ->all();
    }

    public function getSystemUpdateAlertRules($feature)
    {
        $alerts = $this->Alerts;
        $alertRules = $this->AlertRules;

        return $alertRules
            ->find()
            ->contain(['SecurityRoles'])
            ->innerJoin(
                [$alerts->getAlias() => $alerts->getTable()],
                $alertRules->aliasField('feature = ') . $alerts->aliasField('name')
            )
            ->where([
                $alertRules->aliasField('feature') => $feature,
                $alertRules->aliasField('enabled') => 1,
                $alerts->aliasField('frequency =') => 'Once'
            ])
            ->all();
    }

    public function getAlertData($threshold, $model)
    {
        try {
            return $model->getModelAlertData($threshold);
        } catch (\Exception $exception) {
            $this->out('Error in the class: ' . __CLASS__);
            $this->out('Error in the model: ' . $model->getName());
            $this->out($exception->getMessage());
            return [];
        }
    }

    public function getAlertContactList($securityRoleRecords, $institutionId = null): array
    {
        $contactList = [
            'email' => [],
            'phone' => []
        ];

        foreach ($securityRoleRecords as $securityRole) {
            $options = ['securityRoleId' => $securityRole->id];

            if ($institutionId !== null) {
                $options['institutionId'] = $institutionId;
            }

            $result = $this->SecurityGroupUsers
                ->find('emailList', $options)
                ->toArray();

            foreach ($result as $obj) {
                $user = $obj->_matchingData['Users'] ?? null;

                if (!$user) continue;

                if (!empty($user->email)) {
                    $emailRecipient = $user->name . ' <' . $user->email . '>';
                    if (!in_array($emailRecipient, $contactList['email'])) {
                        $contactList['email'][] = $emailRecipient;
                    }
                }

                if (!empty($user->mobile_number) && !in_array($user->mobile_number, $contactList['phone'])) {
                    $contactList['phone'][] = $user->mobile_number;
                }
            }
        }

        return $contactList;
    }

    public function getRoleAssociatedContactList($securityRoleRecords): array
    {
        $contactList = [
            'email' => [],
            'phone' => []
        ];
        foreach ($securityRoleRecords as $securityRole) {
            $userLinks = $this->SecurityGroupUsers
                ->find('all', ['conditions' => ['security_role_id' => $securityRole->id]])
                ->toArray();

            foreach ($userLinks as $link) {
                $users = $this->Users
                    ->find('recipientList', ['recipients' => $link->security_user_id])
                    ->toArray();

                foreach ($users as $user) {
                    if (!empty($user->email)) {
                        $emailRecipient = $user->name . ' <' . $user->email . '>';
                        if (!in_array($emailRecipient, $contactList['email'])) {
                            $contactList['email'][] = $emailRecipient;
                        }
                    }

                    if (!empty($user->mobile_number) && !in_array($user->mobile_number, $contactList['phone'])) {
                        $contactList['phone'][] = $user->mobile_number;
                    }
                }
            }
        }
        return $contactList;
    }

    protected function insertAlertLogs(mixed $rule, $institutionId, $feature, mixed $vars): void
    {
        $contactList = $this->getAlertContactList($rule['security_roles'], $institutionId);
        $methods = array_map('trim', explode(',', $rule->method));

        $subject = $this->AlertLogs->replaceMessage($feature, $rule->subject, $vars);
        $message = $this->AlertLogs->replaceMessage($feature, $rule->message, $vars);

        foreach ($methods as $method) {
            if ($method === 'Email' && !empty($contactList['email'])) {
                $email = implode(', ', $contactList['email']);
                $this->AlertLogs->insertAlertLog($method, $rule->feature, $email, $subject, $message);
            }

            if ($method === 'SMS' && !empty($contactList['phone'])) {
                $phone = implode(', ', $contactList['phone']);
                $this->AlertLogs->insertAlertLog($method, $rule->feature, $phone, $subject, $message);
            }
        }
    }
    abstract public function logAlert($method, $feature, $recipient, $subject, $message);
}
