<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\EntityInterface;

// POCOR-9354
class CopyInstitutionProgramsGradesSubjectsCommand extends CopyCommandBase
{


    public static function defaultName(): string
    {
        // Run as: bin/cake institution:copy-programs-grades FROM_PERIOD_ID TO_PERIOD_ID [-u 2] [--dry-run]
        return 'copy:institution-programs-grades-subjects';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $this->addStandardOptions(
            $parser->setDescription('Copy Institution Grades and Institution Program Grade Subjects from one academic period to another (education structure must already exist in the target).')
        );
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        ini_set('memory_limit', '2G');

        $this->initializeFromInput($args, $io);
        $fromId = $this->fromId;
        $toId   = $this->toId;
        $userId = $this->userId;

        $this->logMsg("=== Institution copy (programs -> grades -> IPGS) ===");
        $this->logMsg("from=$fromId -> to=$toId " . ($this->dryRun ? '[dry-run]' : ''));

        // Academic period names (for tail swap in structure names)

        $fromApName = $this->fromAcademicPeriod->name;
        $toApName   = $this->toAcademicPeriod->name;
        $toAp   = $this->toAcademicPeriod;

        // Target period dates/years for IG rows

        $this->conn = $this->getConnection();
        $this->conn->begin();
        try {
            $this->logMsg("-> Building grade map (by path + codes) …");
            $gradeMap = $this->buildGradeMap($fromId, $toId, $fromApName, $toApName);
            $this->logMsg("  Grade map entries: " . count($gradeMap));

            $this->logMsg("-> Copying institution_grades …");
            $igMap = $this->copyInstitutionGrades($fromId, $toId, $userId, $toAp, $gradeMap);

            $this->logMsg("-> Copying institution_program_grade_subjects (valid EGS only) …");
            $this->copyIPGS($fromId, $igMap, $gradeMap, $userId);

            if ($this->dryRun) {
                $this->logMsg('<info>Dry-run complete: rolling back.</info>');
                $this->conn->rollback();
            } else {
                $this->conn->commit();
                $this->logMsg('<info>Committed.</info>');
            }
            return static::CODE_SUCCESS;
        } catch (\Throwable $e) {
            $this->conn->rollback();
            $this->logMsg('<error>' . $e->getMessage() .'</error>');
            return static::CODE_ERROR;
        }
    }

    // ---------------------------------------------------------------------
    // STEP 1: Map grades FROM -> TO using path + codes
    // Key: "sys|lvl|cyc|prog_code|grade_code"
    // Path names are normalized by swapping a trailing " <fromApName>" to " <toApName>"
    // so "National System 2025" will match "National System 2026", etc.
    // ---------------------------------------------------------------------
    private function buildGradeMap(int $fromPeriod, int $toPeriod, string $fromApName, string $toApName): array
    {
        $sqlFrom = "
            SELECT
                s.name AS sys_name,
                l.name AS lvl_name,
                c.name AS cyc_name,
                p.code AS prog_code,
                g.code AS grade_code,
                g.id   AS grade_id
            FROM education_systems s
            INNER JOIN education_levels     l ON l.education_system_id = s.id
            INNER JOIN education_cycles     c ON c.education_level_id  = l.id
            INNER JOIN education_programmes p ON p.education_cycle_id  = c.id
            INNER JOIN education_grades     g ON g.education_programme_id = p.id
            WHERE s.academic_period_id = :pid
        ";

        $sqlTo = "
            SELECT
                s.name AS sys_name,
                l.name AS lvl_name,
                c.name AS cyc_name,
                p.code AS prog_code,
                g.code AS grade_code,
                g.id   AS grade_id
            FROM education_systems s
            INNER JOIN education_levels     l ON l.education_system_id = s.id
            INNERJOIN education_cycles      c ON c.education_level_id  = l.id
            INNER JOIN education_programmes p ON p.education_cycle_id  = c.id
            INNER JOIN education_grades     g ON g.education_programme_id = p.id
            WHERE s.academic_period_id = :pid
        ";

        // (Fix minor typo: INNERJOIN -> INNER JOIN)
        $sqlTo = str_replace('INNERJOIN', 'INNER JOIN', $sqlTo);

        $fromRows = $this->conn->execute($sqlFrom, ['pid' => $fromPeriod])->fetchAll('assoc');
        $toRows   = $this->conn->execute($sqlTo,   ['pid' => $toPeriod])->fetchAll('assoc');

        // Index target rows by normalized key
        $toIndex = [];
        foreach ($toRows as $r) {
            $key = $this->keyPath($r['sys_name'], $r['lvl_name'], $r['cyc_name']) . '|' . $r['prog_code'] . '|' . $r['grade_code'];
            $toIndex[$key] = (int)$r['grade_id'];
        }

        $map = [];
        $missing = 0;
        foreach ($fromRows as $r) {
            $sys = $this->swapTail($r['sys_name'], $fromApName, $toApName);
            $lvl = $this->swapTail($r['lvl_name'], $fromApName, $toApName);
            $cyc = $this->swapTail($r['cyc_name'], $fromApName, $toApName);
            $key = $this->keyPath($sys, $lvl, $cyc) . '|' . $r['prog_code'] . '|' . $r['grade_code'];

            if (isset($toIndex[$key])) {
                $map[(int)$r['grade_id']] = $toIndex[$key];
            } else {
                $missing++;
                $this->logMsg( "  ~ No target grade for {$key} (skipping related IG/IPGS rows)");
            }
        }
        if ($missing) {
            $this->logMsg("  Unmapped grades (will be skipped): {$missing}");
        }
        return $map;
    }

