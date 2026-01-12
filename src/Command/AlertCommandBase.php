<?php
namespace App\Command;

use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;
use App\Command\Traits\AlertProcessingTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

abstract class AlertCommandBase extends \Cake\Command\Command
{
    use LocatorAwareTrait;
    use AlertProcessingTrait;
    const ROLE_STUDENT = 8; //POCOR-9100
    const ROLE_GUARDIAN = 9; //POCOR-9100
    protected string $processName = '';
    protected string $featureName = '';

    protected int $userId = 0;
    protected array $contacts = [];
    protected $rule;

    public function initialize(): void
    {
        $this->Alerts = $this->fetchTable('Alert.Alerts');
        $this->AlertRules = $this->fetchTable('Alert.AlertRules');
        $this->AlertLogs = $this->fetchTable('Alert.AlertLogs');
        $this->Users = $this->fetchTable('Security.Users');
        $this->SecurityGroupUsers = $this->fetchTable('Security.SecurityGroupUsers');

        $class = basename(str_replace('\\', '/', static::class));
        $this->processName = str_replace('Command', '', $class);
        $this->featureName = str_replace('Alert', '', $this->processName);
    }

    public function prepareContext(Arguments $args, ConsoleIo $io): bool
    {
        $this->setIo($io);
        $this->userId = (int)$args->getOption('user_id');
        $this->ruleId = (int)$args->getOption('rule_id');
        $this->processId = (int)$args->getOption('process_id');
        $ruleId = $this->ruleId;

        if (!$this->userId || !$ruleId) {
            $io->error("Missing required --user_id or --rule_id.");
            return false;
        }

        try {
            $this->rule = $this->AlertRules->get($ruleId, ['contain' => ['SecurityRoles']]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $io->error("Alert rule with ID {$ruleId} not found.");
            return false;
        }

        if (empty($this->rule->security_roles)) {
            $io->out("No roles assigned to alert rule ID {$ruleId}. Skipping.");
            return false;
        }

        return true;
    }
    /**
     * Main template method to execute feature-based alerts.
     *
     * @param string $featureKey Feature identifier (e.g., 'SystemUpdates')
     * @return int Exit code
     */
    protected function runFeatureAlert(string $featureKey): int
    {
        try {

            $pendingItems = $this->getPendingItems($featureKey); // abstract
            if(empty($pendingItems)){
                $this->logMsg("✅ Alert {$featureKey} has no pending items");
            }
//            $sizeofPendingItems = sizeof($pendingItems);
//            if ($sizeofPendingItems > 1) {
//                $pendingItems = [$pendingItems[0]];
//            }
            foreach ($pendingItems as $item) {
                if (
                    $featureKey != 'StudentAttendance' &&  // POCOR-9391
                    (property_exists($this, 'studentId') && $this->studentId)) {
                    $this->contacts = $this->getStudentAssociatedContactList($this->rule->security_roles, $this->studentId);
                } else {
                    $institutionId = (int) $item['institution_id'] ?? null;

                    if ($institutionId) {
                        $contacts = $this->getRoleAssociatedContactList($this->rule->security_roles, $institutionId);
                        $this->contacts = $contacts;
                    } else {
                        $contacts = $this->getRoleAssociatedContactList($this->rule->security_roles);
                        $this->contacts = $contacts;
                    }
                }
                if (empty($this->contacts['email']) && empty($this->contacts['phone'])) {
                    continue; // POCOR-9213
                }
               $placeholders = $this->fillPlaceholders($item); // abstract
               $this->processContactList([$this->rule], $placeholders, fn() => $this->contacts);
            }
        } catch (\Throwable $e) {
            $this->failProcess($this->processId, $this->userId, $e);
            $this->logMsg("[Process Error] " . $e->getMessage());
            return static::CODE_ERROR;
        }

        if (!empty($this->processId)) {
            $this->completeProcess($this->processId, $this->userId);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Abstract: Should return list of pending items to be alerted.
     *
     * @param string $featureKey
     * @return array List of data items (e.g., versions)
     */
    abstract protected function getPendingItems(string $featureKey): array;

    /**
     * Abstract: Must return an array of placeholders => values for a single data item.
     *
     * @param mixed $item The item from getPendingItems()
     * @return array<string, string>
     */
    abstract protected function fillPlaceholders($item): array;

    // POCOR-9213
    public function getRoleAssociatedContactList(
        array $securityRoles,
        ?int $institutionId = null
    ): array {
        $contactList      = ['email' => [], 'phone' => []];
        $allSecurityUserIds = [];

        foreach ($securityRoles as $role) {
            if ($institutionId === null) {
                $query = $this->SecurityGroupUsers->find()
                    ->select(['security_user_id'])
                    ->distinct(['security_user_id'])
                    ->where(['security_role_id' => $role['id']]);
                $ids = collection($query->all())
                    ->extract('security_user_id')
                    ->toList();
            } else {
                // direct
                $direct = $this->SecurityGroupUsers->find()
                    ->select(['security_user_id'])
                    ->distinct(['security_user_id'])
                    ->innerJoin(
                        ['Institutions' => 'institutions'],
                        [
                            'Institutions.id'                => $institutionId,
                            'Institutions.security_group_id = ' .
                            $this->SecurityGroupUsers->aliasField('security_group_id'),
                        ]
                    )
                    ->where(['security_role_id' => $role['id']]);

                // indirect
                $indirect = $this->SecurityGroupUsers->find()
                    ->select(['security_user_id'])
                    ->distinct(['security_user_id'])
                    ->innerJoin(
                        ['SecurityGroupInstitutions' => 'security_group_institutions'],
                        [
                            'SecurityGroupInstitutions.institution_id'      => $institutionId,
                            'SecurityGroupInstitutions.security_group_id = ' .
                            $this->SecurityGroupUsers->aliasField('security_group_id'),
                        ]
                    )
                    ->where(['security_role_id' => $role['id']]);

                $ids = array_merge(
                    collection($direct->all())->extract('security_user_id')->toList(),
                    collection($indirect->all())->extract('security_user_id')->toList()
                );
            }

            // merge & dedupe as we go
            $allSecurityUserIds = array_unique(
                array_merge($allSecurityUserIds, $ids)
            );
        }

        if (!empty($allSecurityUserIds)) {
            // use the accumulated list, not just the last $ids
            $users = $this->Users
                ->find('recipientList', ['recipients' => $allSecurityUserIds])
                ->toArray();

            $contactList = $this->getContactsFromUsers($users, $contactList);
        }

        return $contactList;
    }


    public function getStudentAssociatedContactList(array $securityRoles, $studentUserId): array
    {
        $contactList = ['email' => [], 'phone' => []];
        $recipients = [];
        $securityRoleIds = [];
        foreach ($securityRoles as $role) {
            $securityRoleIds[] = $role['id'];
        }
//        $this->logMsg(print_r($securityRoleIds));
        $StudentGuardians = TableRegistry::getTableLocator()->get('GuardianNav.StudentGuardians');
        if (in_array(self::ROLE_GUARDIAN, $securityRoleIds)) {
            $guardians = $StudentGuardians
                ->find('all')
                ->where([$StudentGuardians->aliasField('student_id') => $studentUserId])
                ->disableHydration()
                ->toArray();

            if (!empty($guardians)) {
                foreach ($guardians as $guardian) {
                    $recipients[] = $guardian['guardian_id'];
                }
            } else {
               $this->logMsg('No guardians found for student ID: ' . $studentUserId);
            }
        }
//        $this->logMsg(print_r(['guardians' => $recipients],true));
        if (in_array(self::ROLE_STUDENT, $securityRoleIds)) {
            $recipients[] = $studentUserId;
        }


        $users = $this->Users
            ->find('recipientList', ['recipients' => $recipients])
            ->toArray();

        $contactList = $this->getContactsFromUsers($users, $contactList);


        return $contactList;
    }

    public function markProcessRunning(): void
    {
        if (empty($this->processId)) {
            return;
        }

        $this->SystemProcesses = $this->fetchTable('SystemProcesses');
        $now = FrozenTime::now();

        $this->SystemProcesses->updateAll([
            'status' => 2,
            'modified' => $now,
            'modified_user_id' => $this->userId,
            'start_date' => $now
        ], ['id' => $this->processId]);

        Log::debug("[SystemProcesses] Marked process {$this->processId} as running.");
    }

    public function failProcess(int $processId, int $userId, $e = null): void
    {
        $this->SystemProcesses = $this->fetchTable('SystemProcesses');

        $this->SystemProcesses->updateAll([
            'status' => -2,
            'modified' => FrozenTime::now(),
            'modified_user_id' => $userId
        ], ['id' => $processId]);

        $errorMessage = '';
        if ($e instanceof \Throwable) {
            $errorMessage = $e->getMessage();
        }

        Log::error("[SystemProcesses] Process $processId failed: $errorMessage");
    }
    // In AlertCommandBase.php
    public function completeProcess(int $processId, int $userId): void
    {
        $this->SystemProcesses = $this->fetchTable('SystemProcesses');
        $now = FrozenTime::now();

        $this->SystemProcesses->updateAll([
            'status' => 3,
            'end_date' => $now,
            'modified' => $now,
            'modified_user_id' => $userId
        ], ['id' => $processId]);
        $this->Alerts->updateAll(['process_id' =>
            getmypid(), 'modified' => FrozenTime::now()],
            ['name' => $this->featureName]);
        Log::debug("[SystemProcesses] Marked process $processId as completed.");
    }

    /**
     * @param $users
     * @param array $contactList
     * @return array
     */
    private function getContactsFromUsers($users, array $contactList): array
    {
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
        return $contactList;
    }


}
