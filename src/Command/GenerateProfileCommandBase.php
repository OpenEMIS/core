<?php
//POCOR-9598: start - Base command for profile generation, replacing the deprecated Shell pattern
namespace App\Command;

use ArrayObject;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\TableRegistry;

/**
 * Abstract base command for all profile generation workers.
 *
 * Each concrete subclass configures which tables and Excel class to use.
 * The processing loop is:
 *   1. Fetch the oldest NEW_PROCESS record from the process queue.
 *   2. Update it to RUNNING.
 *   3. Try renderExcelTemplate.
 *   4. On failure: mark the profile record as FAILED (5) and delete the process entry.
 *   5. Mark the system process as COMPLETED and spawn the next worker recursively.
 */
abstract class GenerateProfileCommandBase extends Command
{
    use LocatorAwareTrait;

    // -----------------------------------------------------------------------
    // Abstract configuration — implemented by each concrete command
    // -----------------------------------------------------------------------

    /** Human-readable name logged in system_processes (matches old Shell class name). */
    abstract protected function getSystemProcessName(): string;

    /** CakePHP table alias for the process queue, e.g. 'ReportCard.ClassProfileProcesses'. */
    abstract protected function getProcessTableAlias(): string;

    /**
     * Fields to SELECT from the process table.
     * These same fields are used as WHERE conditions for RUNNING / FAILED / DELETE operations.
     *
     * @return string[]
     */
    abstract protected function getProcessSelectFields(): array;

    /** CakePHP table alias of the Excel-render model, e.g. 'CustomExcel.ClassProfiles'. */
    abstract protected function getExcelTableAlias(): string;

    /**
     * CakePHP table alias of the PROFILE DATA table (where status is stored).
     * This is different from the Excel table for Class and Staff profiles.
     * e.g. 'Institution.ClassProfiles' vs 'CustomExcel.ClassProfiles'.
     */
    abstract protected function getProfileDataTableAlias(): string;

    /** File to append output to, without path, e.g. 'GenerateAllClassProfiles.log'. */
    abstract protected function getLogFileName(): string;

    // -----------------------------------------------------------------------
    // Optional hooks
    // -----------------------------------------------------------------------

    /**
     * Subclasses may add extra fields to the record before it is passed to renderExcelTemplate.
     * e.g. ClassProfiles adds area_id from the optional third CLI argument.
     */
    protected function enrichRecord(array $record, Arguments $args): array
    {
        return $record;
    }

    // -----------------------------------------------------------------------
    // CakePHP Command wiring
    // -----------------------------------------------------------------------

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->addArgument('model', [
                'help'     => 'Registry alias of the calling model (e.g. Institution.ClassesProfiles)',
                'required' => true,
            ])
            ->addArgument('params', [
                'help'     => 'JSON-encoded params (report_card_id, institution_id, etc.)',
                'required' => true,
            ])
            ->addArgument('area_id', [ //POCOR-9598: optional, used by Class profiles (POCOR-7382)
                'help'     => 'Optional area ID to scope generation to a specific area',
                'required' => false,
            ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $modelArg  = $args->getArgument('model');
        $paramsArg = $args->getArgument('params');

        ////Log::debug('@GenerateProfileCommandBase::execute START model=' . $modelArg . ' params=' . $paramsArg); //[TEMP-LOG]

        if (empty($modelArg) || empty($paramsArg)) {
            ////Log::debug('@GenerateProfileCommandBase::execute empty args, exiting'); //[TEMP-LOG]
            return static::CODE_SUCCESS;
        }

        $SystemProcesses = $this->fetchTable('SystemProcesses');
        $systemProcessId = $SystemProcesses->addProcess(
            $this->getSystemProcessName(),
            getmypid(),
            $modelArg,
            '',
            $paramsArg
        );
        ////Log::debug('@GenerateProfileCommandBase::execute addProcess returned systemProcessId=' . $systemProcessId); //[TEMP-LOG]
        $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, 0);
        ////Log::debug('@GenerateProfileCommandBase::execute system_process_id=' . $systemProcessId . ' set to RUNNING'); //[TEMP-LOG]

        $ProcessTable = $this->fetchTable($this->getProcessTableAlias());
        ////Log::debug('@GenerateProfileCommandBase::execute using ProcessTable=' . $this->getProcessTableAlias() . ' class=' . get_class($ProcessTable)); //[TEMP-LOG]

        // Build SELECT list using aliased field names so CakePHP picks up the right columns.
        $selectFields = array_map(
            fn($f) => $ProcessTable->aliasField($f),
            $this->getProcessSelectFields()
        );

        $recordToProcess = $ProcessTable->find()
            ->select($selectFields)
            ->where([$ProcessTable->aliasField('status') => $ProcessTable::NEW_PROCESS])
            ->order([$ProcessTable->aliasField('created')])
            ->enableHydration(false)
            ->first();

        ////Log::debug('@GenerateProfileCommandBase::execute fetched record: ' . json_encode($recordToProcess)); //[TEMP-LOG]

        if (!empty($recordToProcess)) {
            $io->out('Generating profile record (' . FrozenTime::now() . ')');
            ////Log::debug('@GenerateProfileCommandBase::execute starting generation for record: ' . json_encode($recordToProcess)); //[TEMP-LOG]

            // Mark as RUNNING
            $ProcessTable->updateAll(
                ['status' => $ProcessTable::RUNNING],
                $this->buildConditions($recordToProcess)
            );
            ////Log::debug('@GenerateProfileCommandBase::execute marked process as RUNNING'); //[TEMP-LOG]

            // Build params for Excel rendering
            $excelParams = new ArrayObject([]);
            $excelParams['className']    = $this->getExcelTableAlias();
            $excelParams['requestQuery'] = $this->enrichRecord($recordToProcess, $args);

            ////Log::debug('@GenerateProfileCommandBase::execute enriched requestQuery: ' . json_encode($excelParams['requestQuery'])); //[TEMP-LOG]

            $ExcelTable = $this->fetchTable($this->getExcelTableAlias());
            ////Log::debug('@GenerateProfileCommandBase::execute using ExcelTable=' . $this->getExcelTableAlias() . ' class=' . get_class($ExcelTable)); //[TEMP-LOG]

            ////Log::debug('@GenerateProfileCommandBase::execute about to renderExcelTemplate'); //[TEMP-LOG]
            try {
                $ExcelTable->renderExcelTemplate($excelParams);
                ////Log::debug('@GenerateProfileCommandBase::execute renderExcelTemplate completed successfully'); //[TEMP-LOG]
            } catch (\Exception $e) {
                $io->out('Error: ' . $e->getMessage());
                Log::error('@GenerateProfileCommandBase::execute renderExcelTemplate FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString()); //POCOR-9598: always log full exception to hin-error.log for post-mortem

                //POCOR-9598: mark profile as FAILED and remove from queue so it does not stay stuck
                $ProfileDataTable = $this->fetchTable($this->getProfileDataTableAlias());
                ////Log::debug('@GenerateProfileCommandBase::execute using ProfileDataTable=' . $this->getProfileDataTableAlias() . ' class=' . get_class($ProfileDataTable)); //[TEMP-LOG]
                $conditions = $this->buildConditions($recordToProcess);
                ////Log::debug('@GenerateProfileCommandBase::execute FAILED update conditions=' . json_encode($conditions)); //[TEMP-LOG]
                $ProfileDataTable->updateAll(
                    ['status' => 5],
                    $conditions
                );
                ////Log::debug('@GenerateProfileCommandBase::execute profile marked FAILED (5)'); //[TEMP-LOG]
                ////Log::debug('@GenerateProfileCommandBase::execute deleting process queue with conditions=' . json_encode($conditions)); //[TEMP-LOG]
                $ProcessTable->deleteAll($conditions);
                ////Log::debug('@GenerateProfileCommandBase::execute process queue entry deleted'); //[TEMP-LOG]
            }

            $io->out('End generating profile (' . FrozenTime::now() . ')');
            $SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $SystemProcesses::COMPLETED);
            ////Log::debug('@GenerateProfileCommandBase::execute system_process marked COMPLETED, spawning next'); //[TEMP-LOG]
            $this->recursiveCall($args);
        } else {
            ////Log::debug('@GenerateProfileCommandBase::execute no NEW_PROCESS record found, queue empty'); //[TEMP-LOG]
            $SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $SystemProcesses::COMPLETED);
        }

