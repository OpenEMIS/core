<?php

namespace App\Console\Commands\ReportCards;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ports ReportCardStatusesTable::addReportCardsToProcesses()
 *       + triggerGenerateAllReportCardsShell()
 */
class QueueReportCardsCommand extends Command
{
    const MAX_PROCESSES = 2;

    protected $signature = 'reportcards:queue
                            {institution_id : Institution ID}
                            {class_id       : Institution class ID}
                            {report_card_id : Report card ID}
                            {--student_id=  : Optional single student ID}';

    protected $description = 'Queue report card generation for a class (or single student)';

    public function handle(): int
    {
        // Set timezone from config_items
        $timeZone = DB::table('config_items')->where('code', 'time_zone')->value('value');
        if ($timeZone) {
            date_default_timezone_set($timeZone);
        }

        $institutionId    = (int) $this->argument('institution_id');
        $classId          = (int) $this->argument('class_id');
        $reportCardId     = (int) $this->argument('report_card_id');
        $studentId        = $this->option('student_id') ? (int) $this->option('student_id') : null;

        Log::debug("QueueReportCards: report_card={$reportCardId} class={$classId} institution={$institutionId}");

        // Fetch students in class
        $query = DB::table('institution_class_students')
            ->select([
                'institution_class_students.student_id',
                'institution_class_students.institution_id',
                'institution_class_students.academic_period_id',
                'institution_class_students.education_grade_id',
                'institution_class_students.institution_class_id',
            ])
            ->where('institution_class_students.institution_class_id', $classId);

        if (!is_null($studentId)) {
            $query->where('institution_class_students.student_id', $studentId);
        }

        // Exclude withdrawn students (join institution_students_report_cards)
        $query->leftJoin(
            'institution_students_report_cards',
            function ($join) use ($reportCardId) {
                $join->on('institution_students_report_cards.student_id', '=', 'institution_class_students.student_id')
                    ->on('institution_students_report_cards.institution_id', '=', 'institution_class_students.institution_id')
                    ->on('institution_students_report_cards.academic_period_id', '=', 'institution_class_students.academic_period_id')
                    ->on('institution_students_report_cards.education_grade_id', '=', 'institution_class_students.education_grade_id')
                    ->where('institution_students_report_cards.report_card_id', '=', $reportCardId);
            }
        );

        $classStudents = $query->get();

        if ($classStudents->isEmpty()) {
            $this->info('No students found to queue.');
            return self::SUCCESS;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($classStudents as $student) {
            $idKeys = [
                'report_card_id'       => $reportCardId,
                'institution_class_id' => $student->institution_class_id,
                'student_id'           => $student->student_id,
            ];

            // Upsert into report_card_processes (status=1 NEW)
            $existing = DB::table('report_card_processes')
                ->where($idKeys)
                ->first();

            if ($existing) {
                DB::table('report_card_processes')
                    ->where($idKeys)
                    ->update([
                        'status'           => 1, // NEW
                        'institution_id'   => $student->institution_id,
                        'education_grade_id' => $student->education_grade_id,
                        'academic_period_id' => $student->academic_period_id,
                        'modified'         => $now,
                    ]);
            } else {
                DB::table('report_card_processes')->insert(array_merge($idKeys, [
                    'status'             => 1, // NEW
                    'institution_id'     => $student->institution_id,
                    'education_grade_id' => $student->education_grade_id,
                    'academic_period_id' => $student->academic_period_id,
                    'created'            => $now,
                    'modified'           => $now,
                ]));
            }

            // Upsert institution_students_report_cards (status=2 IN_PROGRESS)
            $recordKeys = [
                'report_card_id'     => $reportCardId,
                'student_id'         => $student->student_id,
                'academic_period_id' => $student->academic_period_id,
                'education_grade_id' => $student->education_grade_id,
                'institution_id'     => $student->institution_id,
            ];

            $isrcExists = DB::table('institution_students_report_cards')
                ->where($recordKeys)
                ->exists();

            if ($isrcExists) {
                DB::table('institution_students_report_cards')
                    ->where($recordKeys)
                    ->update([
                        'status'               => 2, // IN_PROGRESS
                        'started_on'           => $now,
                        'completed_on'         => null,
                        'file_name'            => null,
                        'file_content'         => null,
                        'institution_class_id' => $student->institution_class_id,
                    ]);
            } else {
                DB::table('institution_students_report_cards')->insert(array_merge($recordKeys, [
                    'status'               => 2, // IN_PROGRESS
                    'started_on'           => $now,
                    'institution_class_id' => $student->institution_class_id,
                ]));
            }

            // Delete any pending email processes for this student
            DB::table('report_card_email_processes')
                ->where($idKeys)
                ->delete();
        }

        Log::debug("QueueReportCards: queued {$classStudents->count()} students");

        // Count currently running system processes
        $thirtyMinAgo = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        $runningCount = DB::table('system_processes')
            ->where('name', 'GenerateReportCards')
            ->whereIn('status', [1, 2])
            ->where('created', '>=', $thirtyMinAgo)
            ->count();

        if ($runningCount <= self::MAX_PROCESSES) {
            // Insert a system_processes record then dispatch the worker
            $processId = DB::table('system_processes')->insertGetId([
                'name'    => 'GenerateReportCards',
                'status'  => 1, // NEW
                'created' => $now,
                'modified' => $now,
            ]);

            $this->info("Dispatching reportcards:process (system_process_id={$processId})");

            // Fire-and-forget: dispatch as a background process
            $artisanCmd = 'php ' . base_path('artisan') . ' reportcards:process'
                . ' --system_process_id=' . $processId;
            $logFile    = storage_path('logs/GenerateReportCards.log');
            exec("{$artisanCmd} >> {$logFile} 2>&1 &");
        } else {
            $this->info("Max processes ({self::MAX_PROCESSES}) already running — skipping dispatch.");
        }

        return self::SUCCESS;
    }
}
