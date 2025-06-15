<?php
namespace App\Command;

use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;
use App\Command\Traits\AlertProcessingTrait;
use Cake\Datasource\Exception\RecordNotFoundException;

abstract class AlertCommandBase extends Command
{
    use LocatorAwareTrait;
    use AlertProcessingTrait;

    protected string $processName = '';
    protected string $featureName = '';

    protected int $userId = 0;
    protected array $contacts = [];
    protected $rule;

    public function initialize(): void
    {
        $this->loadModel('Alert.Alerts');
        $this->loadModel('Alert.AlertRules');
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Security.Users');
        $this->loadModel('Security.SecurityGroupUsers');

        $class = basename(str_replace('\\', '/', static::class));
        $this->processName = str_replace('Command', '', $class);
        $this->featureName = str_replace('Alert', '', $this->processName);
    }

    public function prepareContext(Arguments $args, ConsoleIo $io): bool
    {
        $this->userId = (int) $args->getOption('user_id');
        $ruleId = (int) $args->getOption('rule_id');

        if (!$this->userId || !$ruleId) {
            $io->error("Missing required --user_id or --rule_id.");
            return false;
        }

        try {
            $this->rule = $this->AlertRules->get($ruleId, ['contain' => ['SecurityRoles']]);
        } catch (RecordNotFoundException $e) {
            $io->error("Alert rule with ID {$ruleId} not found.");
            return false;
        }

        if (empty($this->rule->security_roles)) {
            $io->out("No roles assigned to alert rule ID {$ruleId}. Skipping.");
            return false;
        }

        $this->contacts = $this->getRoleAssociatedContactList($this->rule->security_roles);

        if (empty($this->contacts['email']) && empty($this->contacts['phone'])) {
            $io->out("No contacts found for alert rule ID {$ruleId}. Skipping.");
            return false;
        }

        return true;
    }

    public function getRoleAssociatedContactList(array $securityRoles): array
    {
        $contactList = ['email' => [], 'phone' => []];

        foreach ($securityRoles as $role) {
            $userLinks = $this->SecurityGroupUsers
                ->find()
                ->where(['security_role_id' => $role['id']])
                ->toArray();

            foreach ($userLinks as $link) {
                $users = $this->Users
                    ->find('recipientList', ['recipients' => $link->security_user_id])
                    ->toArray();

                foreach ($users as $user) {
                    if (!empty($user->email)) {
                        $email = $user->name . ' <' . $user->email . '>';
                        if (!in_array($email, $contactList['email'])) {
                            $contactList['email'][] = $email;
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
}
