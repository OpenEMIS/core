<?php

declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * UsersMergeCommand
 *
 * Merge user with id=merge_id into user with id=base_id, without changing DB unique keys.
 * Strategy:
 *  - Lock both rows (FOR UPDATE)
 *  - Build "move plan": copy from merge→base only if base is empty
 *  - For each field that might hit a UNIQUE index: neutralize merge value first (NULL if possible, else "MERGED-<id>-...")
 *  - Save MERGE first (now non-colliding), then apply plan to BASE and save BASE
 *  - Repoint FK references from merge_id to base_id
 *  - Deactivate merge user
 *
 * NOTE: FOREIGN_KEY_CHECKS and DISABLE/ENABLE KEYS do not bypass UNIQUE constraints.
 * The only reliable way is to change the data so it no longer collides.
 */
class UsersMergeCommand extends Command
{
    /** @var ConsoleIo */
    protected $io;

    private int $systemProcessId = 0;
    private int $baseId = 0;
    private int $mergeId = 0;

    /**
     * Candidate fields that are unique (or commonly enforced as unique) in OpenEMIS deployments.
     * Keep them here so we neutralize these first on the MERGE row before saving.
     *
     * - Your schema shows unique indexes on: username, email, openemis_no.
     * - Your logs also showed a unique on "unique_mobile" (generated/expression column in some envs).
     * - Add/remove fields to match your environment.
     */
    private const CANDIDATE_UNIQUE_FIELDS = [
        'username',
        'email',
        'openemis_no',
        'unique_mobile', // present in some deployments (generated/normalized mobile)
        'mobile_number', // present in some deployments (generated/normalized mobile)
        // 'mobile_number', // include only if you truly have a unique on it
        // 'identity_number',
        // 'external_reference',
    ];

    public function setIo(ConsoleIo $io): void
    {
        $this->io = $io;
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->io = $io;
        // Parse required CLI options
        $this->systemProcessId = (int) $args->getOption('system_process_id');
        $this->baseId          = (int) $args->getOption('base_id');
        $this->mergeId         = (int) $args->getOption('merge_id');

        if (!$this->systemProcessId || !$this->baseId || !$this->mergeId) {
            $io->error('Missing required options: --system_process_id, --base_id, --merge_id');
            return self::CODE_ERROR;
        }

        $pid = getmypid();
        $io->out(sprintf(
            "system_process_id=[%d] base_id=[%d] merge_id=[%d] %s",
            $this->systemProcessId,
            $this->baseId,
            $this->mergeId,
            FrozenTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss')
        ));

        /** @var \Cake\ORM\Table $SystemProcesses */
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');

        // Mark process as RUNNING with PID
        if (method_exists($SystemProcesses, 'updatePid')) {
            $SystemProcesses->updatePid($this->systemProcessId, $pid);
        }
        if (method_exists($SystemProcesses, 'updateProcess')) {
            $SystemProcesses->updateProcess($this->systemProcessId, FrozenTime::now(), $SystemProcesses::RUNNING);
        }

        $conn = ConnectionManager::get('default');
        try {
            // One transaction for the whole merge (locks + saves + FK repoints + deactivate)
            $conn->transactional(function ($conn) use ($SystemProcesses) {

                $Users = TableRegistry::getTableLocator()->get('User.Users');

                // 1) Lock both rows to prevent concurrent edits/merges
                /** @var Entity $base */
                $base = $Users->find()->where(['id' => $this->baseId])
                    ->applyOptions(['forUpdate' => true])->firstOrFail();

                /** @var Entity $merge */
                $merge = $Users->find()->where(['id' => $this->mergeId])
                    ->applyOptions(['forUpdate' => true])->firstOrFail();

                // 2) Compute move plan according to your rule: "if base is empty → take merge"
                $plan = $this->buildMovePlan($Users, $base, $merge);
                // 3) Neutralize MERGE row for any fields that are unique and we plan to move
                //    This avoids UNIQUE violations when we later assign those values to BASE.
                $this->neutralizeMergeForUniqueFields($Users, $merge, $plan, $this->mergeId, $base);
                // 4) Save MERGE FIRST (now neutralized → cannot collide with anyone)
                $Users->saveOrFail($merge, ['checkRules' => false, 'atomic' => false]);
                // 5) Optional preflight: if moving a unique value into BASE collides with a third row, decide policy
                //    Here we *fail fast* with a clear message, but you can also "skip move" instead.
                $this->preflightThirdPartyCollisionsOrFail($Users, $base->id, $merge->id, $plan);

                // 6) Apply the move plan to BASE and save BASE
                foreach ($plan as $field => $valueToAssign) {
                    $base->set($field, $valueToAssign);
                }
                $Users->saveOrFail($base, ['checkRules' => false, 'atomic' => false]);

                // 7) Repoint foreign keys referencing the MERGE user → BASE user
                $this->repointForeignKeys($conn, $this->baseId, $this->mergeId, $SystemProcesses, $this->systemProcessId);

                // 8) Deactivate MERGE user (and optionally scrub PII to avoid future uniqueness surprises)
                $conn->execute(
                    "UPDATE `security_users` SET `status` = 0 WHERE `id` = :id",
                    ['id' => $this->mergeId]
                );

                // Optional: scrub PII/unique-ish fields on merge to avoid future conflicts (uncomment if desired)
                /*
                $this->scrubMergedUser($Users, $this->mergeId, [
                    'email'        => true,
                    'username'     => true,
                    'openemisfupdateQuery_no'  => true,
                    'unique_mobile'=> true,
                    // 'mobile_number'=> true,
                ]);
                */

            });

            if (method_exists($SystemProcesses, 'updateProcess')) {
                $SystemProcesses->updateProcess($this->systemProcessId, FrozenTime::now(), $SystemProcesses::COMPLETED);
            }
            $io->out("[{$this->systemProcessId}] UsersMergeCommand completed");
            return self::CODE_SUCCESS;

        } catch (\Throwable $e) {
            if (method_exists($SystemProcesses, 'updateProcess')) {
                $SystemProcesses->updateProcess($this->systemProcessId, FrozenTime::now(), $SystemProcesses::ERROR);
            }
            Log::error('[UsersMergeCommand] ' . $e->getMessage());
            $io->err($e->getMessage());
            return self::CODE_ERROR;
        }
    }

