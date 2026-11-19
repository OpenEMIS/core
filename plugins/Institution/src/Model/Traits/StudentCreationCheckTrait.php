<?php
namespace Institution\Model\Traits;

use Cake\ORM\TableRegistry;

/**
 * "Limit Student Admission to First Grade" gate.
 *
 * POCOR-9925: the rule is configured PER EDUCATION PROGRAMME
 * (Administration > Education Structure > Programmes), not system-wide. The programme is
 * resolved from the grade the student is being added to, so every check needs a grade.
 * Without a grade (e.g. Directory > Add, which places the student in no programme) there
 * is nothing to restrict.
 *
 * The decision logic only talks to the database through findStudentCreationGrade(),
 * getFirstGradeLimitedProgrammeIds() and
 * findVisibleGradesOfProgrammes(), so it can be unit tested without a database.
 */
trait StudentCreationCheckTrait
{
    /**
     * Returns true if the current user may create a new student at the given grade.
     * Logic: no grade → allow. Programme limit disabled → allow. Applies to ALL users (super admin
     * included, no bypass). Otherwise the grade must be the programme's first grade (lowest-order visible grade of the
     * programme — not of the grades the institution offers). Excluded Security Roles are out of
     * scope for POCOR-9925.
     *
     * @param int|null $gradeId          education_grades.id
     * @param int|null $institutionId    kept for caller compatibility; no longer affects the check //POCOR-9925
     * @param int|null $academicPeriodId kept for caller compatibility; no longer affects the check //POCOR-9925
     * @return bool
     */
    private function isStudentCreationAllowed(?int $gradeId, ?int $institutionId = null, ?int $academicPeriodId = null): bool //POCOR-9385 //POCOR-9925
    {
        //POCOR-9925: no grade → no programme → the per-programme rule cannot apply
        if (empty($gradeId)) {
            return true;
        }

        //POCOR-9925: the rule applies to ALL users (super admin included) — no role bypass
        $grade = $this->findStudentCreationGrade((int)$gradeId);
        if (empty($grade)) {
            return true; //POCOR-9385: grade not found → allow (safe default)
        }

        $programmeId = (int)$grade['education_programme_id'];

        //POCOR-9925: rule switched off for this programme → allow
        if (!in_array($programmeId, $this->getFirstGradeLimitedProgrammeIds([$programmeId]), true)) {
            return true;
        }

        //POCOR-9925: only the PROGRAMME's first grade is allowed, regardless of which grades the
        //institution offers (reporter: "It should only allow Grade 1 since it's the first grade of the
        //programme"). Same helper as the Add-Student dropdown filter, so the picker never offers a
        //grade that Save rejects.
        return in_array((int)$gradeId, $this->getProgrammeFirstGradeIds([$programmeId]), true);
    }

    /**
     * POCOR-9925: the first grade (lowest `order` among visible grades) of each given programme.
     *
     * @param int[] $programmeIds
     * @return int[] education_grades.id values
     */
    private function getProgrammeFirstGradeIds(array $programmeIds): array
    {
        $programmeIds = self::normaliseIds($programmeIds);
        if (empty($programmeIds)) {
            return [];
        }
        return self::pickFirstGradeIdPerProgramme($this->findVisibleGradesOfProgrammes($programmeIds));
    }

    /**
     * POCOR-9925: pure — from grade rows (id, order, education_programme_id) return, per programme,
     * the id of the grade with the lowest order. Ties (same order) resolve to the lowest grade id,
     * so the result never depends on the order rows come back from the database.
     *
     * @param array $gradeRows
     * @return int[]
     */
    public static function pickFirstGradeIdPerProgramme(array $gradeRows): array
    {
        $firstByProgramme = []; // programme_id => ['id' => grade id, 'order' => order]
        foreach ($gradeRows as $row) {
            $programmeId = (int)$row['education_programme_id'];
            $order = (int)$row['order'];
            $id = (int)$row['id'];
            $current = $firstByProgramme[$programmeId] ?? null;
            if ($current === null
                || $order < $current['order']
                || ($order === $current['order'] && $id < $current['id'])) {
                $firstByProgramme[$programmeId] = ['id' => $id, 'order' => $order];
            }
        }
        return array_values(array_column($firstByProgramme, 'id'));
    }

    /**
     * POCOR-9925: pure — Add-Student grade dropdown filter. Rows of restricted programmes are kept
     * only if they are that programme's first grade; rows of other programmes are kept untouched.
     *
     * @param array $gradeOptions           rows with `education_grade_id` and `_programme_id`
     * @param int[] $restrictedProgrammeIds programmes with the limit Enabled
     * @param int[] $firstGradeIds          first grade ids of those programmes
     * @return array re-indexed rows
     */
    public static function filterGradeOptionsToFirstGrade(array $gradeOptions, array $restrictedProgrammeIds, array $firstGradeIds): array
    {
        $restrictedProgrammeIds = self::normaliseIds($restrictedProgrammeIds);
        $firstGradeIds = self::normaliseIds($firstGradeIds);
        return array_values(array_filter($gradeOptions, function ($row) use ($restrictedProgrammeIds, $firstGradeIds) {
            return !in_array((int)$row['_programme_id'], $restrictedProgrammeIds, true)
                || in_array((int)$row['education_grade_id'], $firstGradeIds, true);
        }));
    }

    /**
     * POCOR-9925: the subset of $programmeIds that have "Limit Student Admission to First Grade" Enabled.
     *
     * @param int[] $programmeIds
     * @return int[]
     */
    private function getFirstGradeLimitedProgrammeIds(array $programmeIds): array
    {
        $programmeIds = self::normaliseIds($programmeIds);
        if (empty($programmeIds)) {
            return [];
        }

        $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
        return array_map('intval', $EducationProgrammes->find()
            ->select(['id'])
            ->where([
                $EducationProgrammes->aliasField('id IN') => $programmeIds,
                $EducationProgrammes->aliasField('limit_student_admission_first_grade') => 1,
            ])
            ->disableHydration()
            ->all()
            ->extract('id')
            ->toList());
    }

    /**
     * POCOR-9925: DB lookup — the grade's id, order and programme, or null.
     */
    private function findStudentCreationGrade(int $gradeId): ?array
    {
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $grade = $EducationGrades->find()
            ->select(['id', 'order', 'education_programme_id'])
            ->where([$EducationGrades->aliasField('id') => $gradeId])
            ->disableHydration()
            ->first();
        return $grade ?: null;
    }

    /**
     * POCOR-9925: DB lookup — visible grades (id, order, education_programme_id) of the programmes.
     *
     * @param int[] $programmeIds non-empty
     * @return array
     */
    private function findVisibleGradesOfProgrammes(array $programmeIds): array
    {
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        return $EducationGrades->find()
            ->select(['id', 'order', 'education_programme_id'])
            ->where([
                $EducationGrades->aliasField('education_programme_id IN') => $programmeIds,
                $EducationGrades->aliasField('visible') => 1,
            ])
            ->disableHydration()
            ->toArray();
    }

    /**
     * @param array $ids
     * @return int[] unique positive ints
     */
    private static function normaliseIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /**
     * Block message for UI (Add form validation error).
     */
    private function studentCreationBlockMessage(string $gradeName): string //POCOR-9385: block message with grade name
    {
        return sprintf(
            __('New students can only be enrolled in the entry grade. %s is not an entry grade for this programme.'), //POCOR-9385: cleaner message
            $gradeName
        );
    }
}
