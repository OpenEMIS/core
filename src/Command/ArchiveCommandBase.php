<?php

namespace App\Command;

use App\Service\ArchiveService;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

//POCOR-8898: start - abstract base command replacing common main() logic across all 4 Archive*Shell files
abstract class ArchiveCommandBase extends Command
{
    public int $pid = 0; //POCOR-8898
    public string $processName = ''; //POCOR-8898
    public string $featureName = ''; //POCOR-8898
    public int $systemProcessId = 0; //POCOR-8898
    public int $recordsToArchive = 0; //POCOR-8898
    public int $recordsToArchiveTotal = 0; //POCOR-8898
    public int $recordsInArchive = 0; //POCOR-8898
    public int $academicPeriodId = 0; //POCOR-8898

    protected ConsoleIo $io; //POCOR-8898

    abstract protected function getTablesToArchive(): array; //POCOR-8898
    abstract protected function getProcessName(): string; //POCOR-8898
    abstract protected function getFeatureName(): string; //POCOR-8898

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        //POCOR-8898: define 4 positional arguments matching the old Shell $args[0..3]
        $parser
            ->addArgument('academic_period_id', [
                'help' => 'Academic period ID to archive',
                'required' => true,
            ])
            ->addArgument('pid', [
                'help' => 'Transfer log p_id',
                'required' => true,
            ])
            ->addArgument('records_to_archive', [
                'help' => 'Number of records to archive',
                'required' => false,
            ])
            ->addArgument('records_in_archive', [
                'help' => 'Number of records already in archive',
                'required' => false,
            ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        //POCOR-8898: template execute() — mirrors old Shell main() logic, identical across all 4 shells
        $this->io = $io;

        $academicPeriodId = (int)($args->getArgument('academic_period_id') ?? 0); //POCOR-8898
        $pid = (int)($args->getArgument('pid') ?? 0); //POCOR-8898
        $recordsToArchive = (int)($args->getArgument('records_to_archive') ?? 0); //POCOR-8898
        $recordsInArchive = (int)($args->getArgument('records_in_archive') ?? 0); //POCOR-8898

        $this->recordsInArchive = $recordsInArchive; //POCOR-8898
        $this->recordsToArchive = $recordsToArchive; //POCOR-8898
        $this->recordsToArchiveTotal = $recordsToArchive; //POCOR-8898
        $this->academicPeriodId = $academicPeriodId; //POCOR-8898

        $io->out("academic period id: $academicPeriodId, process id : $pid, recordsToArchive: $recordsToArchive, recordsInArchive: $recordsInArchive");

        $processName = $this->getProcessName(); //POCOR-8898
        $tablesToArchive = $this->getTablesToArchive(); //POCOR-8898

        if ($academicPeriodId === 0) { //POCOR-8898
            $io->error('No valid academic period given');
            return CommandInterface::CODE_ERROR;
        }
        if ($pid === 0) { //POCOR-8898
            $io->error('No valid pid given');
            return CommandInterface::CODE_ERROR;
        }

        $processedDateTime = date('d-m-Y H:i:s'); //POCOR-8898
        $io->out("Initializing $processName:  $processedDateTime");
        $mypid = getmypid(); //POCOR-8898
        $systemProcessId = ArchiveService::startArchiveTransferSystemProcess($academicPeriodId, $mypid, $processName, $pid); //POCOR-8898
        $processedDateTime = ArchiveService::setSystemProcessRunning($systemProcessId); //POCOR-8898
        $io->out($processedDateTime . ' - Running System PID:' . $systemProcessId);

        $this->pid = $pid; //POCOR-8898
        $this->processName = $processName; //POCOR-8898
        $this->featureName = $this->getFeatureName(); //POCOR-8898
        $this->systemProcessId = $systemProcessId; //POCOR-8898
        $tableMovedOK = true; //POCOR-8898

        foreach ($tablesToArchive as $tableToArchive) {
            try {
                $tableMovedOK = $tableMovedOK &&
                    ArchiveService::moveRecordsToArchive($academicPeriodId, $tableToArchive, $this); //POCOR-8898
            } catch (\Exception $e) {
                $io->out("Error in $processName");
                $io->out($e->getMessage());
                $io->out("Transfer failed $processName:  $processedDateTime");
                $processedDateTime = ArchiveService::setSystemProcessFailed($systemProcessId); //POCOR-8898
                $io->out("System process failed $processName:  $processedDateTime");
                exit(1); //POCOR-7895
            }
            $io->out("Finished archiving records for $tableToArchive");
        }

        if ($tableMovedOK) {
            try {
                //POCOR-8898: Check if this archive will make year non-editable
                $archiveStatus = ArchiveService::checkArchiveCompletionStatus($academicPeriodId, $this->featureName); //POCOR-8898
                if ($archiveStatus['hasWarning'] && $archiveStatus['alert']) { //POCOR-8898
                    $io->warning($archiveStatus['alert']); //POCOR-8898
                    $TransferLogs = \Cake\ORM\TableRegistry::getTableLocator()->get('Archive.TransferLogs'); //POCOR-8898
                    $TransferLogs->logArchiveCompletionAlert($pid, $archiveStatus['alert']); //POCOR-8898
                } //POCOR-8898

                $processedDateTime = ArchiveService::setTransferLogsCompleted($pid); //POCOR-8898
                $io->out("Transfer completed $processName:  $processedDateTime");
                $processedDateTime = ArchiveService::setSystemProcessCompleted($systemProcessId); //POCOR-8898
                $io->out("System process completed $processName:  $processedDateTime");
            } catch (\Exception $e) {
                $io->out("Error in $processName");
                $io->out($e->getMessage());
                $processedDateTime = ArchiveService::setTransferLogsFailed($pid); //POCOR-8898
                $io->out("Transfer failed $processName:  $processedDateTime");
                $processedDateTime = ArchiveService::setSystemProcessFailed($systemProcessId); //POCOR-8898
                $io->out("System process failed $processName:  $processedDateTime");
                throw $e;
            }
        }

        if (!$tableMovedOK) {
            $processedDateTime = ArchiveService::setTransferLogsFailed($pid); //POCOR-8898
            $io->out("Transfer failed $processName:  $processedDateTime");
            $processedDateTime = ArchiveService::setSystemProcessFailed($systemProcessId); //POCOR-8898
            $io->out("System process failed $processName:  $processedDateTime");
            $io->out("No records to update ");
        }

        $io->out("Ended $processName:  $processedDateTime");
        return CommandInterface::CODE_SUCCESS; //POCOR-8898
    }

    public function out(string $message): void
    {
        //POCOR-8898: proxy so ArchiveService can call $caller->out() as before
        $this->io->out($message);
    }

}
//POCOR-8898: end
