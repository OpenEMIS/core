<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\ORM\Entity;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\EntityInterface;

/**
 * Base class for all academic structure/data copy CLI commands.
 */
abstract class CopyCommandBase extends Command
{
    /** @var int */
    protected int $fromId;

    /** @var int */
    protected int $toId;

    /** @var int */
    protected int $userId;

    /** @var bool */
    protected bool $dryRun = false;

    /** @var bool */
    protected bool $quiet = false;

    /** @var int|null */
    protected ?int $processId = null;

    /** @var ConsoleIo|null */
    protected ?ConsoleIo $io = null;

    /** @var ConnectionInterface|null */
    protected ?ConnectionInterface $conn = null;

    // NEW
    protected ?EntityInterface $fromAcademicPeriod = null;
    protected ?EntityInterface $toAcademicPeriod = null;

    /**
     * Each subclass must call this in their `buildOptionParser`.
     */
    protected function addStandardOptions(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->addArgument('from', [
                'help' => 'Source academic_period_id',
                'required' => true
            ])
            ->addArgument('to', [
                'help' => 'Target academic_period_id',
                'required' => true
            ])
            ->addArgument('user', [
                'help' => 'User ID (used for created_user_id and system process logging)',
                'required' => true
            ])
            ->addOption('dry-run', [
                'boolean' => true,
                'default' => false,
                'help' => 'Simulate the operation without saving anything'
            ])
            ->addOption('quiet', [
                'boolean' => true,
                'default' => false,
                'help' => 'Suppress most console output'
            ]);
    }

    /**
     * Call this in every `execute()` method to hydrate input.
     */

    protected function initializeFromInput(Arguments $args, ConsoleIo $io): void
    {
        $this->setConsoleIo($io);

        $this->fromId = (int)$args->getArgument('from');
        $this->toId   = (int)$args->getArgument('to');
        $this->userId = (int)$args->getArgument('user');

        $this->dryRun = (bool)$args->getOption('dry-run');
        $this->quiet  = (bool)$args->getOption('quiet');

        $this->conn = ConnectionManager::get('default');
        $this->conn->getDriver()->enableAutoQuoting(true);

        $apTable = $this->getDynamicTableInstance('academic_periods');

        try {
            $this->fromAcademicPeriod = $apTable->get($this->fromId);
        } catch (RecordNotFoundException $e) {
            $io->err("Source academic period ID {$this->fromId} not found.");
            exit(static::CODE_ERROR);
        }

        try {
            $this->toAcademicPeriod = $apTable->get($this->toId);
        } catch (RecordNotFoundException $e) {
            $io->err("Target academic period ID {$this->toId} not found.");
            exit(static::CODE_ERROR);
        }
    }

    /**
     * Dynamically load tables (plugin-safe).
     */
    protected function getDynamicTableInstance(string $tableName): Table
    {
        $locator = TableRegistry::getTableLocator();

        if ($locator->exists($tableName)) {
            return $locator->get($tableName);
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];
        $alias = Inflector::camelize($table);

        $className = $plugin
            ? "{$plugin}\\Model\\Table\\{$alias}Table"
            : "App\\Model\\Table\\{$alias}Table";

        if (!class_exists($className)) {
            $className = Table::class;
        }

        $locator->setConfig($alias, [
            'className' => $className,
            'table'     => $table,
            'alias'     => $alias,
        ]);

        return $locator->get($alias);
    }

    protected function saveOrThrow(Table $table, $entity, string $label): void
    {
        if ($this->dryRun) {
            $this->logMsg("[dry-run] Would save entity for: {$label}");
            return;
        }

        if (!$table->save($entity)) {
            $errors = json_encode($entity->getErrors(), JSON_UNESCAPED_UNICODE);
            throw new \RuntimeException("Failed to save {$label}: {$errors}");
        }
    }

    protected function startProcess(string $feature, array $args = []): void
    {
        $this->fetchTable('SystemProcesses');
        $this->processId = $this->SystemProcesses->addProcess(
            $feature,
            getmypid(),
            $feature,
            $args
        );
        $this->SystemProcesses->updateProcess($this->processId, null, 2, 0); // RUNNING
        $this->logMsg("Started process #{$this->processId} [{$feature}]");
    }

    protected function completeProcess(): void
    {
        if (!$this->processId) return;

        $this->fetchTable('SystemProcesses');
        $now = FrozenTime::now();

        $this->SystemProcesses->updateAll([
            'status' => 3,
            'end_date' => $now,
            'modified' => $now,
            'modified_user_id' => $this->userId
        ], ['id' => $this->processId]);

        $this->logMsg("Completed process #{$this->processId}");
    }

    protected function failProcess(?\Throwable $e = null): void
    {
        if (!$this->processId) return;

        $this->fetchTable('SystemProcesses');

        $this->SystemProcesses->updateAll([
            'status' => -2,
            'modified' => FrozenTime::now(),
            'modified_user_id' => $this->userId
        ], ['id' => $this->processId]);

        $msg = $e ? $e->getMessage() : 'Unknown error';
        $this->logMsg("Process failed: {$msg}");
    }

    protected function setConsoleIo(ConsoleIo $io): void
    {
        $this->io = $io;
    }

