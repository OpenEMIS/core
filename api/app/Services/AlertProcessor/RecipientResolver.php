<?php
declare(strict_types=1);

namespace App\Services\AlertProcessor;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Service for resolving alert recipients
 *
 * Handles finding recipients by security roles and institutions.
 * Mirrors the logic from CakePHP's AlertCommandBase.
 *
 * @package App\Services\AlertProcessor
 */
class RecipientResolver
{
    const GUARDIAN = 9;
    const STUDENT = 8;

    /**
     * POCOR-9509: Get contact list for users with specific security roles
     *
     * Retrieves contact information (email and phone) for users assigned to specified security roles.
     * Supports filtering by institution and institution class, with special handling for academic
     * institutions to include their education headquarters contacts.
     *
     * @param array $securityRoles Array of role objects/arrays with 'id' field
     * @param int|null $institutionId Optional institution ID to filter by
     * @param int|null $institutionClassId Optional institution class ID to filter by
     * @return array Contact list ['email' => [...], 'phone' => [...]]
     */
    public function getRoleAssociatedContactList(
        array $securityRoles,
        ?int $institutionId = null,
        ?int $institutionClassId = null
    ): array {
        $contactList = ['email' => [], 'phone' => []];

        // Get all relevant security user IDs based on roles and filters
        $securityUserIds = $this->collectSecurityUserIds($securityRoles, $institutionId, $institutionClassId);

        if (empty($securityUserIds)) {
            return $contactList;
        }

        // Fetch active users and return their contact information
        $users = $this->fetchActiveUsers($securityUserIds);

        return $this->getContactsFromUsers($users, $contactList);
    }

    /**
     * Collect all security user IDs based on roles and filtering criteria
     *
     * @param array $securityRoles
     * @param int|null $institutionId
     * @param int|null $institutionClassId
     * @return array Unique array of security user IDs
     */
    private function collectSecurityUserIds(
        array $securityRoles,
        ?int $institutionId,
        ?int $institutionClassId
    ): array {
        $allSecurityUserIds = [];

        foreach ($securityRoles as $role) {
            $roleId = $this->extractRoleId($role);

            $userIds = $this->getUserIdsByContext($roleId, $institutionId, $institutionClassId);

            $allSecurityUserIds = array_merge($allSecurityUserIds, $userIds);
        }

        return array_unique($allSecurityUserIds);
    }

    /**
     * Extract role ID from role object or array
     *
     * @param mixed $role
     * @return int
     */
    private function extractRoleId($role): int
    {
        return is_array($role) ? $role['id'] : $role->id;
    }

    /**
     * Get user IDs based on the filtering context (global, institution, or class)
     *
     * @param int $roleId
     * @param int|null $institutionId
     * @param int|null $institutionClassId
     * @return array
     */
    private function getUserIdsByContext(
        int $roleId,
        ?int $institutionId,
        ?int $institutionClassId
    ): array {
        // Class-level filtering
        if ($institutionClassId !== null) {
            return $this->getUserIdsByClass($roleId, $institutionClassId);
        }

        // Institution-level filtering
        if ($institutionId !== null) {
            return $this->getUserIdsByInstitution($roleId, $institutionId);
        }

        // Global: all users with this role
        return $this->getAllUserIdsForRole($roleId);
    }

    /**
     * Get all user IDs for a role (no institution filtering)
     *
     * @param int $roleId
     * @return array
     */
    private function getAllUserIdsForRole(int $roleId): array
    {
        return DB::table('security_group_users')
            ->select('security_user_id')
            ->distinct()
            ->where('security_role_id', $roleId)
            ->pluck('security_user_id')
            ->toArray();
    }

    /**
     * Get user IDs associated with an institution (direct and indirect)
     * For academic institutions, also includes users from the education headquarters
     *
     * @param int $roleId
     * @param int $institutionId
     * @return array
     */
    private function getUserIdsByInstitution(int $roleId, int $institutionId): array
    {
        // Get users directly associated with the institution
        $directUserIds = $this->getDirectInstitutionUserIds($roleId, $institutionId);

        // Get users indirectly associated via security_group_institutions
        $indirectUserIds = $this->getIndirectInstitutionUserIds($roleId, $institutionId);

        $allUserIds = array_merge($directUserIds, $indirectUserIds);

        // If institution is academic, include users from education headquarters
        if ($this->isAcademicInstitution($institutionId)) {
            $headquartersUserIds = $this->getEducationHeadquartersUserIds($roleId, $institutionId);
            $allUserIds = array_merge($allUserIds, $headquartersUserIds);
        }

        return $allUserIds;
    }

