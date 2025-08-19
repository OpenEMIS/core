<?php

declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\TableRegistry;
use Cake\Console\ConsoleOptionParser;

// POCOR-8633
class UsersMergeCommand extends Command
{
    private $systemProcessId = '';
    private $baseId = '';
    private $mergeId = '';
    public function setIo(ConsoleIo $io): void
    {
        $this->io = $io;
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->addOption('system_process_id', ['short' => 's', 'help' => 'System process ID', 'required' => true])
            ->addOption('base_id',           ['short' => 'b', 'help' => 'Base user ID',      'required' => true])
            ->addOption('merge_id',          ['short' => 'm', 'help' => 'User ID to merge',  'required' => true]);
    }
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->systemProcessId = (int)$args->getOption('system_process_id');
        $this->baseId          = (int)$args->getOption('base_id');
        $this->mergeId         = (int)$args->getOption('merge_id');

        if (!$this->systemProcessId || !$this->baseId || !$this->mergeId) {
            $io->error('Missing required options: --system_process_id, --base_id, --merge_id');
            return self::CODE_ERROR;
        }
        // Args: <system_process_id> <base_id> <merge_id>
        $systemProcessId = $this->systemProcessId;
        $baseId = $this->baseId;
        $mergeId = $this->mergeId;
        $io->out("system_process_id=[$systemProcessId] base_id=[$baseId] merge_id=[$mergeId]" . FrozenTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'));
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');

        // Mark RUNNING + PID
        if (method_exists($SystemProcesses, 'updatePid')) {
            $SystemProcesses->updatePid((int)$systemProcessId, $pid);
        }
        if ((int)$systemProcessId > 0){
            if (method_exists($SystemProcesses, 'updateProcess')) {
                $SystemProcesses->updateProcess((int)$systemProcessId, FrozenTime::now(), $SystemProcesses::ERROR);
            }
        }

        if ($systemProcessId === '' || $baseId === '' || $mergeId === '') {
            $io->err('Something is missing. Usage: bin/cake users_merge system_process_id=<system_process_id> base_id=<base_id> merge_id=<merge_id>');
            return self::CODE_ERROR;
        }

        /** @var \Cake\ORM\Table $SystemProcesses */

        $conn = ConnectionManager::get('default');

        try {
            $Users = TableRegistry::getTableLocator()->get('User.Users');

            // 1) Fetch base & merge entities
            $base = $Users->get($baseId);
            $merge = $Users->get($mergeId);

            // 2) Compute field-level merge & apply to base
            foreach ($this->compareEntities($base, $merge) as $mf) {
                if (!empty($mf['to_change'])) {
                    $base->set($mf['field'], $mf['result_value']);
                }
            }
            $Users->saveOrFail($base, ['checkRules' => false, 'atomic' => true]);

            // 3) Cross-table re-point in one transaction
            $conn->transactional(function ($conn) use ($baseId, $mergeId, $SystemProcesses, $systemProcessId, $io) {
                // turn off FK checks for this session
                $conn->execute('SET FOREIGN_KEY_CHECKS = 0');

                $related = $this->getRelatedRecords($conn);

                $done = 0;
                foreach ($related as $r) {
                    $table = $r['table_name'];
                    $col = $r['column_name'];

                    // Disable keys if supported (MyISAM/Aria; noop on InnoDB)
                    try {
                        $conn->execute("ALTER TABLE `{$table}` DISABLE KEYS");
                    } catch (\Throwable $e) {
                    }

                    // Re-point references
                    $conn->execute(
                        "UPDATE `{$table}` SET `{$col}` = :base WHERE `{$col}` = :merge",
                        ['base' => $baseId, 'merge' => $mergeId]
                    );

                    // Cleanup any remaining rows with merge id (safety)
                    $conn->execute(
                        "DELETE FROM `{$table}` WHERE `{$col}` = :merge",
                        ['merge' => $mergeId]
                    );

                    // Re-enable keys
                    try {
                        $conn->execute("ALTER TABLE `{$table}` ENABLE KEYS");
                    } catch (\Throwable $e) {
                    }

                    $done++;
                    if (method_exists($SystemProcesses, 'updateProcess')) {
                        $SystemProcesses->updateProcess((int)$systemProcessId, null, $SystemProcesses::RUNNING, $done);
                    }
                }

                // Deactivate merged user
                $conn->execute(
                    "UPDATE `security_users` SET `status` = 0 WHERE `id` = :merge",
                    ['merge' => $mergeId]
                );

                // turn FK checks back on
                $conn->execute('SET FOREIGN_KEY_CHECKS = 1');
            });

            if (method_exists($SystemProcesses, 'updateProcess')) {
                $SystemProcesses->updateProcess((int)$systemProcessId, FrozenTime::now(), $SystemProcesses::COMPLETED);
            }
            $io->out("[$pid] UsersMergeCommand completed");
            return self::CODE_SUCCESS;

        } catch (\Throwable $e) {
            if (method_exists($SystemProcesses, 'updateProcess')) {
                $SystemProcesses->updateProcess((int)$systemProcessId, FrozenTime::now(), $SystemProcesses::ERROR);
            }
            Log::error('[UsersMergeCommand] ' . $e->getMessage());
            $io->err($e->getMessage());
            return self::CODE_ERROR;
        }
    }

    /**
     * Copy of your INFORMATION_SCHEMA scan (schema-agnostic)
     * Returns [['table_name'=>..., 'column_name'=>...], ...]
     */
    private function getRelatedRecords($conn): array
    {
        $db = $conn->config()['database'];
        $rows = $conn->execute(
            "SELECT COLUMN_NAME, TABLE_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE COLUMN_NAME IN ('security_user_id','student_id','user_id','core_user_id',
                                   'staff_id','secondary_staff_id','assignee_id','guardian_id')
               AND COLUMN_NAME NOT IN ('modified_user_id','created_user_id')
               AND TABLE_NAME NOT LIKE 'z%'
               AND TABLE_SCHEMA = :db",
            ['db' => $db]
        )->fetchAll('assoc');

        $out = [];
        foreach ($rows as $row) {
            $out[] = ['table_name' => $row['TABLE_NAME'], 'column_name' => $row['COLUMN_NAME']];
        }
        return $out;
    }

    /**
     * Rule: if base field is empty -> take merge value.
     */
    private function compareEntities($base, $merge, array $exclude = []): array
    {
        if (!$exclude) {
            $exclude = [
                'id', 'password', 'status', 'created_user_id', 'created',
                'modified_user_id', 'modified', 'name', 'name_with_id',
                'name_with_id_role', 'default_identity_type', 'has_special_needs'
            ];
        }

        $result = [];
        $fields = array_keys(array_merge($base->toArray(), $merge->toArray()));
        foreach ($fields as $field) {
            if (in_array($field, $exclude, true)) {
                continue;
            }
            $b = $base->get($field);
            $m = $merge->get($field);

            $bNorm = is_string($b) ? trim($b) : $b;
            $mNorm = is_string($m) ? trim($m) : $m;

            $res = $bNorm;
            $toChange = false;
            if ($bNorm === null || $bNorm === '' || ($bNorm === 0 && $mNorm)) {
                $res = $mNorm;
                $toChange = ($mNorm !== null && $mNorm !== '');
            }

            $result[] = [
                'field' => $field,
                'base_value' => $bNorm,
                'merge_value' => $mNorm,
                'result_value' => $res,
                'to_change' => $toChange,
            ];
        }
        return $result;
    }
}