    protected function logMsg(string $msg): void
    {
        if (!$this->quiet && $this->io) {
            $this->io->out($msg);
        }
    }

    protected function getConnection(): \Cake\Database\Connection
    {
        if (!$this->conn) {
            $this->conn = ConnectionManager::get('default');
            $this->conn->getDriver()->enableAutoQuoting(true);
        }
        return $this->conn;
    }
    //POCOR-9767 starts
    /** @var string[]|null Cached academic period names (longest first) */
    protected ?array $academicPeriodNames = null;

    /**
     * Rewrite education structure names for the target academic period.
     *
     * Works with any academic period name format, e.g.:
     *   "2025", "2025-2026", "2025-new-academic-period", "2026-new-academic-one"
     *
     * Rules:
     * 1. Already ends with TO period name → unchanged
     * 2. Name equals FROM period name → TO period name
     * 3. Ends with " " + FROM period name → replace trailing FROM with TO
     * 4. FROM period appears as a standalone segment → replace that segment with TO
     *    (avoids partial matches, e.g. FROM "2025" must not alter "... 2025-2026")
     * 5. Name ends with ANY known academic period name (e.g. an older period like
     *    "2024-2025" baked in from a previous copy) → strip it and append TO period
     * 6. Otherwise → append " " + TO period name
     */
    protected function renameWithAcademicPeriod(string $name, string $fromApName, string $toApName): string
    {
        $name = trim($name);
        $fromApName = trim($fromApName);
        $toApName = trim($toApName);

        if ($name === '' || $toApName === '' || $fromApName === $toApName) {
            return $name;
        }

        if ($this->endsWithAcademicPeriodSuffix($name, $toApName)) {
            return $name;
        }

        if ($fromApName !== '') {
            if ($name === $fromApName) {
                return $toApName;
            }

            if ($this->endsWithAcademicPeriodSuffix($name, $fromApName)) {
                return mb_substr($name, 0, mb_strlen($name) - mb_strlen($fromApName)) . $toApName;
            }

            if ($this->containsStandaloneAcademicPeriod($name, $fromApName)) {
                return $this->replaceStandaloneAcademicPeriod($name, $fromApName, $toApName);
            }
        }

        // Name may carry an older period suffix (e.g. copied earlier from "2024-2025").
        // Strip a trailing known academic period name before appending the TO period.
        $base = $this->stripTrailingAcademicPeriod($name, $toApName);

        return $base . ' ' . $toApName;
    }

    /**
     * If $name ends with " " + any known academic period name, remove that suffix.
     * Uses the longest matching period name so "System 2024-2025" strips fully
     * rather than leaving a fragment. The TO period is skipped (handled elsewhere).
     */
    protected function stripTrailingAcademicPeriod(string $name, string $toApName): string
    {
        foreach ($this->getAcademicPeriodNames() as $periodName) {
            if ($periodName === '' || $periodName === $toApName) {
                continue;
            }

            if ($this->endsWithAcademicPeriodSuffix($name, $periodName)) {
                $stripped = mb_substr($name, 0, mb_strlen($name) - mb_strlen($periodName));

                return rtrim($stripped);
            }
        }

        return $name;
    }

    /**
     * All academic period names, ordered longest first so the most specific
     * suffix is matched before a shorter, partially-overlapping one.
     *
     * @return string[]
     */
    protected function getAcademicPeriodNames(): array
    {
        if ($this->academicPeriodNames !== null) {
            return $this->academicPeriodNames;
        }

        $names = [];
        try {
            $rows = $this->getConnection()
                ->execute('SELECT name FROM academic_periods WHERE name IS NOT NULL')
                ->fetchAll('assoc');

            foreach ($rows as $row) {
                $value = trim((string)($row['name'] ?? ''));
                if ($value !== '') {
                    $names[$value] = $value;
                }
            }
        } catch (\Throwable $e) {
            // If the lookup fails, fall back to no stripping.
            $names = [];
        }

        $names = array_values($names);
        usort($names, static fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $this->academicPeriodNames = $names;
    }

    /**
     * True when $name is exactly $period, or ends with " " + $period.
     */
    protected function endsWithAcademicPeriodSuffix(string $name, string $period): bool
    {
        if ($period === '') {
            return false;
        }

        return $name === $period || mb_substr($name, -mb_strlen(' ' . $period)) === (' ' . $period);
    }

    /**
     * True when $period appears as a full segment bounded by start/end or whitespace.
     * Prevents FROM "2025" matching inside "2025-2026".
     */
    protected function containsStandaloneAcademicPeriod(string $name, string $period): bool
    {
        if ($period === '' || mb_strpos($name, $period) === false) {
            return false;
        }

        $pattern = '/(?:^|\s)' . preg_quote($period, '/') . '(?:$|\s)/u';

        return preg_match($pattern, $name) === 1;
    }

    /**
     * Replace standalone occurrences of FROM period with TO period.
     */
    protected function replaceStandaloneAcademicPeriod(string $name, string $fromPeriod, string $toPeriod): string
    {
        $pattern = '/(?<=^|\s)' . preg_quote($fromPeriod, '/') . '(?=$|\s)/u';

        return preg_replace($pattern, $toPeriod, $name) ?? $name;
    }
    //POCOR-9767 ends    
}