    /**
     * Build the move plan:
     *  - Only move when base is "empty-ish" (null or ''), keep base otherwise
     *  - Returns [ field => value_from_merge, ... ] for fields that should be copied
     */
    private function buildMovePlan(Table $Users, Entity $base, Entity $merge): array
    {
        $exclude = [
            'id','password','status','created_user_id','created',
            'modified_user_id','modified','name','name_with_id',
            'name_with_id_role','default_identity_type','has_special_needs'
        ];

        $schema = $Users->getSchema();
        $fields = array_unique(array_merge(array_keys($base->toArray()), array_keys($merge->toArray())));
        $plan = [];

        foreach ($fields as $field) {
            if (in_array($field, $exclude, true)) {
                continue;
            }
            $baseV  = $base->get($field);
            $mergeV = $merge->get($field);

            $baseNorm  = is_string($baseV)  ? trim($baseV)  : $baseV;
            $mergeNorm = is_string($mergeV) ? trim($mergeV) : $mergeV;

            // Rule: only move when base is empty-ish and merge has a non-empty value
            if (($baseNorm === null || $baseNorm === '') && ($mergeNorm !== null && $mergeNorm !== '')) {
                $plan[$field] = $mergeNorm;
            }
        }
        return $plan;
    }

    private function neutralizeMergeForUniqueFields(Table $Users, Entity $merge, array $plan, int $mergeId, Entity $base): void
    {
        $schema  = $Users->getSchema();
        $columns = $schema->columns();

        $present = array_intersect(self::CANDIDATE_UNIQUE_FIELDS, array_keys($columns));
        if(empty($present)){
            $present = array_intersect(self::CANDIDATE_UNIQUE_FIELDS, array_values($columns));
        }

        foreach ($present as $field) {
            $mergeVal = $merge->get($field);
            $baseVal  = $base->get($field);
            $normalize = function ($val) {
                if (is_string($val)) {
                    return trim($val);
                }
                return $val;
            };

            $mergeVal = $normalize($mergeVal);
            $baseVal  = $normalize($baseVal);

            $incoming = $plan[$field] ?? null;

            // If it's the generated unique_mobile, blank the SOURCE instead
            if ($field === 'unique_mobile' && $schema->getColumn('mobile_number')) {
                if ($mergeVal !== null && $mergeVal === $baseVal) {
                    $merge->set('mobile_number', null); // force unique_mobile → NULL
                }
                continue;
            }


            // Case 1: merge has the same as base → must neutralize
            if ($mergeVal !== null && $mergeVal === $baseVal) {
                $this->forceNeutralize($merge, $field, $mergeVal, $mergeId, $schema);
                continue;
            }

            // Case 2: merge has the same as the value we plan to move → must neutralize
            if ($incoming !== null && $mergeVal === $incoming) {
                $this->forceNeutralize($merge, $field, $mergeVal, $mergeId, $schema);
            }
        }
    }