    // ---------------------------------------------------------------------
    // STEP 2: Copy institution_grades (IG)
    // - For each IG in FROM period, if its grade maps to a target grade, insert IG for TO period
    //   unless an identical row already exists.
    // - Uses start_date/start_year from the TO period.
    // ---------------------------------------------------------------------
    private function copyInstitutionGrades(
        int       $fromPeriod,
        int       $toPeriod,
        int       $userId,
        EntityInterface   $toAp,
        array     $gradeMap
    ): array {
        $rows = $this->conn->execute(
            "SELECT id, education_grade_id, academic_period_id, institution_id
         FROM institution_grades
         WHERE academic_period_id = ?",
            [$fromPeriod]
        )->fetchAll('assoc');

        $outMap   = []; // old_ig_id => new_ig_id
        $inserted = 0;
        $existing = 0;
        $skipped  = 0;
        $failed   = 0;

        foreach ($rows as $r) {
            $oldIgId   = (int)$r['id'];
            $oldGrade  = (int)$r['education_grade_id'];
            $instId    = (int)$r['institution_id'];
            $newGrade  = $gradeMap[$oldGrade] ?? null;

            if (!$newGrade) {
                $skipped++;
                $this->logMsg("  ~ IG#{$oldIgId} skipped: no grade map for grade {$oldGrade}");
                continue;
            }

            // Already exists?
            $exists = $this->conn->execute(
                "SELECT id FROM institution_grades
             WHERE education_grade_id = ? AND academic_period_id = ? AND institution_id = ? LIMIT 1",
                [$newGrade, $toPeriod, $instId]
            )->fetch('assoc');

            if ($exists) {
                $outMap[$oldIgId] = (int)$exists['id'];
                $existing++;
                $this->logMsg("  ↺ IG exists for inst {$instId}, grade {$newGrade} -> #{$exists['id']}");
                continue;
            }

            if ($this->dryRun) {
                $fakeId = -1 * ($inserted + 1);
                $outMap[$oldIgId] = $fakeId;
                $inserted++;
                $this->logMsg(" ? (dry-run) Would insert IG for inst={$instId}, grade={$newGrade}, period={$toPeriod}");
                continue;
            }

            $params = [
                'grade' => $newGrade,
                'period'=> $toPeriod,
                'sdate' => $toAp->start_date
                ? $toAp->start_date->format('Y-m-d')
                : null, //POCOR-9533
                'syear' => $toAp->start_year ?? null,
                'edate' => null,
                'eyear' => null,
                'inst'  => $instId,
                'muid'  => $userId,
                'mod'   => date('Y-m-d H:i:s'),
                'cuid'  => $userId,
                'crt'   => date('Y-m-d H:i:s'),
            ];

            // pre-insert context
            $this->logMsg(sprintf(
                "  -> IG insert pending: inst=%d, grade=%d, period=%d, start_date=%s, start_year=%s",
                $instId, $newGrade, $toPeriod,
                $params['sdate'] ?? 'NULL',
                (string)($params['syear'] ?? 'NULL')
            ));

            try {
                $this->conn->execute(
                    "INSERT INTO institution_grades
                 (education_grade_id, academic_period_id, start_date, start_year, end_date, end_year,
                  institution_id, modified_user_id, modified, created_user_id, created)
                 VALUES (:grade,:period,:sdate,:syear,:edate,:eyear,:inst,:muid,:mod,:cuid,:crt)",
                    $params
                );

                // get the new id (MySQL / MariaDB path; works with Cake's PDO driver)
                $newId = (int)$this->conn->getDriver()->lastInsertId();
                if ($newId <= 0) {
                    // defensive: some drivers require a fallback
                    $newId = (int)$this->conn->execute("SELECT LAST_INSERT_ID() AS id")->fetch('assoc')['id'];
                }

                if ($newId <= 0) {
                    throw new \RuntimeException('lastInsertId() returned 0/NULL');
                }

                $outMap[$oldIgId] = $newId;
                $inserted++;
                $this->logMsg("  ✓ IG inserted -> #{$newId} (inst {$instId}, grade {$newGrade})");
            } catch (\Throwable $e) {
                $failed++;
                // log the failure and keep going (do NOT rethrow)
                $this->io->err(sprintf(
                    "  x IG insert FAILED: inst=%d, grade=%d, period=%d. Error: %s",
                    $instId, $newGrade, $toPeriod, $e->getMessage()
                ));
                continue;
            }
        }

        $this->logMsg("  InstitutionGrades: inserted={$inserted}, existing={$existing}, skipped={$skipped}, failed={$failed}");
        return $outMap;
    }

