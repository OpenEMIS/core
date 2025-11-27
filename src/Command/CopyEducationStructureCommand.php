<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\LocatorAwareTrait;

// POCOR-9354
class CopyEducationStructureCommand extends CopyCommandBase
{
    use LocatorAwareTrait;

    // Tables
    private $AcademicPeriods;
    private $EducationSystems;
    private $EducationLevels;
    private $EducationCycles;
    private $EducationProgrammes;
    private $EducationProgrammesNextProgrammes;
    private $EducationGrades;
    private $EducationGradesSubjects;

    public static function defaultName(): string
    {
        // Run as: bin/cake copy:education-structure FROM_PERIOD_ID TO_PERIOD_ID USER_ID
        return 'copy:education-structure';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Copy education structure...');
        return $this->addStandardOptions($parser);
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        ini_set('memory_limit', '2G');

        $this->initializeFromInput($args, $io);

        $this->conn = $this->getConnection();

//        $this->AcademicPeriods                  = $this->getDynamicTableInstance('academic_periods');
        $this->EducationSystems                 = $this->getDynamicTableInstance('education_systems');
        $this->EducationLevels                  = $this->getDynamicTableInstance('education_levels');
        $this->EducationCycles                  = $this->getDynamicTableInstance('education_cycles');
        $this->EducationProgrammes              = $this->getDynamicTableInstance('education_programmes');
        $this->EducationProgrammesNextProgrammes= $this->getDynamicTableInstance('education_programmes_next_programmes');
        $this->EducationGrades                  = $this->getDynamicTableInstance('education_grades');
        $this->EducationGradesSubjects          = $this->getDynamicTableInstance('education_grades_subjects');

        $this->logMsg('Start Copy Education Structure');
        $fromId = $this->fromId;
        $toId = $this->toId;
        $userId = $this->userId;
        // Validate periods + get names for suffix-swap
        $fromAp = $this->fromAcademicPeriod;
        $toAp   = $this->toAcademicPeriod;

        // Check destination has no systems yet (same as your shell)
        $existsTo = $this->EducationSystems->find()->where(['academic_period_id' => $toId])->count();
        if ($existsTo > 0) {
            $io->err('Target academic period already has education systems. Aborting.');
            return static::CODE_ERROR;
        }

        $this->conn->begin();
        try {
            $this->copyProcess($fromId, $toId, $userId, (string)$fromAp->name, (string)$toAp->name, $io);

            if ($this->dryRun) {
                $this->logMsg('<info>Dry-run: rolling back.</info>');
                $this->conn->rollback();
            } else {
                $this->conn->commit();
            }
        } catch (\Throwable $e) {
            $this->conn->rollback();
            $io->err($e->getMessage());
            // $io->err($e->getTraceAsString()); // uncomment for deep debug
            return static::CODE_ERROR;
        }

        $this->logMsg('End Education Structure Copy');
        return static::CODE_SUCCESS;
    }

