<?php
namespace Institution\Model\Traits;

use Cake\ORM\TableRegistry;

trait StudentCreationCheckTrait
{
    /**
     * Returns true if the current user may create a new student at the given grade.
     * Logic: feature disabled → allow. Excluded role → allow. Grade not allowed → block.
     *
     * When $institutionId and $academicPeriodId are provided, "first grade" means the
     * lowest-order grade of the same programme that the institution actually runs for
     * that period. Without them, falls back to global order=1 check.
     *
     * @param int|null $gradeId          education_grades.id
     * @param int|null $institutionId    institution_id for institution-grade context
     * @param int|null $academicPeriodId academic_period_id for institution-grade context
     * @return bool
     */
    private function isStudentCreationAllowed(?int $gradeId, ?int $institutionId = null, ?int $academicPeriodId = null): bool //POCOR-9385: student creation gate
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        // Feature disabled → always allow //POCOR-9385: feature toggle check
        if ($ConfigItems->value('restrict_student_creation') != 1) {
            return true;
        }

        // Excluded roles bypass //POCOR-9385: excluded roles check
        $excludedRaw = $ConfigItems->value('student_creation_excluded_roles');
        if (!empty($excludedRaw)) {
            $excludedIds = array_filter(explode(',', $excludedRaw));
            $userId = method_exists($this, 'getUserID') ? $this->getUserID() : (int)$this->request->getSession()->read('Auth.User.id');
            $instId  = $institutionId ?? (method_exists($this, 'getInstitutionID') ? ($this->getInstitutionID() ?: 0) : 0);

            if ($instId > 0) {
                $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $userRoles = $Institutions->getInstitutionRoles($userId, $instId); //POCOR-9385: institution roles
            } else {
                $userRoles = $this->AccessControl->getRolesByUser($userId)->toArray(); //POCOR-9385: directory fallback
            }

            foreach ($userRoles as $roleId) {
                if (in_array((string)$roleId, $excludedIds, true)) {
                    return true; //POCOR-9385: role excluded — bypass restriction
                }
            }
        }

        // No grade context → block //POCOR-9385: no grade = block
        if (empty($gradeId)) {
            return false;
        }

        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $grade = $EducationGrades->find()
            ->where([$EducationGrades->aliasField('id') => $gradeId])
            ->first();

        if (!$grade) {
            return true; //POCOR-9385: grade not found → allow (safe default)
        }

        //POCOR-9385: when institution+period context is available, check against the lowest-order
        //grade of this programme that the institution actually runs for that period
        if (!empty($institutionId) && !empty($academicPeriodId)) {
            $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $minOrder = $InstitutionGrades->find()
                ->select(['min_order' => 'MIN(EducationGrades.order)'])
                ->join([
                    'EducationGrades' => [
                        'table'      => 'education_grades',
                        'type'       => 'INNER',
                        'conditions' => 'EducationGrades.id = InstitutionGrades.education_grade_id',
                    ],
                ])
                ->where([
                    'InstitutionGrades.institution_id'    => $institutionId,
                    'InstitutionGrades.academic_period_id' => $academicPeriodId,
                    'EducationGrades.education_programme_id' => $grade->education_programme_id, //POCOR-9385: same programme only
                ])
                ->disableHydration()
                ->first();

            $minOrderValue = $minOrder['min_order'] ?? null;

            if ($minOrderValue === null) {
                return true; //POCOR-9385: institution has no grades for this programme+period → allow (safe default)
            }

            return (int)$grade->order === (int)$minOrderValue; //POCOR-9385: first in institution's programme for this period
        }

        // Fallback: global order=1 check (no institution/period context e.g. import without full row data) //POCOR-9385
        return (int)$grade->order === 1;
    }

    /**
     * Block message for UI (Add form validation error).
     */
    private function studentCreationBlockMessage(string $gradeName): string //POCOR-9385: block message with grade name
    {
        return sprintf(
            __('Student creation is not permitted for %s. Only authorised entry grades may create new students. Please search for an existing student instead.'),
            $gradeName
        );
    }

    /**
     * Block message for Directory context (no grade name available).
     */
    private function studentCreationBlockMessageNoGrade(): string //POCOR-9385: directory block message
    {
        return __('Student creation is currently restricted. Only authorised entry grades may create new students. Please search for an existing student instead.');
    }
}