        $this->killSelf();
        ////Log::debug('@GenerateProfileCommandBase::execute exiting'); //[TEMP-LOG]
        return static::CODE_SUCCESS;
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a WHERE condition array from the fetched process record.
     * Uses all select fields as composite key conditions.
     */
    protected function buildConditions(array $record): array
    {
        $conditions = [];
        foreach ($this->getProcessSelectFields() as $field) {
            if (isset($record[$field])) {
                $conditions[$field] = $record[$field];
            }
        }
        ////Log::debug('@GenerateProfileCommandBase::buildConditions built conditions=' . json_encode($conditions)); //[TEMP-LOG]
        return $conditions;
    }

    /**
     * Spawn the next worker instance in the background (same command, same args).
     */
    private function recursiveCall(Arguments $args): void
    {
        $commandName = static::defaultName();
        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $commandName
            . ' ' . escapeshellarg($args->getArgument('model'))
            . ' ' . escapeshellarg($args->getArgument('params'));
        // Forward optional area_id if provided (POCOR-7382 / POCOR-9598)
        $areaId = $args->getArgument('area_id');
        if (!empty($areaId)) {
            $cmd .= ' ' . escapeshellarg($areaId);
        }
        $logs    = ROOT . DS . 'logs' . DS . $this->getLogFileName() . ' 2>&1 & echo $!'; //POCOR-9598: 2>&1 captures stderr from recursive worker cycles
        $shellCmd = $cmd . ' >> ' . $logs;
        ////Log::debug('@GenerateProfileCommandBase::recursiveCall spawning command=' . $commandName . ' with full cmd=' . $shellCmd); //[TEMP-LOG]
        try {
            $result = exec($shellCmd);
            ////Log::debug('@GenerateProfileCommandBase::recursiveCall exec returned: ' . $result); //[TEMP-LOG]
            Log::write('debug', $shellCmd);
        } catch (\Exception $ex) {
            Log::error('@GenerateProfileCommandBase::recursiveCall exception: ' . $ex->getMessage());
            Log::write('error', __METHOD__ . ' recursive call failed: ' . $ex->getMessage());
        }
    }

    private function killSelf(): void
    {
        try {
            $pid = getmypid();
            ////Log::debug('@GenerateProfileCommandBase::killSelf killing pid=' . $pid); //[TEMP-LOG]
            if (function_exists('posix_kill')) {
                posix_kill($pid, 9);
            } else {
                exec("kill -15 $pid");
            }
        } catch (\Exception $e) {
            ////Log::debug('@GenerateProfileCommandBase::killSelf exception killing self: ' . $e->getMessage()); //[TEMP-LOG]
            // ignore — process will exit naturally
        }
    }
}
//POCOR-9598: end