    /**
     * Get user IDs directly associated with an institution
     * (via institution.security_group_id)
     *
     * @param int $roleId
     * @param int $institutionId
     * @return array
     */
    private function getDirectInstitutionUserIds(int $roleId, int $institutionId): array
    {
        return DB::table('security_group_users')
            ->join('institutions', function ($join) use ($institutionId) {
                $join->on('institutions.security_group_id', '=', 'security_group_users.security_group_id')
                    ->where('institutions.id', '=', $institutionId);
            })
            ->where('security_group_users.security_role_id', $roleId)
            ->distinct()
            ->pluck('security_group_users.security_user_id')
            ->toArray();
    }

    /**
     * Get user IDs indirectly associated with an institution
     * (via security_group_institutions table)
     *
     * @param int $roleId
     * @param int $institutionId
     * @return array
     */
    private function getIndirectInstitutionUserIds(int $roleId, int $institutionId): array
    {
        return DB::table('security_group_users')
            ->join('security_group_institutions', function ($join) use ($institutionId) {
                $join->on('security_group_institutions.security_group_id', '=', 'security_group_users.security_group_id')
                    ->where('security_group_institutions.institution_id', '=', $institutionId);
            })
            ->where('security_group_users.security_role_id', $roleId)
            ->distinct()
            ->pluck('security_group_users.security_user_id')
            ->toArray();
    }

    /**
     * Check if an institution is academic
     *
     * @param int $institutionId
     * @return bool
     */
    private function isAcademicInstitution(int $institutionId): bool
    {
        $institution = DB::table('institutions')
            ->select('classification')
            ->where('id', $institutionId)
            ->first();

        // Adjust this condition based on your actual classification values
        // Assuming 'academic' or similar value indicates academic institution
        return $institution && $institution->classification == 1;
    }

    /**
     * Get user IDs from the education headquarters for the area of an academic institution
     *
     * @param int $roleId
     * @param int $institutionId
     * @return array
     */
    private function getEducationHeadquartersUserIds(int $roleId, int $institutionId): array
    {
        // Get the area_id for the academic institution
        $areaId = DB::table('institutions')
            ->where('id', $institutionId)
            ->value('area_id');

        if (!$areaId) {
            return [];
        }

        // Find the non-academic (education headquarters) institution for this area
        $headquartersIds = DB::table('institutions')
            ->where('area_id', $areaId)
            ->where('classification', '!=', 1) // Non-academic
            ->pluck('id')
            ->toArray();

        if (empty($headquartersIds)) {
            return [];
        }

        $allHeadquartersUserIds = [];

        // Get users from each headquarters institution
        foreach ($headquartersIds as $hqId) {
            $directIds = $this->getDirectInstitutionUserIds($roleId, $hqId);
            $indirectIds = $this->getIndirectInstitutionUserIds($roleId, $hqId);

            $allHeadquartersUserIds = array_merge($allHeadquartersUserIds, $directIds, $indirectIds);
        }

        return $allHeadquartersUserIds;
    }

    /**
     * Get user IDs associated with an institution class
     *
     * @param int $roleId
     * @param int $institutionClassId
     * @return array
     */
    private function getUserIdsByClass(int $roleId, int $institutionClassId): array
    {
        // Primary staff from institution_classes
        $primaryStaffIds = DB::table('institution_classes')
            ->where('id', $institutionClassId)
            ->distinct()
            ->pluck('staff_id')
            ->toArray();

        // Secondary staff from institution_classes_secondary_staff
        $secondaryStaffIds = DB::table('institution_classes_secondary_staff')
            ->where('institution_class_id', $institutionClassId)
            ->distinct()
            ->pluck('secondary_staff_id')
            ->toArray();

        return array_merge($primaryStaffIds, $secondaryStaffIds);
    }

