<?php
namespace Institution\Model\Traits;

use Cake\ORM\TableRegistry;

trait StudentCreationCheckTrait
{
    /**
     * Returns true if the current user may create a new student at the given grade.
     * Logic: feature disabled → allow. Excluded role → allow. Grade not allowed → block.
     *
     * @param int|null $gradeId education_grades.id (null = no grade context → block if feature on)
     * @return bool
     */
    private function isStudentCreationAllowed(?int $gradeId): bool //POCOR-9385: student creation gate
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
            // getUserID() is available on ControllerActionTable; fall back to session for import context //POCOR-9385: user id fallback
            $userId = method_exists($this, 'getUserID') ? $this->getUserID() : (int)$this->request->getSession()->read('Auth.User.id');
            $institutionId = method_exists($this, 'getInstitutionID') ? ($this->getInstitutionID() ?: 0) : 0;

            if ($institutionId > 0) {
                $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $userRoles = $Institutions->getInstitutionRoles($userId, $institutionId); //POCOR-9385: institution roles
            } else {
                // Directory context — no institution; use AccessControl //POCOR-9385: directory fallback
                $userRoles = $this->AccessControl->getRolesByUser($userId)->toArray();
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

        // Calculate: is this grade order=1 in its programme? //POCOR-9385: entry grade = order 1, no table needed
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades'); //POCOR-9385: use Education.EducationGrades
        $grade = $EducationGrades->find()
            ->where([$EducationGrades->aliasField('id') => $gradeId]) //POCOR-9385: fetch grade by id
            ->first();

        if (!$grade) {
            return true; //POCOR-9385: grade not found → allow (safe default)
        }

        return (int)$grade->order === 1; //POCOR-9385: first grade of programme = entry grade
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
