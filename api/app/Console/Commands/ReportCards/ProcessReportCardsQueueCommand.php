<?php

namespace App\Console\Commands\ReportCards;

use App\Services\ReportCard\ReportCardGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ports GenerateAllReportCardsShell::main() + recursiveCallToMyself()
 */
class ProcessReportCardsQueueCommand extends Command
{
    protected $signature = 'reportcards:process
                            {--system_process_id= : ID of the system_processes record for this worker}
                            {--once               : Process a single record then exit (used by the scheduler)}';

    protected $description = 'Process the next pending report card from the queue';

    public function handle(ReportCardGeneratorService $generator): int
    {
        // Set timezone from config_items
        $timeZone = DB::table('config_items')->where('code', 'time_zone')->value('value');
        if ($timeZone) {
            date_default_timezone_set($timeZone);
        }

        $systemProcessId = $this->option('system_process_id');
        $now             = date('Y-m-d H:i:s');

        // Register / update system_processes record to RUNNING (status=2)
        if ($systemProcessId) {
            DB::table('system_processes')
                ->where('id', $systemProcessId)
                ->update([
                    'status'   => 2, // RUNNING
                    'modified' => $now,
                ]);
        } else {
            $systemProcessId = DB::table('system_processes')->insertGetId([
                'name'     => 'GenerateReportCards',
                'status'   => 2, // RUNNING
                'created'  => $now,
                'modified' => $now,
            ]);
        }

        // Fetch the next NEW record
        $record = DB::table('report_card_processes')
            ->where('status', 1) // NEW
            ->orderBy('created')
            ->orderBy('student_id')
            ->first();

        if (!$record) {
            $this->info('No pending report cards to process.');
            $this->markSystemProcessComplete($systemProcessId);
            return self::SUCCESS;
        }

        // Mark as RUNNING (status=2)
        DB::table('report_card_processes')
            ->where([
                'report_card_id'       => $record->report_card_id,
                'institution_class_id' => $record->institution_class_id,
                'student_id'           => $record->student_id,
            ])
            ->update(['status' => 2, 'modified' => $now]);

        $this->info("Processing report card for student {$record->student_id}");

        try {
            $generator->generate((array) $record);
            $this->markSystemProcessComplete($systemProcessId);
        } catch (\Throwable $e) {
            Log::error('ProcessReportCardsQueue: failed for student ' . $record->student_id, [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->error("Error generating report card: " . $e->getMessage());

            // Mark record as ERROR (status=-1)
            DB::table('report_card_processes')
                ->where([
                    'report_card_id'       => $record->report_card_id,
                    'institution_class_id' => $record->institution_class_id,
                    'student_id'           => $record->student_id,
                ])
                ->update(['status' => -1, 'modified' => $now]);

            DB::table('institution_students_report_cards')
                ->where([
                    'report_card_id'     => $record->report_card_id,
                    'student_id'         => $record->student_id,
                    'academic_period_id' => $record->academic_period_id,
                    'education_grade_id' => $record->education_grade_id,
                    'institution_id'     => $record->institution_id,
                ])
                ->update(['status' => -1, 'modified' => $now]);

            return self::FAILURE;
        }

        // Unless --once, recurse to pick up the next record
        if (!$this->option('once')) {
            $artisanCmd = 'php ' . base_path('artisan') . ' reportcards:process';
            $logFile    = storage_path('logs/GenerateReportCards.log');
            exec("{$artisanCmd} >> {$logFile} 2>&1 &");
        }

        return self::SUCCESS;
    }

    private function markSystemProcessComplete(int $systemProcessId): void
    {
        DB::table('system_processes')
            ->where('id', $systemProcessId)
            ->update([
                'status'   => 3, // COMPLETED
                'modified' => date('Y-m-d H:i:s'),
            ]);
    }
}
