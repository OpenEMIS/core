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
        $restrictStudentCreation = $ConfigItems->value('restrict_student_creation');
        if ($restrictStudentCreation != 1) {
            return true;
        }

        // Super admin bypass //POCOR-9385: super_admin is never restricted
        if ($this->request->getSession()->read('Auth.User.super_admin') == 1) {
            return true;
        }

        // Excluded roles bypass //POCOR-9385: single source of truth, also used by the dropdown filter
        if ($this->isUserExcludedFromStudentCreationRestriction($institutionId)) {
            return true;
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

        //POCOR-9385: when institution+period context is available, validate against the SAME
        //entry-grade set that getEducationGrade offers in the dropdown (single source of truth in
        //InstitutionGradesTable::getEntryEducationGradeIds) so the picker can never offer a grade
        //that Save rejects (no select-then-error).
        if (!empty($institutionId) && !empty($academicPeriodId)) {
            $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $entryIds = $InstitutionGrades->getEntryEducationGradeIds((int)$institutionId, (int)$academicPeriodId);

            if (empty($entryIds)) {
                return true; //POCOR-9385: institution has no grades for this period → allow (safe default)
            }

            return in_array((int)$gradeId, $entryIds, true); //POCOR-9385: gradeId must be an entry grade offered by the dropdown
        }

        // Fallback: global order=1 check (no institution/period context e.g. import without full row data) //POCOR-9385
        return (int)$grade->order === 1;
    }

    /**
     * Returns true if the acting user's role is on the "Excluded Security Roles" list for the
     * restrict_student_creation config item — meaning they may bypass the entry-grade rule.
     * Shared by isStudentCreationAllowed() (Save) and InstitutionsController::getEducationGrade()
     * (dropdown filter) so both read the excluded-roles list the exact same way.
     *
     * @param int|null $institutionId when omitted, falls back to $this->getInstitutionID() (if
     *                                available) and then to the Directory-context AccessControl roles
     * @return bool
     */
    private function isUserExcludedFromStudentCreationRestriction(?int $institutionId = null): bool //POCOR-9385
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $excludedRaw = $ConfigItems->valueSelection('restrict_student_creation'); //POCOR-9385: stored on the same row as the toggle

        if (empty($excludedRaw)) {
            return false;
        }

        $excludedIds = array_filter(explode(',', $excludedRaw));
        $userId = method_exists($this, 'getUserID') ? $this->getUserID() : (int)$this->request->getSession()->read('Auth.User.id');
        $instId = $institutionId ?? (method_exists($this, 'getInstitutionID') ? ($this->getInstitutionID() ?: 0) : 0);

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

        return false;
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

    /**
     * Block message for Directory context (no grade name available).
     */
    private function studentCreationBlockMessageNoGrade(): string //POCOR-9385: directory block message
    {
        return __('New students can only be enrolled in the entry grade of their education programme.'); //POCOR-9385: cleaner message
    }
}