    // ---------------------------------------------------------------------
    // STEP 3: Copy IPGS (Institution Program Grade Subjects)
    // - Source rows = IPGS joined to IG (filtered by FROM period)
    // - Insert only if:
    //   a) old IG maps to a new IG
    //   b) old education_grade_id maps to a new grade
    //   c) (new grade, subject) exists in education_grades_subjects (guard)
    //   d) identical IPGS row doesn’t already exist
    // ---------------------------------------------------------------------
    private function copyIPGS(
        int $fromPeriod,
        array $igMap,      // old_ig_id => new_ig_id
        array $gradeMap,   // old_grade_id => new_grade_id
        int $userId
    ): void {
        // Pull source IPGS with their *old* IG and Grade
        $src = $this->conn->execute(
            "SELECT ipgs.institution_grade_id,
                ipgs.education_grade_id,
                ipgs.education_grade_subject_id,  -- this references education_subjects.id
                ipgs.institution_id
         FROM institution_program_grade_subjects ipgs
         INNER JOIN institution_grades ig
                 ON ig.id = ipgs.institution_grade_id
         WHERE ig.academic_period_id = ?",
            [$fromPeriod]
        )->fetchAll('assoc');

        // Build a fast set of allowed (grade, subject) based on EGS
        $egs = $this->conn->execute(
            "SELECT education_grade_id, education_subject_id
         FROM education_grades_subjects"
        )->fetchAll('assoc');

        $allowed = [];
        foreach ($egs as $r) {
            $allowed[(int)$r['education_grade_id'] . ':' . (int)$r['education_subject_id']] = true;
        }

        // Cache of valid institutions to avoid FK failures due to orphans
        $instRows = $this->conn->execute("SELECT id FROM institutions")->fetchAll('assoc');
        $validInst = [];
        foreach ($instRows as $ir) {
            $validInst[(int)$ir['id']] = true;
        }

        // Quick set of valid NEW IG ids (skip negative fake ids from a dry-run feed)
        $validNewIG = [];
        foreach ($igMap as $old => $new) {
            if ($new && $new > 0) {
                $validNewIG[(int)$new] = true;
            }
        }

        $inserted = 0;
        $existing = 0;
        $skipped  = 0;     // no map
        $blocked  = 0;     // not in EGS
        $missInst = 0;     // missing institution
        $missIG   = 0;     // missing/new IG not persisted
        $failed   = 0;     // exception on insert

        foreach ($src as $r) {
            $oldIG   = (int)$r['institution_grade_id'];
            $oldGr   = (int)$r['education_grade_id'];
            $subjId  = (int)$r['education_grade_subject_id'];
            $instId  = (int)$r['institution_id'];

            $newIG   = $igMap[$oldIG]    ?? null;
            $newGr   = $gradeMap[$oldGr] ?? null;

            // require maps
            if (!$newIG || !$newGr) {
                $skipped++;
                $this->logMsg("  ~ IPGS skip: missing IG/Grade map (oldIG {$oldIG} -> ".($newIG ?? '∅').", oldGr {$oldGr} -> ".($newGr ?? '∅').")");
                continue;
            }
            // skip negative ids from a dry-run feed or if IG not actually persisted
            if ($newIG <= 0 || empty($validNewIG[$newIG])) {
                $missIG++;
                $this->logMsg("  ~ IPGS skip: new IG#{$newIG} not persisted/valid (oldIG {$oldIG})");
                continue;
            }
            // guard: institution must exist (avoid FK error if DB has orphans)
            if (empty($validInst[$instId])) {
                $missInst++;
                $this->logMsg("  ~ IPGS skip: institution #{$instId} missing");
                continue;
            }
            // guard: (grade, subject) must exist in EGS
            if (empty($allowed[$newGr . ':' . $subjId])) {
                $blocked++;
                $this->logMsg("  ^ IPGS blocked: subject {$subjId} is not linked to grade {$newGr} in EGS");
                continue;
            }

            // Exists?
            $exists = $this->conn->execute(
                "SELECT id FROM institution_program_grade_subjects
             WHERE institution_grade_id = ? AND education_grade_id = ?
               AND education_grade_subject_id = ? AND institution_id = ?
             LIMIT 1",
                [$newIG, $newGr, $subjId, $instId]
            )->fetch('assoc');

            if ($exists) {
                $existing++;
                continue;
            }

            if ($this->dryRun) {
                $inserted++;
                $this->logMsg(" ? (dry-run) Would add IPGS: IG#{$newIG}, grade#{$newGr}, subject#{$subjId}, inst#{$instId}");
                continue;
            }

            // pre-insert context for troubleshooting
            $this->logMsg("  -> IPGS insert pending: IG={$newIG}, grade={$newGr}, subject={$subjId}, inst={$instId}");

            try {
                $this->conn->execute(
                    "INSERT INTO institution_program_grade_subjects
                 (institution_grade_id, education_grade_id, education_grade_subject_id,
                  institution_id, created_user_id, created)
                 VALUES (:ig, :gr, :subj, :inst, :uid, :ts)",
                    [
                        'ig'  => $newIG,
                        'gr'  => $newGr,
                        'subj'=> $subjId,
                        'inst'=> $instId,
                        'uid' => $userId,
                        'ts'  => date('Y-m-d H:i:s'),
                    ]
                );
                $inserted++;
            } catch (\Throwable $e) {
                $failed++;
                $this->io->err(sprintf(
                    "  x IPGS insert FAILED: IG=%d, grade=%d, subject=%d, inst=%d. Error: %s",
                    $newIG, $newGr, $subjId, $instId, $e->getMessage()
                ));
                // keep going
                continue;
            }
        }

        $this->logMsg("  IPGS: inserted={$inserted}, existing={$existing}, skipped_no_map={$skipped}, blocked_not_in_EGS={$blocked}, missing_institution={$missInst}, missing_new_IG={$missIG}, failed={$failed}");
    }


    private function keyPath(string $sys, string $lvl, string $cyc): string
    {
        return $sys . '|' . $lvl . '|' . $cyc;
    }

    /**
     * Replace a trailing " SPACE + fromTail" with " SPACE + toTail", if present.
     * e.g., "National System 2025" -> "National System 2026"
     */
    private function swapTail(string $name, string $fromTail, string $toTail): string
    {
        $pattern = '/\s+' . preg_quote($fromTail, '/') . '$/u';
        return preg_replace($pattern, ' ' . $toTail, $name) ?? $name;
    }

}
