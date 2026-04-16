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
        \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed input gradeId=' . json_encode($gradeId) . ' institutionId=' . json_encode($institutionId) . ' academicPeriodId=' . json_encode($academicPeriodId)); //[TEMP-LOG]

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        // Feature disabled → always allow //POCOR-9385: feature toggle check
        $restrictStudentCreation = $ConfigItems->value('restrict_student_creation');
        \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed restrict_student_creation=' . json_encode($restrictStudentCreation)); //[TEMP-LOG]

        if ($restrictStudentCreation != 1) {
            return true;
        }

        // Excluded roles bypass //POCOR-9385: excluded roles check
        $excludedRaw = $ConfigItems->value('student_creation_excluded_roles');
        \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed student_creation_excluded_roles=' . json_encode($excludedRaw)); //[TEMP-LOG]

        if (!empty($excludedRaw)) {
            $excludedIds = array_filter(explode(',', $excludedRaw));
            $userId = method_exists($this, 'getUserID') ? $this->getUserID() : (int)$this->request->getSession()->read('Auth.User.id');
            \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed userId=' . json_encode($userId)); //[TEMP-LOG]

            $instId  = $institutionId ?? (method_exists($this, 'getInstitutionID') ? ($this->getInstitutionID() ?: 0) : 0);

            if ($instId > 0) {
                $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $userRoles = $Institutions->getInstitutionRoles($userId, $instId); //POCOR-9385: institution roles
            } else {
                $userRoles = $this->AccessControl->getRolesByUser($userId)->toArray(); //POCOR-9385: directory fallback
            }

            \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed userRoles=' . json_encode($userRoles) . ' excludedIds=' . json_encode($excludedIds)); //[TEMP-LOG]

            $roleExcluded = false;
            foreach ($userRoles as $roleId) {
                if (in_array((string)$roleId, $excludedIds, true)) {
                    $roleExcluded = true;
                    break;
                }
            }

            \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed roleExcluded=' . json_encode($roleExcluded)); //[TEMP-LOG]

            if ($roleExcluded) {
                return true; //POCOR-9385: role excluded — bypass restriction
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
            \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed grade not found for gradeId=' . json_encode($gradeId)); //[TEMP-LOG]
            return true; //POCOR-9385: grade not found → allow (safe default)
        }

        \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed grade->order=' . json_encode($grade->order) . ' grade->education_programme_id=' . json_encode($grade->education_programme_id)); //[TEMP-LOG]

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

            \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed minOrderValue=' . json_encode($minOrderValue) . ' (int)grade->order=' . json_encode((int)$grade->order)); //[TEMP-LOG]

            if ($minOrderValue === null) {
                \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed institution has no grades for programme+period, allowing'); //[TEMP-LOG]
                return true; //POCOR-9385: institution has no grades for this programme+period → allow (safe default)
            }

            $result = (int)$grade->order === (int)$minOrderValue;
            \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed institutional context result=' . json_encode($result)); //[TEMP-LOG]
            return $result; //POCOR-9385: first in institution's programme for this period
        }

        // Fallback: global order=1 check (no institution/period context e.g. import without full row data) //POCOR-9385
        $result = (int)$grade->order === 1;
        \Cake\Log\Log::debug('@StudentCreationCheckTrait::isStudentCreationAllowed global fallback result (order===1)=' . json_encode($result)); //[TEMP-LOG]
        return $result;
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