    private function forceNeutralize(Entity $merge, string $field, mixed $current, int $mergeId, \Cake\Database\Schema\TableSchema $schema): void
    {

        $colMeta    = $schema->getColumn($field) ?? [];
        $isNullable = (bool)($colMeta['null'] ?? false);
        $maxLen     = (int)($colMeta['length'] ?? 191);

        if ($isNullable) {
            $merge->set($field, null);
            $this->io->out('Force Nulled');
        } else {
            $token = sprintf('MERGED-%d-%s', $mergeId, substr(sha1((string)$current), 0, 6));
            $merge->set($field, mb_substr($token, 0, max(1, $maxLen)));
            $this->io->out("Force Changed to $token");
        }
    }

    /**
     * Optional safety: ensure that no third-party row will collide with BASE after move.
     * If it would, we throw — you can change this policy to "skip that field" instead.
     */
    private function preflightThirdPartyCollisionsOrFail(Table $Users, int $baseId, int $mergeId, array $plan): void
    {
        foreach ($plan as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            // Probe if some *other* row (not base or merge) already has this value
            $exists = $Users->find()
                    ->select(['id'])
                    ->where([$field => $value])
                    ->andWhere(function ($exp) use ($baseId, $mergeId) {
                        return $exp->notIn('id', [$baseId, $mergeId]);
                    })
                    ->enableHydration(false)
                    ->limit(1)
                    ->count() > 0;

            if ($exists) {
                // Strict policy: abort the merge and tell the operator which field/value collided
                throw new \RuntimeException(sprintf(
                    'Merge would violate UNIQUE on %s="%s" (value already used by another row).',
                    $field,
                    is_scalar($value) ? (string)$value : json_encode($value)
                ));

                // Softer policy: just skip moving that field
                // unset($plan[$field]);
            }
        }
    }

    /**
     * Repoint foreign keys that reference the merge user id -> base user id.
     * Scans INFORMATION_SCHEMA for common FK column names you listed.
     */
    private function repointForeignKeys($conn, int $baseId, int $mergeId, Table $SystemProcesses, int $systemProcessId): void
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

        $done = 0;
        $errors = [];

        foreach ($rows as $r) {
            $table = $r['TABLE_NAME'];
            $col   = $r['COLUMN_NAME'];

            try {
                // Single UPDATE is enough; no need to DELETE after — we just moved references.
                $conn->execute(
                    "UPDATE `{$table}` SET `{$col}` = :base WHERE `{$col}` = :merge",
                    ['base' => $baseId, 'merge' => $mergeId]
                );
            } catch (\Throwable $e) {
                $errors[] = "[{$table}.{$col}] {$e->getMessage()}";
            }

            $done++;
            if (method_exists($SystemProcesses, 'updateProcess')) {
                $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, $done);
            }
        }

        if ($errors) {
            throw new \RuntimeException('User merge failed: ' . implode(' | ', $errors));
        }
    }

    /**
     * Optional helper: scrub PII/unique-ish fields on the merged-away user.
     * Call this after deactivating to prevent future collisions if someone reuses the record.
     */
    private function scrubMergedUser(Table $Users, int $mergeId, array $fieldsToScrub): void
    {
        /** @var Entity $row */
        $row = $Users->find()->where(['id' => $mergeId])->applyOptions(['forUpdate' => true])->first();
        if (!$row) { return; }

        $schema = $Users->getSchema();
        foreach ($fieldsToScrub as $field => $enabled) {
            if (!$enabled) { continue; }
            if (!$schema->getColumn($field)) { continue; }

            $meta     = $schema->getColumn($field) ?? [];
            $isNull   = (bool)($meta['null'] ?? false);
            $max      = (int)($meta['length'] ?? 191);

            if ($isNull) {
                $row->set($field, null);
            } else {
                $row->set($field, mb_substr('MERGED-' . $mergeId, 0, max(1, $max)));
            }
        }

        $Users->saveOrFail($row, ['checkRules' => false, 'atomic' => false]);
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->addOption('system_process_id', ['short' => 's', 'help' => 'System process ID', 'required' => true])
            ->addOption('base_id',           ['short' => 'b', 'help' => 'Base user ID',        'required' => true])
            ->addOption('merge_id',          ['short' => 'm', 'help' => 'User ID to merge',     'required' => true]);
    }
}
