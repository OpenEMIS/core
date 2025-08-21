<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

// POCOR-9354
class InstitutionCopyProgramsGradesCommand extends Command
{
    /** @var \Cake\Database\Connection */
    private $conn;

    private bool $dryRun   = false;
    private bool $verbose  = true;
    private int  $userId   = 2;

    public static function defaultName(): string
    {
        // Run as: bin/cake institution:copy-programs-grades FROM_PERIOD_ID TO_PERIOD_ID [-u 2] [--dry-run]
        return 'institution:copy-programs-grades';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('Copy Institution Grades and Institution Program Grade Subjects from one academic period to another (education structure must already exist in the target).')
            ->addArgument('from', ['help' => 'Source academic_period_id', 'required' => true])
            ->addArgument('to',   ['help' => 'Target academic_period_id', 'required' => true])
            ->addOption('dry-run', ['help' => 'Log actions without writing', 'boolean' => true, 'default' => false])
            ->addOption('quiet',   ['help' => 'Reduce output', 'boolean' => true, 'default' => false])
            ->addOption('user',    ['help' => 'created_user_id/modified_user_id for inserts', 'short' => 'u', 'default' => 2]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        ini_set('memory_limit', '2G');

        $fromId      = (int)$args->getArgument('from');
        $toId        = (int)$args->getArgument('to');
        $this->dryRun  = (bool)$args->getOption('dry-run');
        $this->verbose = !$args->getOption('quiet');
        $this->userId  = (int)$args->getOption('user') ?: 2;
        $this->conn    = ConnectionManager::get('default');

        $io->out("=== Institution copy (programs → grades → IPGS) ===");
        $io->out("from=$fromId → to=$toId " . ($this->dryRun ? '[dry-run]' : ''));

        // Academic period names (for tail swap in structure names)
        [$fromApName, $toApName] = $this->getPeriodNames($fromId, $toId);

        // Target period dates/years for IG rows
        $toPeriodMeta = $this->conn->execute(
            "SELECT start_date, start_year, end_date, end_year
             FROM academic_periods WHERE id = ?",
            [$toId]
        )->fetch('assoc');
        if (!$toPeriodMeta) {
            $io->err("Target academic period #$toId not found.");
            return static::CODE_ERROR;
        }

        $this->conn->begin();
        try {
            $io->out("→ Building grade map (by path + codes) …");
            $gradeMap = $this->buildGradeMap($fromId, $toId, $fromApName, $toApName, $io);
            $io->out("  Grade map entries: " . count($gradeMap));

            $io->out("→ Copying institution_grades …");
            $igMap = $this->copyInstitutionGrades($fromId, $toId, $toPeriodMeta, $gradeMap, $io);

            $io->out("→ Copying institution_program_grade_subjects (valid EGS only) …");
            $this->copyIPGS($fromId, $igMap, $gradeMap, $io);

            if ($this->dryRun) {
                $io->out('<info>Dry-run complete: rolling back.</info>');
                $this->conn->rollback();
            } else {
                $this->conn->commit();
                $io->out('<info>Committed.</info>');
            }
            return static::CODE_SUCCESS;
        } catch (\Throwable $e) {
            $this->conn->rollback();
            $io->err($e->getMessage());
            return static::CODE_ERROR;
        }
    }

    // ---------------------------------------------------------------------
    // STEP 1: Map grades FROM → TO using path + codes
    // Key: "sys|lvl|cyc|prog_code|grade_code"
    // Path names are normalized by swapping a trailing " <fromApName>" to " <toApName>"
    // so "National System 2025" will match "National System 2026", etc.
    // ---------------------------------------------------------------------
    private function buildGradeMap(int $fromPeriod, int $toPeriod, string $fromAp, string $toAp, ConsoleIo $io): array
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

        // (Fix minor typo: INNERJOIN → INNER JOIN)
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
            $sys = $this->swapTail($r['sys_name'], $fromAp, $toAp);
            $lvl = $this->swapTail($r['lvl_name'], $fromAp, $toAp);
            $cyc = $this->swapTail($r['cyc_name'], $fromAp, $toAp);
            $key = $this->keyPath($sys, $lvl, $cyc) . '|' . $r['prog_code'] . '|' . $r['grade_code'];

            if (isset($toIndex[$key])) {
                $map[(int)$r['grade_id']] = $toIndex[$key];
            } else {
                $missing++;
                $this->v($io, "  ↷ No target grade for {$key} (skipping related IG/IPGS rows)");
            }
        }
        if ($missing) {
            $io->out("  Unmapped grades (will be skipped): {$missing}");
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
        int $fromPeriod,
        int $toPeriod,
        array $toPeriodMeta,
        array $gradeMap,
        ConsoleIo $io
    ): array {
        $rows = $this->conn->execute(
            "SELECT id, education_grade_id, academic_period_id, institution_id
             FROM institution_grades
             WHERE academic_period_id = ?",
            [$fromPeriod]
        )->fetchAll('assoc');

        $outMap   = []; // old_ig_id => new_ig_id
        $inserted = 0; $existing = 0; $skipped = 0;

        foreach ($rows as $r) {
            $oldIgId   = (int)$r['id'];
            $oldGrade  = (int)$r['education_grade_id'];
            $instId    = (int)$r['institution_id'];
            $newGrade  = $gradeMap[$oldGrade] ?? null;

            if (!$newGrade) {
                $skipped++;
                $this->v($io, "  ↷ IG#{$oldIgId} skipped: no grade map for grade {$oldGrade}");
                continue;
            }

            // Exists?
            $exists = $this->conn->execute(
                "SELECT id FROM institution_grades
                 WHERE education_grade_id = ? AND academic_period_id = ? AND institution_id = ? LIMIT 1",
                [$newGrade, $toPeriod, $instId]
            )->fetch('assoc');

            if ($exists) {
                $outMap[$oldIgId] = (int)$exists['id'];
                $existing++;
                $this->v($io, "  ↺ IG exists for inst {$instId}, grade {$newGrade} → #{$exists['id']}");
                continue;
            }

            if ($this->dryRun) {
                $fakeId = -1 * ($inserted + 1);
                $outMap[$oldIgId] = $fakeId;
                $inserted++;
                $this->v($io, "  ✎ (dry-run) Would insert IG for inst {$instId}, grade {$newGrade}");
                continue;
            }

            $this->conn->execute(
                "INSERT INTO institution_grades
                 (education_grade_id, academic_period_id, start_date, start_year, end_date, end_year,
                  institution_id, modified_user_id, modified, created_user_id, created)
                 VALUES (:grade,:period,:sdate,:syear,:edate,:eyear,:inst,:muid,:mod,:cuid,:crt)",
                [
                    'grade' => $newGrade,
                    'period'=> $toPeriod,
                    'sdate' => $toPeriodMeta['start_date'],
                    'syear' => $toPeriodMeta['start_year'],
                    'edate' => null,
                    'eyear' => null,
                    'inst'  => $instId,
                    'muid'  => $this->userId,
                    'mod'   => date('Y-m-d H:i:s'),
                    'cuid'  => $this->userId,
                    'crt'   => date('Y-m-d H:i:s'),
                ]
            );
            $newId = (int)$this->conn->getDriver()->lastInsertId();
            $outMap[$oldIgId] = $newId;
            $inserted++;
            $this->v($io, "  ✓ IG inserted → #{$newId} (inst {$instId}, grade {$newGrade})");
        }

        $io->out("  InstitutionGrades: inserted={$inserted}, existing={$existing}, skipped={$skipped}");
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
        ConsoleIo $io
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

        $inserted = 0; $existing = 0; $skipped = 0; $blocked = 0;

        foreach ($src as $r) {
            $oldIG   = (int)$r['institution_grade_id'];
            $oldGr   = (int)$r['education_grade_id'];
            $subjId  = (int)$r['education_grade_subject_id'];
            $instId  = (int)$r['institution_id'];

            $newIG   = $igMap[$oldIG]   ?? null;
            $newGr   = $gradeMap[$oldGr] ?? null;

            if (!$newIG || !$newGr) {
                $skipped++;
                $this->v($io, "  ↷ IPGS skip: missing IG/Grade map (oldIG {$oldIG} -> ".($newIG ?? '∅').", oldGr {$oldGr} -> ".($newGr ?? '∅').")");
                continue;
            }

            // Guard: only copy if (newGr, subjId) exists in EGS
            if (empty($allowed[$newGr . ':' . $subjId])) {
                $blocked++;
                $this->v($io, "  ⚠︎ IPGS blocked: subject {$subjId} is not linked to grade {$newGr} in EGS");
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
                $this->v($io, "  ✎ (dry-run) Would add IPGS: IG#{$newIG}, grade#{$newGr}, subject#{$subjId}, inst#{$instId}");
                continue;
            }

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
                    'uid' => $this->userId,
                    'ts'  => date('Y-m-d H:i:s'),
                ]
            );
            $inserted++;
        }

        $io->out("  IPGS: inserted={$inserted}, existing={$existing}, skipped_no_map={$skipped}, blocked_not_in_EGS={$blocked}");
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------
    private function getPeriodNames(int $fromId, int $toId): array
    {
        $rows = $this->conn->execute(
            "SELECT id, name FROM academic_periods WHERE id IN (?, ?)",
            [$fromId, $toId]
        )->fetchAll('assoc');

        $byId = [];
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = (string)$r['name'];
        }
        if (!isset($byId[$fromId], $byId[$toId])) {
            throw new \RuntimeException('Academic period name(s) not found.');
        }
        return [$byId[$fromId], $byId[$toId]];
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

    private function v(ConsoleIo $io, string $msg): void
    {
        if ($this->verbose) $io->out($msg);
    }
}