    /**
     * Fetch active users by their security user IDs
     *
     * @param array $securityUserIds
     * @return \Illuminate\Support\Collection
     */
    private function fetchActiveUsers(array $securityUserIds)
    {
        return DB::table('security_users')
            ->select([
                'id',
                'openemis_no',
                'first_name',
                'middle_name',
                'third_name',
                'last_name',
                'preferred_name',
                'email',
                'mobile_number',
            ])
            ->whereIn('id', $securityUserIds)
            ->where('status', 1) // Only active users
            ->get();
    }

    /**
     * POCOR-9509: Get contact list for student-associated users (guardians, student)
     *
     * @param array $securityRoles Array of role objects/arrays
     * @param int $studentUserId Student's security_user_id
     * @return array Contact list ['email' => [...], 'phone' => [...]]
     */
    public function getStudentAssociatedContactList(array $securityRoles, int $studentUserId): array
    {
        $contactList = ['email' => [], 'phone' => []];
        $recipients = [];
        $securityRoleIds = [];

        foreach ($securityRoles as $role) {
            $securityRoleIds[] = is_array($role) ? $role['id'] : $role->id;
        }

        // Get guardians if ROLE_GUARDIAN is in the list
        if (in_array(self::GUARDIAN, $securityRoleIds, true)) { // ROLE_GUARDIAN = 9
            $guardians = DB::table('student_guardians')
                ->where('student_id', $studentUserId)
                ->pluck('guardian_id')
                ->toArray();

            if (!empty($guardians)) {
                $recipients = array_merge($recipients, $guardians);
            } else {
                // Log::debug("[POCOR-9509] No guardians found for student ID: {$studentUserId}");
            }
        }

        // Include student if ROLE_STUDENT is in the list
        if (in_array(self::STUDENT, $securityRoleIds, true)) { // ROLE_STUDENT = 8
            $recipients[] = $studentUserId;
        }

        if (empty($recipients)) {
            return $contactList;
        }

        // Get user contact information
        $users = DB::table('security_users')
            ->select([
                'id',
                'openemis_no',
                'first_name',
                'middle_name',
                'third_name',
                'last_name',
                'preferred_name',
                'email',
                'mobile_number',
            ])
            ->whereIn('id', array_unique($recipients))
            ->where('status', 1) // Only active users
            ->get();

        return $this->getContactsFromUsers($users, $contactList);
    }

    /**
     * POCOR-9509: Extract contact information from user records
     *
     * Formats email as "Full Name <email@example.com>"
     * Adds phone numbers directly
     *
     * @param \Illuminate\Support\Collection $users User records
     * @param array $contactList Initial contact list
     * @return array Updated contact list ['email' => [...], 'phone' => [...]]
     */
    protected function getContactsFromUsers($users, array $contactList): array
    {
        foreach ($users as $user) {
            // Build full name
            $nameParts = array_filter([
                $user->first_name,
                $user->middle_name,
                $user->third_name,
                $user->last_name,
            ]);
            $fullName = implode(' ', $nameParts);

            // Add email (formatted as "Name <email>")
            if (!empty($user->email)) {
                $email = trim($fullName) . ' <' . $user->email . '>';
                if (!in_array($email, $contactList['email'], true)) {
                    $contactList['email'][] = $email;
                }
            }

            // Add phone number
            if (!empty($user->mobile_number) && !in_array($user->mobile_number, $contactList['phone'], true)) {
                $contactList['phone'][] = $user->mobile_number;
            }
        }

        return $contactList;
    }

    /**
     * POCOR-9509: Get contact info for specific user IDs
     *
     * Simpler version when you already have the user IDs
     *
     * @param array $userIds Array of security_user_ids
     * @return array Contact list ['email' => [...], 'phone' => [...]]
     */
    public function getContactsForUsers(array $userIds): array
    {
        $contactList = ['email' => [], 'phone' => []];

        if (empty($userIds)) {
            return $contactList;
        }

        $users = DB::table('security_users')
            ->select([
                'id',
                'first_name',
                'middle_name',
                'third_name',
                'last_name',
                'email',
                'mobile_number',
            ])
            ->whereIn('id', array_unique($userIds))
            ->where('status', 1)
            ->get();

        return $this->getContactsFromUsers($users, $contactList);
    }
}
