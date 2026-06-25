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

}
