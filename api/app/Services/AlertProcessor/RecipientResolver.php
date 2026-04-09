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
    /**
     * POCOR-9509: Get contact list for users with specific security roles
     *
     * If institutionId is provided, only users associated with that institution are included.
     * Users can be associated directly (institution.security_group_id) or indirectly
     * (via security_group_institutions table).
     *
     * @param array $securityRoles Array of role objects/arrays with 'id' field
     * @param int|null $institutionId Optional institution ID to filter by
     * @return array Contact list ['email' => [...], 'phone' => [...]]
     */
    public function getRoleAssociatedContactList(array $securityRoles, ?int $institutionId = null): array
    {
        $contactList = ['email' => [], 'phone' => []];
        $allSecurityUserIds = [];

        foreach ($securityRoles as $role) {
            $roleId = is_array($role) ? $role['id'] : $role->id;

            if ($institutionId === null) {
                // Global: all users with this role
                $ids = DB::table('security_group_users')
                    ->select('security_user_id')
                    ->distinct()
                    ->where('security_role_id', $roleId)
                    ->pluck('security_user_id')
                    ->toArray();
            } else {
                // Institution-specific: direct + indirect associations

                // Direct: institution.security_group_id matches
                $directIds = DB::table('security_group_users')
                    ->join('institutions', function ($join) use ($institutionId) {
                        $join->on('institutions.security_group_id', '=', 'security_group_users.security_group_id')
                            ->where('institutions.id', '=', $institutionId);
                    })
                    ->where('security_group_users.security_role_id', $roleId)
                    ->distinct()
                    ->pluck('security_group_users.security_user_id')
                    ->toArray();

                // Indirect: via security_group_institutions table
                $indirectIds = DB::table('security_group_users')
                    ->join('security_group_institutions', function ($join) use ($institutionId) {
                        $join->on('security_group_institutions.security_group_id', '=', 'security_group_users.security_group_id')
                            ->where('security_group_institutions.institution_id', '=', $institutionId);
                    })
                    ->where('security_group_users.security_role_id', $roleId)
                    ->distinct()
                    ->pluck('security_group_users.security_user_id')
                    ->toArray();

                $ids = array_merge($directIds, $indirectIds);
            }

            // Merge and deduplicate
            $allSecurityUserIds = array_unique(array_merge($allSecurityUserIds, $ids));
        }

        if (empty($allSecurityUserIds)) {
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
            ->whereIn('id', $allSecurityUserIds)
            ->where('status', 1) // Only active users
            ->get();

        return $this->getContactsFromUsers($users, $contactList);
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
        if (in_array(9, $securityRoleIds, true)) { // ROLE_GUARDIAN = 9
            $guardians = DB::table('student_guardians')
                ->where('student_id', $studentUserId)
                ->pluck('guardian_id')
                ->toArray();

            if (!empty($guardians)) {
                $recipients = array_merge($recipients, $guardians);
            } else {
                Log::debug("[POCOR-9509] No guardians found for student ID: {$studentUserId}");
            }
        }

        // Include student if ROLE_STUDENT is in the list
        if (in_array(8, $securityRoleIds, true)) { // ROLE_STUDENT = 8
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