    private function copyProcess(int $fromPeriod, int $toPeriod, int $userId, string $fromApName, string $toApName, ConsoleIo $io): void
    {
        $now = date('Y-m-d H:i:s');

        // Maps: old_id => new_id
        $levelMap = [];
        $cycleMap = [];
        $programmeMap = [];
        $gradeMap = [];
        $pendingEdges = []; // POCOR-9356

        // 1) Education Systems in FROM period
        $systems = $this->EducationSystems->find()
            ->where(['academic_period_id' => $fromPeriod])
            ->order(['`order`'])
            ->all();

        foreach ($systems as $sys) {
            // If a name ends with " <fromApName>", rewrite it to " <toApName>"
            $newSystemName = $this->swapNameTail((string)$sys->name, $fromApName, $toApName);
            $this->logMsg('Start Education Structure Copy To ' . $newSystemName);

            // Avoid duplicates in TO: same system name for that TO period
            $existing = $this->EducationSystems->find()
                ->where([
                    'academic_period_id' => $toPeriod,
                    'name'               => $newSystemName,
                ])->first();

            if ($existing) {
                $newSystemId = (int)$existing->id;
                $this->logMsg("System exists: {$newSystemName} (id={$newSystemId})");
            } else {
                $entity = $this->EducationSystems->newEntity([
                    'name'               => $newSystemName,
                    'academic_period_id' => $toPeriod,
                    'order'            => $sys->order ?? 1,
                    'visible'            => $sys->visible ?? 1,
                    'created_user_id'    => $sys->created_user_id ?? $userId,
                    'created'            => $sys->created ?? $now,
                ]);
                $this->saveOrThrow($this->EducationSystems, $entity, 'education_systems');
                $newSystemId = (int)$entity->id;
                $this->logMsg("System +: {$newSystemName} (id={$newSystemId})");
            }

            // 2) Levels under this system
            $levels = $this->EducationLevels->find()
                ->where(['education_system_id' => $sys->id])
                ->order(['`order`'])
                ->all();

            foreach ($levels as $lvl) {
                $lvlName = $this->swapNameTail((string)$lvl->name, $fromApName, $toApName);

                // insert (no dedupe across names here; structure copy expects fresh tree)
                $lvlEntity = $this->EducationLevels->newEntity([
                    'education_system_id'      => $newSystemId,
                    'education_level_isced_id' => $lvl->education_level_isced_id,
                    'name'                     => $lvlName,
                    'order'                  => $lvl->order,
                    'visible'                  => $lvl->visible ?? 1,
                    'created_user_id'          => $userId,
                    'created'                  => $now,
                ]);

                $this->saveOrThrow($this->EducationLevels, $lvlEntity, 'education_levels');
                $newLevelId = (int)$lvlEntity->id;
                $levelMap[(int)$lvl->id] = $newLevelId;

                // 3) Cycles under level
                $cycles = $this->EducationCycles->find()
                    ->where(['education_level_id' => $lvl->id])
                    ->order(['`order`'])
                    ->all();

                foreach ($cycles as $cyc) {
                    $cycName = $this->swapNameTail((string)$cyc->name, $fromApName, $toApName);

                    $cycEntity = $this->EducationCycles->newEntity([
                        'education_level_id' => $newLevelId,
                        'name'               => $cycName,
                        'admission_age'      => $cyc->admission_age,
                        'order'            => $cyc->order,
                        'visible'            => $cyc->visible ?? 1,
                        'created_user_id'    => $userId,
                        'created'            => $now,
                    ]);
                    $this->saveOrThrow($this->EducationCycles, $cycEntity, 'education_cycles');
                    $newCycleId = (int)$cycEntity->id;
                    $cycleMap[(int)$cyc->id] = $newCycleId;

                    // 4) Programmes under cycle
                    $progs = $this->EducationProgrammes->find()
                        ->where(['education_cycle_id' => $cyc->id])
                        ->order(['`order`'])
                        ->all();

                    foreach ($progs as $prg) {
                        $progName = $this->swapNameTail((string)$prg->name, $fromApName, $toApName);

                        $progEntity = $this->EducationProgrammes->newEntity([
                            'education_cycle_id'          => $newCycleId,
                            'education_field_of_study_id' => $prg->education_field_of_study_id,
                            'education_certification_id'  => $prg->education_certification_id,
                            'code'                        => $prg->code,
                            'name'                        => $progName,
                            'duration'                    => $prg->duration,
                            'order'                     => $prg->order,
                            'visible'                     => $prg->visible ?? 1,
                            'created_user_id'             => $userId,
                            'created'                     => $now,
                        ]);
                        $this->saveOrThrow($this->EducationProgrammes, $progEntity, 'education_programmes');
                        $newProgId = (int)$progEntity->id;
                        $programmeMap[(int)$prg->id] = $newProgId;

                        // (A) Programme edges (next_programmes) — MAP to new IDs (fixes legacy shell behavior)
                        //POCOR-9356 -- START
                        $edges = $this->EducationProgrammesNextProgrammes->find()
                            ->where(['education_programme_id' => $prg->id])
                            ->all();

                        foreach ($edges as $edge) {
                            $oldNext = (int)$edge->next_programme_id;
                            $newNext = $programmeMap[$oldNext] ?? null;

                            if ($newNext) {
                                // insert now (and avoid duplicates)
                                $exists = $this->EducationProgrammesNextProgrammes->find()
                                    ->where([
                                        'education_programme_id' => $newProgId,
                                        'next_programme_id'      => $newNext
                                    ])->first();
                                if (!$exists) {
                                    $npEntity = $this->EducationProgrammesNextProgrammes->newEntity([
                                        'id'                     => $this->uuid(),
                                        'education_programme_id' => $newProgId,
                                        'next_programme_id'      => $newNext,
                                    ]);
                                    $this->saveOrThrow($this->EducationProgrammesNextProgrammes, $npEntity, 'education_programmes_next_programmes');
                                }
                            } else {
                                // target not created yet — resolve later
                                $pendingEdges[] = [
                                    'new_programme_id' => $newProgId,
                                    'old_next_id'      => $oldNext,
                                ];
                            }
                        }
                        //POCOR-9356 -- END

                        // 5) Grades under programme
                        $grades = $this->EducationGrades->find()
                            ->where(['education_programme_id' => $prg->id])
                            ->order(['`order`'])
                            ->all();

                        foreach ($grades as $gr) {
                            $gradeName = $this->swapNameTail((string)$gr->name, $fromApName, $toApName);

                            $grEntity = $this->EducationGrades->newEntity([
                                'education_programme_id' => $newProgId,
                                'education_stage_id'     => $gr->education_stage_id, // global ref
                                'code'                   => $gr->code,
                                'name'                   => $gradeName,
                                'admission_age'          => $gr->admission_age,
                                'order'                => $gr->order,
                                'visible'                => $gr->visible ?? 1,
                                'created_user_id'        => $userId,
                                'created'                => $now,
                            ]);
                            $this->saveOrThrow($this->EducationGrades, $grEntity, 'education_grades');
                            $newGradeId = (int)$grEntity->id;
                            $gradeMap[(int)$gr->id] = $newGradeId;

                            // 6) Grade-Subject pairs
                            $pairs = $this->EducationGradesSubjects->find()
                                ->where(['education_grade_id' => $gr->id])
                                ->all();

                            foreach ($pairs as $pair) {
                                // Skip if target pair already exists
                                $existsPair = $this->EducationGradesSubjects->find()
                                    ->where([
                                        'education_grade_id'   => $newGradeId,
                                        'education_subject_id' => (int)$pair->education_subject_id
                                    ])->first();
                                if ($existsPair) {
                                    continue;
                                }

                                $egsEntity = $this->EducationGradesSubjects->newEntity([
                                    'id'                     => $this->uuid(), // column exists even if PK is composite
                                    'education_grade_id'    => $newGradeId,
                                    'education_subject_id'  => (int)$pair->education_subject_id,
                                    'hours_required'        => $pair->hours_required,
                                    'visible'               => $pair->visible ?? 1,
                                    'auto_allocation'       => $pair->auto_allocation ?? 1,
                                    'requirement'           => $pair->requirement,
                                    'result_type'           => $pair->result_type ?? 'Assessments',
                                    'created_user_id'       => $userId,
                                    'created'               => $now,
                                ]);
                                $this->saveOrThrow($this->EducationGradesSubjects, $egsEntity, 'education_grades_subjects');
                            }
                        } // grades
                    } // programmes
                } // cycles
            } // levels
        } // systems
        // POCOR-9356 -- START resolve any deferred edges now that $programmeMap is complete
        foreach ($pendingEdges as $pe) {
            $newProgId = (int)$pe['new_programme_id'];
            $oldNext   = (int)$pe['old_next_id'];
            $newNext   = $programmeMap[$oldNext] ?? null;

            // skip edges that point outside the copied tree (e.g., old 23)
            if (!$newNext) {
                continue;
            }

            $exists = $this->EducationProgrammesNextProgrammes->find()
                ->where([
                    'education_programme_id' => $newProgId,
                    'next_programme_id'      => $newNext
                ])->first();

            if (!$exists) {
                $npEntity = $this->EducationProgrammesNextProgrammes->newEntity([
                    'id'                     => $this->uuid(),
                    'education_programme_id' => $newProgId,
                    'next_programme_id'      => $newNext,
                ]);
                $this->saveOrThrow($this->EducationProgrammesNextProgrammes, $npEntity, 'education_programmes_next_programmes');
            }
        }
        //POCOR-9356 -- END

        $this->logMsg('Copy complete.');
    }

    /**
     * Replace a trailing " <fromApName>" with " <toApName>" if present.
     * Examples:
     *   "National System 2025" + (from="2025", to="2026") → "National System 2026"
     *   "Cycle (2025)"         + (from="2025", to="2026") → unchanged (pattern is only " SPACE + fromName")
     */
    private function swapNameTail(string $name, string $fromApName, string $toApName): string
    {
        $pattern = '/\s+' . preg_quote($fromApName, '/') . '$/u';
        if (preg_match($pattern, $name) === 1) {
            return preg_replace($pattern, ' ' . $toApName, $name) ?? $name;
        }
        return $name;
    }


    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

}
