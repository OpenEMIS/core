<?php

namespace Directory\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class DirectoriesTable extends ControllerActionTable
{
    // public $InstitutionStudent;

    // these constants are being used in AdvancedPositionSearchBehavior as well
    // remember to check AdvancedPositionSearchBehavior if these constants are being modified
    const ALL = 0;
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    const STUDENTNOTINSCHOOL = 5;
    const STAFFNOTINSCHOOL = 6;

    private $dashboardQuery;

    /**
     * POCOR-8231
     * Retrieves internal search results for users based on provided parameters.
     *
     * @param array $requestDataParams The parameters for the internal search.
     * @return array The search results.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getUserInternalSearch(array $requestDataParams): array
    {
//        Log::debug(__FUNCTION__);
//        Log::debug(print_r($requestDataParams, true));
        $institutionId = $requestDataParams['institution_id'] ?? null;
        $userTypeId = $requestDataParams['user_type_id'] ?? null;
        $firstName = $requestDataParams['first_name'] ?? null;
        $lastName = $requestDataParams['last_name'] ?? null;
        $openemisNo = $requestDataParams['openemis_no'] ?? null;
        $identityNumber = $requestDataParams['identity_number'] ?? null;
        $dateOfBirth = $requestDataParams['date_of_birth'] ?? null;
        $identityTypeId = $requestDataParams['identity_type_id'] ?? null;
        $nationalityId = $requestDataParams['nationality_id'] ?? null;
        $studentOpenemisNo = $requestDataParams['student_openemis_no'] ?? null; // POCOR-8063
        $guardianTypeId = $requestDataParams['guardian_type_id'] ?? null; // POCOR-8063
        $limit = $requestDataParams['limit'] ?? 10;
        $page = $requestDataParams['page'] ?? 1;
        $userId = $requestDataParams['id'] ?? null;
        // POCOR-8231 unified getting table
        $securityUsersTable = self::getDynamicTableInstance('User.Users');
        $userIdentitiesTable = self::getDynamicTableInstance('User.Identities');
        $userNationalitiesTable = self::getDynamicTableInstance('user_nationalities');
        $gendersTable = self::getDynamicTableInstance('User.Genders');
        // POCOR-8231 unified getting table
        $identityTypesTable = self::getDynamicTableInstance('FieldOption.IdentityTypes');
        $nationalitiesTable = self::getDynamicTableInstance('FieldOption.Nationalities');
        $areaAdministrativesTable = self::getDynamicTableInstance('Area.AreaAdministratives');
        $birthAreaAdministrativesTable = self::getDynamicTableInstance('Area.AreaAdministratives');

        if($userId){
            $openemisNo = null;
            $identityNumber = null;
            $firstName = null;
            $lastName = null;
            $dateOfBirth = null;
        }
        if($openemisNo){
            $identityNumber = null;
            $firstName = null;
            $lastName = null;
            $dateOfBirth = null;
        }
        if($identityNumber){
            $firstName = null;
            $lastName = null;
            $dateOfBirth = null;
        }
        // POCOR-8063: start
        $base_conditions = [];
        if ($studentOpenemisNo && $guardianTypeId) {
            $base_conditions = [$securityUsersTable->aliasField('openemis_no !=') => $studentOpenemisNo];
        }
        if (!$identityNumber) {
            $new_conditions = self::buildUserSearchConditions($securityUsersTable, $userId, $openemisNo, $firstName, $lastName, $dateOfBirth);
            $conditions = array_merge($base_conditions, $new_conditions);
//            Log::debug(print_r($conditions, true));
        }
        // POCOR-8063: end
        $usersSearchResult = [];

        if (!empty($conditions)) {
            $usersSearchResult = self::getUsersSearchArr($securityUsersTable,
                $gendersTable,
                $identityTypesTable,
                $nationalitiesTable,
                $areaAdministrativesTable,
                $userIdentitiesTable,
                $userNationalitiesTable,
                $birthAreaAdministrativesTable,
                $conditions,
                $limit,
                $page);
        }
//        Log::debug(__FUNCTION__);
//        Log::debug(print_r($identityTypeId, true));
//        Log::debug(print_r($identityNumber, true));
        $identityUsersResult = [];

        if ($identityNumber) {
            // POCOR-8063: start
            $new_identityCondition = self::getUserSearchIdentityCondition($identityTypeId,
                $identityNumber,
                $nationalityId,
                $userIdentitiesTable);
            $identityCondition = array_merge($base_conditions, $new_identityCondition);
//            Log::debug(print_r($conditions, true));
            // POCOR-8063: end
            $identityUsersResult = self::getUsersSearchWithIdentityArr($securityUsersTable,
                $gendersTable,
                $identityTypesTable,
                $nationalitiesTable,
                $areaAdministrativesTable,
                $userIdentitiesTable,
                $identityCondition,
                $birthAreaAdministrativesTable,
                $limit,
                $page);
        }

        if (!empty($identityUsersResult)) {
            $usersSearchResult = $identityUsersResult;
        }

        $institutionsTable = self::getDynamicTableInstance('Institution.Institutions');

        $institutionStaffTable = self::getDynamicTableInstance('Institution.Staff');

        $userInternalSearchResult = [];
        foreach ($usersSearchResult as $securityUser) {
            $userInternalSearchResult[] = self::buildUserInternalSearchResult(
                $securityUser,
                $institutionId,
                $userTypeId,
                $institutionsTable,
                $institutionStaffTable);
        }
//        Log::debug(__FUNCTION__);
//        Log::debug(print_r($userInternalSearchResult, true));

        return ['data' => $userInternalSearchResult, 'total' => count($userInternalSearchResult)];
    }

    private static function buildUserSearchConditions($securityUsersTable, $userId, $openemisNo, $firstName, $lastName, $dateOfBirth): array
    {
        $conditions = [];

        if (!empty($userId)) {
            $conditions[$securityUsersTable->aliasField('id')] = $userId;
        } elseif (!empty($openemisNo)) {
            $conditions[$securityUsersTable->aliasField('openemis_no')] = $openemisNo;
        } else {
            if (!empty($firstName)) {
                $conditions[$securityUsersTable->aliasField('first_name') . ' LIKE'] = $firstName . '%';
            }
            if (!empty($lastName)) {
                $conditions[$securityUsersTable->aliasField('last_name') . ' LIKE'] = $lastName . '%';
            }
            if (!empty($dateOfBirth)) {
                $conditions[$securityUsersTable->aliasField('date_of_birth')] = date_create($dateOfBirth)->format('Y-m-d');
            }
        }

        return $conditions;
    }

    /**
     * POCOR-8231
     * Gets the search results for users based on provided conditions.
     *
     * @param \Cake\ORM\Table $securityUsers The security users table instance.
     * @param \Cake\ORM\Table $genders The genders table instance.
     * @param \Cake\ORM\Table $mainIdentityTypes The main identity types table instance.
     * @param \Cake\ORM\Table $mainNationalities The main nationalities table instance.
     * @param \Cake\ORM\Table $areaAdministratives The area administratives table instance.
     * @param \Cake\ORM\Table $userIdentities The user identities table instance.
     * @param \Cake\ORM\Table $userNationalities The user identities table instance.
     * @param \Cake\ORM\Table $birthAreaAdministratives The birth area administratives table instance.
     * @param array $conditions The search conditions.
     * @param int $limit The limit for the search results.
     * @param int $page The page number for the search results.
     * @return array The search results.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getUsersSearchArr(Table $securityUsers,
                                             Table $genders,
                                             Table $mainIdentityTypes,
                                             Table $mainNationalities,
                                             Table $areaAdministratives,
                                             Table $userIdentities,
                                             Table $userNationalities,
                                             Table $birthAreaAdministratives,
                                             array $conditions, int $limit, int $page): array
    {
        $securityUsersResult = $securityUsers
            ->find()
            ->select([
                'id' => $securityUsers->aliasField('id'),
                'username' => $securityUsers->aliasField('username'),
                'password' => $securityUsers->aliasField('password'),
                'openemis_no' => $securityUsers->aliasField('openemis_no'),
                'first_name' => $securityUsers->aliasField('first_name'),
                'middle_name' => $securityUsers->aliasField('middle_name'),
                'third_name' => $securityUsers->aliasField('third_name'),
                'last_name' => $securityUsers->aliasField('last_name'),
                'preferred_name' => $securityUsers->aliasField('preferred_name'),
                'email' => $securityUsers->aliasField('email'),
                'mobile_number' => $securityUsers->aliasField('mobile_number'), // POCOR-9011
                'address' => $securityUsers->aliasField('address'),
                'postal_code' => $securityUsers->aliasField('postal_code'),
                'date_of_death' => $securityUsers->aliasField('date_of_death'),
                'external_reference' => $securityUsers->aliasField('external_reference'),
                'last_login' => $securityUsers->aliasField('last_login'),
                'photo_name' => $securityUsers->aliasField('photo_name'),
                'photo_content' => $securityUsers->aliasField('photo_content'),
                'preferred_language' => $securityUsers->aliasField('preferred_language'),
                'address_area_id' => $securityUsers->aliasField('address_area_id'),
                'birthplace_area_id' => $securityUsers->aliasField('birthplace_area_id'),
                'gender_id' => $securityUsers->aliasField('gender_id'),
                'date_of_birth' => $securityUsers->aliasField('date_of_birth'),
                'nationality_id' => $securityUsers->aliasField('nationality_id'),
                'identity_number' => $securityUsers->aliasField('identity_number'),
                'super_admin' => $securityUsers->aliasField('super_admin'),
                'status' => $securityUsers->aliasField('status'),
                'is_student' => $securityUsers->aliasField('is_student'),
                'is_staff' => $securityUsers->aliasField('is_staff'),
                'is_guardian' => $securityUsers->aliasField('is_guardian'),
                'Genders_id' => $genders->aliasField('id'),
                'Genders_name' => $genders->aliasField('name'),
                'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                'MainNationalities_id' => $mainNationalities->aliasField('id'),
                'MainNationalities_name' => $mainNationalities->aliasField('name'),
                'area_name' => $areaAdministratives->aliasField('name'),
                'area_code' => $areaAdministratives->aliasField('code'),
                'birth_area_name' => 'birthAreaAdministratives.name',
                'birth_area_code' => 'birthAreaAdministratives.code',
                'MainIdentityTypes_number' => $userIdentities->aliasField('number'),
            ])
            ->leftJoin([$userIdentities->getAlias() => $userIdentities->getTable()], [
                $userIdentities->aliasField('security_user_id') . ' = ' . $securityUsers->aliasField('id')
            ])
            ->leftJoin([$userNationalities->getAlias() => $userNationalities->getTable()], [
                $userNationalities->aliasField('security_user_id') . ' = ' . $securityUsers->aliasField('id')
            ])
            ->leftJoin([$genders->getAlias() => $genders->getTable()], [
                $genders->aliasField('id') . ' = ' . $securityUsers->aliasField('gender_id')
            ])
            ->leftJoin([$mainIdentityTypes->getAlias() => $mainIdentityTypes->getTable()], [
                $mainIdentityTypes->aliasField('id') . ' = ' . $userIdentities->aliasField('identity_type_id')
            ])
            ->leftJoin([$mainNationalities->getAlias() => $mainNationalities->getTable()], [
                $mainNationalities->aliasField('id') . ' = ' . $userNationalities->aliasField('nationality_id'),
                $userNationalities->aliasField('preferred = 1')
            ])
            ->leftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable()], [
                $areaAdministratives->aliasField('id') . ' = ' . $securityUsers->aliasField('address_area_id')
            ])
            ->leftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->getTable()], [
                'birthAreaAdministratives.id = ' . $securityUsers->aliasField('birthplace_area_id')
            ])
            ->where([$securityUsers->aliasField('super_admin') . ' <> ' => 1, $conditions])
            ->group([$securityUsers->aliasField('id')])
            ->disableHydration()
            ->limit($limit)
            ->page($page);
        // POCOR-8231 removed redundant debug
        // Log::debug($securityUsersResult->sql());
        $securityUsersResult = $securityUsersResult ? (is_array($securityUsersResult) ? $securityUsersResult : $securityUsersResult->toArray()) : [];
        return $securityUsersResult;

    }

    /**
     * POCOR-8231
     * Gets the identity search conditions for users based on provided parameters.
     *
     * @param int|null $identityTypeId The identity type ID.
     * @param string|null $identityNumber The identity number.
     * @param int|null $nationalityId The nationality ID.
     * @param \Cake\ORM\Table $userIdentities The user identities table instance.
     * @return array The identity search conditions.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getUserSearchIdentityCondition(?int $identityTypeId, ?string $identityNumber, ?int $nationalityId, Table $userIdentities): array
    {

        $identityCondition = [];
        if ($identityTypeId && $identityNumber && $nationalityId) {
            $identityCondition[$userIdentities->aliasField('identity_type_id')] = $identityTypeId;
            $identityCondition[$userIdentities->aliasField('nationality_id')] = $nationalityId;
            $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
        } elseif ($identityTypeId && $identityNumber) {
            $identityCondition[$userIdentities->aliasField('identity_type_id')] = $identityTypeId;
            $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
        } elseif ($identityNumber) {
            $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
        }
        return $identityCondition;
    }

    /**
     * POCOR-8231
     * Gets the search results for users with identity based on provided conditions.
     *
     * @param \Cake\ORM\Table $securityUsers The security users table instance.
     * @param \Cake\ORM\Table $genders The genders table instance.
     * @param \Cake\ORM\Table $mainIdentityTypes The main identity types table instance.
     * @param \Cake\ORM\Table $mainNationalities The main nationalities table instance.
     * @param \Cake\ORM\Table $areaAdministratives The area administratives table instance.
     * @param \Cake\ORM\Table $userIdentities The user identities table instance.
     * @param array $identityCondition The identity search conditions.
     * @param \Cake\ORM\Table $birthAreaAdministratives The birth area administratives table instance.
     * @param int $limit The limit for the search results.
     * @param int $page The page number for the search results.
     * @return array The search results.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getUsersSearchWithIdentityArr(Table $securityUsers, Table $genders, Table $mainIdentityTypes, Table $mainNationalities, Table $areaAdministratives, Table $userIdentities, array $identityCondition, Table $birthAreaAdministratives, int $limit, int $page): array
    {
        $userNationalities = self::getDynamicTableInstance('user_nationalities'); //POCOR-8776

        $identityUsersResult = $securityUsers
            ->find()
            ->select([
                'id' => $securityUsers->aliasField('id'),
                'username' => $securityUsers->aliasField('username'),
                'password' => $securityUsers->aliasField('password'),
                'openemis_no' => $securityUsers->aliasField('openemis_no'),
                'first_name' => $securityUsers->aliasField('first_name'),
                'middle_name' => $securityUsers->aliasField('middle_name'),
                'third_name' => $securityUsers->aliasField('third_name'),
                'last_name' => $securityUsers->aliasField('last_name'),
                'preferred_name' => $securityUsers->aliasField('preferred_name'),
                'email' => $securityUsers->aliasField('email'),
                'mobile_number' => $securityUsers->aliasField('mobile_number'), // POCOR-9011
                'address' => $securityUsers->aliasField('address'),
                'postal_code' => $securityUsers->aliasField('postal_code'),
                'date_of_death' => $securityUsers->aliasField('date_of_death'),
                'external_reference' => $securityUsers->aliasField('external_reference'),
                'last_login' => $securityUsers->aliasField('last_login'),
                'photo_name' => $securityUsers->aliasField('photo_name'),
                'photo_content' => $securityUsers->aliasField('photo_content'),
                'preferred_language' => $securityUsers->aliasField('preferred_language'),
                'address_area_id' => $securityUsers->aliasField('address_area_id'),
                'birthplace_area_id' => $securityUsers->aliasField('birthplace_area_id'),
                'gender_id' => $securityUsers->aliasField('gender_id'),
                'date_of_birth' => $securityUsers->aliasField('date_of_birth'),
                'nationality_id' => $securityUsers->aliasField('nationality_id'),
                'identity_number' => $securityUsers->aliasField('identity_number'),
                'super_admin' => $securityUsers->aliasField('super_admin'),
                'status' => $securityUsers->aliasField('status'),
                'is_student' => $securityUsers->aliasField('is_student'),
                'is_staff' => $securityUsers->aliasField('is_staff'),
                'is_guardian' => $securityUsers->aliasField('is_guardian'),
                'Genders_id' => $genders->aliasField('id'),
                'Genders_name' => $genders->aliasField('name'),
                'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                'MainNationalities_id' => $mainNationalities->aliasField('id'),
                'MainNationalities_name' => $mainNationalities->aliasField('name'),
                'area_name' => $areaAdministratives->aliasField('name'),
                'area_code' => $areaAdministratives->aliasField('code'),
                'birth_area_name' => 'birthAreaAdministratives.name',
                'birth_area_code' => 'birthAreaAdministratives.code',
                'MainIdentityTypes_number' => $userIdentities->aliasField('number'),
            ])
            ->innerJoin([$userIdentities->getAlias() => $userIdentities->getTable()], [
                $userIdentities->aliasField('security_user_id') . ' = ' . $securityUsers->aliasField('id'),
                $identityCondition
            ])
            ->leftJoin([$genders->getAlias() => $genders->getTable()], [
                $genders->aliasField('id') . ' = ' . $securityUsers->aliasField('gender_id')
            ])
            ->leftJoin([$mainIdentityTypes->getAlias() => $mainIdentityTypes->getTable()], [
                $mainIdentityTypes->aliasField('id') . ' = ' . $userIdentities->aliasField('identity_type_id')
            ])
            ->leftJoin([$userNationalities->getAlias() => $userNationalities->getTable()], [ //POCOR-8776 start
                $userNationalities->aliasField('security_user_id') . ' = ' . $securityUsers->aliasField('id')
            ])
            ->leftJoin([$mainNationalities->getAlias() => $mainNationalities->getTable()], [
                $mainNationalities->aliasField('id') . ' = ' . $userNationalities->aliasField('nationality_id'),
                $userNationalities->aliasField('preferred = 1') // //POCOR-8776 end
            ])
            ->leftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable()], [
                $areaAdministratives->aliasField('id') . ' = ' . $securityUsers->aliasField('address_area_id')
            ])
            ->leftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->getTable()], [
                'birthAreaAdministratives.id = ' . $securityUsers->aliasField('birthplace_area_id')
            ])
            ->where([$securityUsers->aliasField('super_admin') . ' <> ' => 1, $identityCondition])
            ->group([$securityUsers->aliasField('id')])
            ->disableHydration()
            ->limit($limit)
            ->page($page);

        $identityUsersResult = $identityUsersResult ? (is_array($identityUsersResult) ? $identityUsersResult : $identityUsersResult->toArray()) : [];

        return $identityUsersResult;
    }

    private static function buildUserInternalSearchResult($securityUser,
                                                          $institutionId,
                                                          $userTypeId,
                                                          $institutionsTable,
                                                          $institutionStaffTable): array
    {
        // POCOR-8231 unified table get
        $nationalitiesTable = self::getDynamicTableInstance('FieldOption.Nationalities');
        $identityTypesTable = self::getDynamicTableInstance('FieldOption.IdentityTypes');
        $specialNeedsTable = self::getDynamicTableInstance('SpecialNeeds.SpecialNeedsAssessments');

        if (is_resource($securityUser['photo_content'])) {
            // Read the resource content
            $content = stream_get_contents($securityUser['photo_content']);
            // Encode the content to base64
            $securityUser['photo_content'] = base64_encode($content);
        }

        $hasSpecialNeeds = $specialNeedsTable->find()
                ->where(['security_user_id' => $securityUser['id']])
                ->count() == 1;

        $firstName = $securityUser['first_name'] ?? '';
        $middleName = $securityUser['middle_name'] ?? '';
        $thirdName = $securityUser['third_name'] ?? '';
        $lastName = $securityUser['last_name'] ?? '';
        $nameParts = array_filter([$firstName, $middleName, $thirdName, $lastName]);
        $name = implode(' ', $nameParts);
        $accountTypes = [];
        if (!empty($securityUser['is_student'])) {
            $accountTypes[] = 'Student';
        }
        if (!empty($securityUser['is_staff'])) {
            $accountTypes[] = 'Staff';
        }
        if (!empty($securityUser['is_guardian'])) {
            $accountTypes[] = 'Guardian';
        }
        $account_type = !empty($accountTypes) ? implode(', ', $accountTypes) : 'Others';
        $contactData = self::getContactData($securityUser['id']);
        // POCOR-8231 for photo

        $userInternalSearchResult = [
            'id' => $securityUser['id'],
            'username' => $securityUser['username'],
            'openemis_no' => $securityUser['openemis_no'],
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'third_name' => $thirdName,
            'last_name' => $lastName,
            'name' => $name,
            'date_of_birth' => $securityUser['date_of_birth']->format('Y-m-d'),
            'gender_id' => $securityUser['gender_id'],
            'gender' => $securityUser['Genders_name'] ? __($securityUser['Genders_name']) : null,
            'gender_name' => $securityUser['Genders_name'] ? __($securityUser['Genders_name']) : null,
            'nationality' => $securityUser['MainNationalities_name'] ? __($securityUser['MainNationalities_name']) : null,
            'nationality_id' => $securityUser['MainNationalities_id'] ? intval($securityUser['MainNationalities_id']) : null,
            'identity_type' => $securityUser['MainIdentityTypes_name'] ? __($securityUser['MainIdentityTypes_name']) : null,
            'identity_type_id' => $securityUser['MainIdentityTypes_id'] ? intval($securityUser['MainIdentityTypes_id']) : null,
            'identity_number' => $securityUser['MainIdentityTypes_number'] ? __($securityUser['MainIdentityTypes_number']) : null,
            'has_special_needs' => $hasSpecialNeeds,
            'contact_data' => $contactData,
            'contact_type_id' => $contactData['contact_type_id'],
            'contact_type_name' => $contactData['contact_type_name'],
            'contact_value' => $contactData['contact_value'],
            'account_type' => $account_type,
            // POCOR-8231 added missing data
            'address_area_id' => $securityUser['address_area_id'],
            'area_name' => $securityUser['area_name'],
            'area_code' => $securityUser['area_code'],
            'birthplace_area_id' => $securityUser['birthplace_area_id'],
            'birth_area_name' => $securityUser['birth_area_name'],
            'birth_area_code' => $securityUser['birth_area_code'],
            'address' => $securityUser['address'],
            'postal_code' => $securityUser['postal_code'],
            'photo_name' => $securityUser['photo_name'],
            'photo_content' => $securityUser['photo_content'],
            'email' => $securityUser['email'], // POCOR-9011
            'mobile_number' => $securityUser['mobile_number'], // POCOR-9011
            // Add other fields as needed
        ];

        // Add institution details and other logic based on userTypeId
        if ($userTypeId == self::STUDENT) {
            $userInternalSearchResult = array_merge($userInternalSearchResult, self::getStudentDetails($securityUser['id'], $institutionId));
        } elseif ($userTypeId == self::STAFF) {
            $userInternalSearchResult = array_merge($userInternalSearchResult, self::getStaffDetails($securityUser['id'], $institutionId, $institutionStaffTable, $institutionsTable));
        }

        return $userInternalSearchResult;
    }

    /**
     * Retrieves the contact data for a user. POCOR-8231
     *
     * @param int $userId
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getContactData(int $userId): array
    {
        $userContacts = self::getDynamicTableInstance('user_contacts');
        $contactTypes = self::getDynamicTableInstance('contact_types');
        $contactOptions = self::getDynamicTableInstance('contact_options');

        $userContactsData = $userContacts
            ->find()
            ->select([
                'contact_value' => $userContacts->aliasField('value'),
                'contact_type_id' => $userContacts->aliasField('contact_type_id'),
                'contact_type_name' => $contactTypes->aliasField('name'),
                'contact_option_name' => $contactOptions->aliasField('name'),
            ])
            ->innerJoin(
                [$contactTypes->getAlias() => $contactTypes->getTable()],
                [$contactTypes->aliasField('id = ') . $userContacts->aliasField('contact_type_id')]
            )
            ->innerJoin(
                [$contactOptions->getAlias() => $contactOptions->getTable()],
                [$contactOptions->aliasField('id = ') . $contactTypes->aliasField('contact_option_id')]
            )
            ->where([
                $userContacts->aliasField('security_user_id = ') . $userId,
                $userContacts->aliasField('preferred = ') . 1
            ])
            ->first();

        return $userContactsData ? $userContactsData->toArray() : [
            'contact_value' => null,
            'contact_type_id' => null,
            'contact_type' => null,
        ];
    }

    /**
     * POCOR-8231
     * Retrieves student details based on security user ID and institution ID.
     *
     * @param int $securityUserId The security user ID.
     * @param int|null $institutionId The institution ID.
     * @return array The student details.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getStudentDetails(int $securityUserId, ?int $institutionId): array
    {

        $student = self::getStudent($securityUserId);
        if (!$student) {
            return [];
        }
        $isSameSchool = $isDiffSchool = 0;
        if ($student['institution_id']) {
            $isSameSchool = $student['institution_id'] == $institutionId ? 1 : 0;
            $isDiffSchool = !$isSameSchool ? 1 : 0;
        }
        $pendingTransfer = self::getPendingTransfer($securityUserId);
        $pendingWithdraw = self::getPendingWithdraw($securityUserId);
// POCOR-8231 custom data not needed as it is got later
//        $customDataArray = self::getStudentCustomData($securityUserId);

        return [
            'institution_id' => $student['institution_id'],
            'institution_name' => $student['institution_name'],
            'institution_code' => $student['institution_code'],
            'current_enrol_institution_id' => $student['institution_id'],
            'current_enrol_institution_name' => $student['institution_name'],
            'current_enrol_institution_code' => $student['institution_code'],
            'academic_period_id' => $student['academic_period_id'],
            'current_enrol_academic_period_id' => $student['academic_period_id'],
            'academic_period_year' => $student['academic_period_year'],
            'current_enrol_academic_period_year' => $student['academic_period_year'],
            'education_grade_id' => $student['education_grade_id'],
            'current_enrol_education_grade_id' => $student['education_grade_id'],
            'is_same_school' => $isSameSchool,
            'is_diff_school' => $isDiffSchool,
            'is_pending_transfer' => $pendingTransfer ? 1 : 0,
            'pending_transfer_institution_name' => $pendingTransfer['institution_name'] ?? '',
            'pending_transfer_institution_code' => $pendingTransfer['institution_code'] ?? '',
            'pending_transfer_prev_institution_name' => $pendingTransfer['previous_institution_name'] ?? '',
            'pending_transfer_prev_institution_code' => $pendingTransfer['previous_institution_code'] ?? '',
            'is_pending_withdraw' => $pendingWithdraw ? 1 : 0,
            'pending_withdraw_institution_name' => $pendingWithdraw['institution_name'] ?? '',
            'pending_withdraw_institution_code' => $pendingWithdraw['institution_code'] ?? '',
// POCOR-8231 removed as it is got later
//            'custom_data' => $customDataArray,
        ];
    }

    /**
     * Retrieves student details for a given security user ID. POCOR-8231
     *
     * @param int $securityUserId
     * @return array
     * @throws \Exception If there is an error retrieving the student data
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getStudent(int $securityUserId): array
    {
        $institutionStudentsTable = self::getDynamicTableInstance('Institution.InstitutionStudents');
        $studentStatusesTable = self::getDynamicTableInstance('Student.StudentStatuses');
        $institutionsTable = self::getDynamicTableInstance('Institution.Institutions');

        $statuses = $studentStatusesTable->findCodeList();
        $studentStatusCurrent = $statuses['CURRENT'];

        try {
            $studentQuery = $institutionStudentsTable
                ->find()
                ->select([
                    'institution_id' => $institutionStudentsTable->aliasField('institution_id'),
                    'student_id' => $institutionStudentsTable->aliasField('student_id'),
                    'student_status_id' => $institutionStudentsTable->aliasField('student_status_id'),
                    'institution_name' => $institutionsTable->aliasField('name'),
                    'institution_code' => $institutionsTable->aliasField('code'),
                    'academic_period_id' => $institutionStudentsTable->aliasField('academic_period_id'),
                    'academic_period_year' => $institutionStudentsTable->aliasField('start_year'),
                    'education_grade_id' => $institutionStudentsTable->aliasField('education_grade_id')
                ])
                ->innerJoin([$institutionsTable->getAlias() => $institutionsTable->getTable()], [
                    $institutionsTable->aliasField('id') . ' = ' . $institutionStudentsTable->aliasField('institution_id')
                ])
                ->where([
                    $institutionStudentsTable->aliasField('student_id') => $securityUserId,
                    $institutionStudentsTable->aliasField('student_status_id') => $studentStatusCurrent
                ])
                ->disableHydration();

            $studentEntity = $studentQuery->first();
            $result = $studentEntity ? (is_array($studentEntity) ? $studentEntity : $studentEntity->toArray()) : [];
        } catch (\Exception $exception) {
// POCOR-8231 to find where is the error
            Log::debug(__FUNCTION__);
            Log::debug('Error: ' . $exception->getMessage());
            $result = [];
        }

        return $result;
    }

    /**
     * POCOR-8231
     * Gets a dynamic table instance with all associations.
     *
     * @param string $tableName The name of the table.
     * @return \Cake\ORM\Table The table instance.
     * @throws \Exception If the table instance cannot be retrieved.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            // POCOR-8231 to find where is the error
            Log::debug(__FUNCTION__);
            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    /**
     * Retrieves the pending transfer details for a given security user ID. POCOR-8231
     *
     * @param int $securityUserId
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getPendingTransfer(int $securityUserId): array
    {
        $institutionsTable = self::getDynamicTableInstance('Institution.Institutions');
        $prevInstitutionsTable = self::getDynamicTableInstance('Institution.Institutions');
        $transfersTable = self::getDynamicTableInstance('Institution.InstitutionStudentTransfers');

        $doneStatus = $transfersTable::DONE;

        try {
            $transfersQuery = $transfersTable
                ->find()
                ->select([
                    'id' => $transfersTable->aliasField('id'),
                    'institution_id' => $transfersTable->aliasField('institution_id'),
                    'previous_institution_id' => $transfersTable->aliasField('previous_institution_id'),
                    'student_id' => $transfersTable->aliasField('student_id'),
                    'institution_name' => $institutionsTable->aliasField('name'),
                    'institution_code' => $institutionsTable->aliasField('code'),
                    'previous_institution_name' => $prevInstitutionsTable->aliasField('name'),
                    'previous_institution_code' => $prevInstitutionsTable->aliasField('code'),
                    'academic_period_id' => $transfersTable->aliasField('academic_period_id'),
                ])
                ->innerJoin([$institutionsTable->getAlias() => $institutionsTable->getTable()], [
                    $institutionsTable->aliasField('id') . ' = ' . $transfersTable->aliasField('institution_id')
                ])
                ->innerJoin([$prevInstitutionsTable->getAlias() => $prevInstitutionsTable->getTable()], [
                    $prevInstitutionsTable->aliasField('id') . ' = ' . $transfersTable->aliasField('previous_institution_id')
                ])
                ->matching('Statuses', function ($q) use ($doneStatus) {
                    return $q->where([
                        'Statuses.category <>' => $doneStatus,
                    ]);
                })
                ->where([
                    $transfersTable->aliasField('student_id') => $securityUserId,
                ]);

            $transferEntity = $transfersQuery->first();
            $result = $transferEntity ? $transferEntity->toArray() : [];
        } catch (\Exception $exception) {
            // POCOR-8231 to find where is the error
            Log::debug(__FUNCTION__);
            Log::debug('Error: ' . $exception->getMessage());
            $result = [];
        }

        return $result;
    }

    /**
     * Retrieves the pending withdraw details for a given security user ID. POCOR-8231
     *
     * @param int $securityUserId
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getPendingWithdraw(int $securityUserId): array
    {
        $institutionsTable = self::getDynamicTableInstance('Institution.Institutions');
        $withdrawsTable = self::getDynamicTableInstance('Institution.StudentWithdraw');

        $doneStatus = $withdrawsTable::DONE;

        try {
            $withdrawsQuery = $withdrawsTable
                ->find()
                ->select([
                    'institution_id' => $withdrawsTable->aliasField('institution_id'),
                    'student_id' => $withdrawsTable->aliasField('student_id'),
                    'institution_name' => $institutionsTable->aliasField('name'),
                    'institution_code' => $institutionsTable->aliasField('code'),
                    'academic_period_id' => $withdrawsTable->aliasField('academic_period_id'),
                ])
                ->innerJoin([$institutionsTable->getAlias() => $institutionsTable->getTable()], [
                    $institutionsTable->aliasField('id') . ' = ' . $withdrawsTable->aliasField('institution_id')
                ])
                ->matching('Statuses', function ($q) use ($doneStatus) {
                    return $q->where([
                        'Statuses.category <>' => $doneStatus,
                    ]);
                })
                ->where([
                    $withdrawsTable->aliasField('student_id') => $securityUserId,
                ]);

            $withdrawEntity = $withdrawsQuery->first();
            $result = $withdrawEntity ? $withdrawEntity->toArray() : [];
        } catch (\Exception $exception) {
            // POCOR-8231 to find where is the error
            Log::debug(__FUNCTION__);
            Log::debug('Error: ' . $exception->getMessage());
            $result = [];
        }

        return $result;
    }

    /**
     * Retrieves the custom data for a student. POCOR-8231
     *
     * @param int $studentId
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getStudentCustomData(int $studentId): array
    {
        $studentCustomFieldValuesTable = self::getDynamicTableInstance('student_custom_field_values');
        $studentCustomFieldOptionsTable = self::getDynamicTableInstance('student_custom_field_options');

        $studentCustomData = $studentCustomFieldValuesTable->find()
            ->select([
                'id' => $studentCustomFieldValuesTable->aliasField('id'),
                'custom_id' => 'studentCustomField.id',
                'student_id' => $studentCustomFieldValuesTable->aliasField('student_id'),
                'student_custom_field_id' => $studentCustomFieldValuesTable->aliasField('student_custom_field_id'),
                'text_value' => $studentCustomFieldValuesTable->aliasField('text_value'),
                'number_value' => $studentCustomFieldValuesTable->aliasField('number_value'),
                'decimal_value' => $studentCustomFieldValuesTable->aliasField('decimal_value'),
                'textarea_value' => $studentCustomFieldValuesTable->aliasField('textarea_value'),
                'date_value' => $studentCustomFieldValuesTable->aliasField('date_value'),
                'time_value' => $studentCustomFieldValuesTable->aliasField('time_value'),
                'option_value_text' => $studentCustomFieldOptionsTable->aliasField('name'),
                'name' => 'studentCustomField.name',
                'field_type' => 'studentCustomField.field_type',
            ])
            ->leftJoin(
                ['studentCustomField' => 'student_custom_fields'],
                [
                    'studentCustomField.id = ' . $studentCustomFieldValuesTable->aliasField('student_custom_field_id')
                ]
            )
            ->leftJoin(
                [$studentCustomFieldOptionsTable->getAlias() => $studentCustomFieldOptionsTable->getTable()],
                [
                    $studentCustomFieldOptionsTable->aliasField('student_custom_field_id') . ' = ' . $studentCustomFieldValuesTable->aliasField('student_custom_field_id'),
                    $studentCustomFieldOptionsTable->aliasField('id') . ' = ' . $studentCustomFieldValuesTable->aliasField('number_value')
                ]
            )
            ->where([
                $studentCustomFieldValuesTable->aliasField('student_id') => $studentId,
            ])
            ->toArray();

        $customField = [];
        foreach ($studentCustomData as $data) {
            $fieldType = $data['field_type'];
            $customFieldData = [
                'id' => $data['custom_id'],
                'name' => $data['name']
            ];

            switch ($fieldType) {
                case 'TEXT':
                    $customFieldData['text_value'] = $data['text_value'];
                    break;
                case 'CHECKBOX':
                    $customFieldData['checkbox_value'] = $data['option_value_text'];
                    break;
                case 'NUMBER':
                    $customFieldData['number_value'] = $data['number_value'];
                    break;
                case 'DECIMAL':
                    $customFieldData['decimal_value'] = $data['decimal_value'];
                    break;
                case 'TEXTAREA':
                    $customFieldData['textarea_value'] = $data['textarea_value'];
                    break;
                case 'DROPDOWN':
                    $customFieldData['dropdown_value'] = $data['option_value_text'];
                    break;
                case 'DATE':
                    $customFieldData['date_value'] = date('Y-m-d', strtotime($data['date_value']));
                    break;
                case 'TIME':
                    $customFieldData['time_value'] = date('h:i A', strtotime($data['time_value']));
                    break;
                case 'COORDINATES':
                    $customFieldData['coordinate_value'] = $data['text_value'];
                    break;
            }
            $customField[] = $customFieldData;
        }

        return $customField;
    }

    /**
     * POCOR-8231
     * Retrieves staff details based on security user ID and institution ID.
     *
     * @param int $securityUserId The security user ID.
     * @param int|null $institutionId The institution ID.
     * @param \Cake\ORM\Table $institutionStaffTable The institution staff table instance.
     * @param \Cake\ORM\Table $institutionsTable The institutions table instance.
     * @return array The staff details.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getStaffDetails(int $securityUserId, ?int $institutionId, $institutionStaffTable, $institutionsTable): array
    {
        // POCOR-8231 unified table get
        $staffStatusesTable = self::getDynamicTableInstance('Staff.StaffStatuses');
        $assignedStatus = $staffStatusesTable->getIdByCode('ASSIGNED');
        // POCOR-8231 no search for institution if it is not given
        $where = [
            $institutionStaffTable->aliasField('staff_id') => $securityUserId,
            $institutionStaffTable->aliasField('staff_status_id') => $assignedStatus
        ];
        $institutionStaffTbl = $institutionStaffTable
            ->find()
            ->select([
                'institution_id' => $institutionStaffTable->aliasField('institution_id'),
                'staff_id' => $institutionStaffTable->aliasField('staff_id'),
                'institution_position_id' => $institutionStaffTable->aliasField('institution_position_id'),
                'staff_status_id' => $institutionStaffTable->aliasField('staff_status_id'),
                'institution_name' => $institutionsTable->aliasField('name'),
                'institution_code' => $institutionsTable->aliasField('code')
            ])
            ->InnerJoin([$institutionsTable->getAlias() => $institutionsTable->getTable()], [
                $institutionsTable->aliasField('id') . ' = ' . $institutionStaffTable->aliasField('institution_id')
            ])
            ->where($where)
            ->toArray();
        if($institutionId){
            $thisInstitution = $institutionsTable->get($institutionId);
            $where[$institutionStaffTable->aliasField('institution_id')] = $institutionId;
        }
        $sameInstitutionStaffTbl = $institutionStaffTable
            ->find()
            ->select([
                'institution_id' => $institutionStaffTable->aliasField('institution_id'),
                'staff_id' => $institutionStaffTable->aliasField('staff_id'),
                'institution_position_id' => $institutionStaffTable->aliasField('institution_position_id'),
                'staff_status_id' => $institutionStaffTable->aliasField('staff_status_id'),
                'institution_name' => $institutionsTable->aliasField('name'),
                'institution_code' => $institutionsTable->aliasField('code')
            ])
            ->InnerJoin([$institutionsTable->getAlias() => $institutionsTable->getTable()], [
                $institutionsTable->aliasField('id') . ' = ' . $institutionStaffTable->aliasField('institution_id')
            ])
            ->where($where)
            ->toArray();

        $isSameSchool = $isDiffSchool = 0;
        if (!empty($sameInstitutionStaffTbl)) {
            $institutionStaffTbl = $sameInstitutionStaffTbl;
        }
        $positionArray = [];
        // POCOR-8231-start
        $isSameSchool = -1;
        $isDiffSchool = -1;
//        Log::debug(print_r($institutionStaffTbl, true));
        foreach ($institutionStaffTbl as $staff) {
            $positionArray[] = $staff->institution_position_id;
            if ($staff->institution_id) {
                if ($isSameSchool == -1) {
                    $isSameSchool = $staff->institution_id == $institutionId ? 1 : 0;
                }
                if ($isDiffSchool == -1) {
                    $isDiffSchool = !$isSameSchool ? 1 : 0;
                }
            }
        }
        // POCOR-8231 end fixed search same-school - other-school
// POCOR-8231 removed custom data here
//        $customDataArray = self::getStaffCustomData($securityUserId);
        // POCOR-8231: start fixed this and that school names and ids for search and add
        $arrResult = [
            'institution_id' => $thisInstitution->id,
            'institution_name' => $thisInstitution->name,
            'institution_code' => $thisInstitution->code,
            'current_enrol_institution_id' => $institutionStaffTbl[0]->institution_id ?? '',
            'current_enrol_institution_name' => $institutionStaffTbl[0]->institution_name ?? '',
            'current_enrol_institution_code' => $institutionStaffTbl[0]->institution_code ?? '',
            'positions' => $positionArray,
            'is_same_school' => $isSameSchool,
            'is_diff_school' => $isDiffSchool,
            // POCOR-8231 removed custom data in search
//            'custom_data' => $customDataArray,
        ];
//        Log::debug(print_r($arrResult,true));
        return $arrResult;
        // POCOR-8231: end
    }

    /**
     * Retrieves the custom data for a staff member. POCOR-8231
     *
     * @param int $staffId
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getStaffCustomData(int $staffId): array
    {
        $staffCustomFieldValuesTable = self::getDynamicTableInstance('staff_custom_field_values');
        $staffCustomFieldOptionsTable = self::getDynamicTableInstance('staff_custom_field_options');

        $staffCustomData = $staffCustomFieldValuesTable->find()
            ->select([
                'id' => $staffCustomFieldValuesTable->aliasField('id'),
                'custom_id' => 'staffCustomField.id',
                'staff_id' => $staffCustomFieldValuesTable->aliasField('staff_id'),
                'staff_custom_field_id' => $staffCustomFieldValuesTable->aliasField('staff_custom_field_id'),
                'text_value' => $staffCustomFieldValuesTable->aliasField('text_value'),
                'number_value' => $staffCustomFieldValuesTable->aliasField('number_value'),
                'decimal_value' => $staffCustomFieldValuesTable->aliasField('decimal_value'),
                'textarea_value' => $staffCustomFieldValuesTable->aliasField('textarea_value'),
                'date_value' => $staffCustomFieldValuesTable->aliasField('date_value'),
                'time_value' => $staffCustomFieldValuesTable->aliasField('time_value'),
                'option_value_text' => $staffCustomFieldOptionsTable->aliasField('name'),
                'name' => 'staffCustomField.name',
                'field_type' => 'staffCustomField.field_type',
            ])
            ->leftJoin(
                ['staffCustomField' => 'staff_custom_fields'],
                [
                    'staffCustomField.id = ' . $staffCustomFieldValuesTable->aliasField('staff_custom_field_id')
                ]
            )
            ->leftJoin(
                [$staffCustomFieldOptionsTable->getAlias() => $staffCustomFieldOptionsTable->getTable()],
                [
                    $staffCustomFieldOptionsTable->aliasField('staff_custom_field_id') . ' = ' . $staffCustomFieldValuesTable->aliasField('staff_custom_field_id'),
                    $staffCustomFieldOptionsTable->aliasField('id') . ' = ' . $staffCustomFieldValuesTable->aliasField('number_value')
                ]
            )
            ->where([
                $staffCustomFieldValuesTable->aliasField('staff_id') => $staffId,
            ])
            ->toArray();

        $customField = [];
        foreach ($staffCustomData as $data) {
            $fieldType = $data['field_type'];
            $customFieldData = [
                'id' => $data['custom_id'],
                'name' => $data['name']
            ];

            switch ($fieldType) {
                case 'TEXT':
                    $customFieldData['text_value'] = $data['text_value'];
                    break;
                case 'CHECKBOX':
                    $customFieldData['checkbox_value'] = $data['option_value_text'];
                    break;
                case 'NUMBER':
                    $customFieldData['number_value'] = $data['number_value'];
                    break;
                case 'DECIMAL':
                    $customFieldData['decimal_value'] = $data['decimal_value'];
                    break;
                case 'TEXTAREA':
                    $customFieldData['textarea_value'] = $data['textarea_value'];
                    break;
                case 'DROPDOWN':
                    $customFieldData['dropdown_value'] = $data['option_value_text'];
                    break;
                case 'DATE':
                    $customFieldData['date_value'] = date('Y-m-d', strtotime($data['date_value']));
                    break;
                case 'TIME':
                    $customFieldData['time_value'] = date('h:i A', strtotime($data['time_value']));
                    break;
                case 'COORDINATES':
                    $customFieldData['coordinate_value'] = $data['text_value'];
                    break;
            }
            $customField[] = $customFieldData;
        }

        return $customField;
    }

    // POCOR-5684

    public function initialize(array $config): void
    {
        // echo "<pre>";print_r($_REQUEST);die;
        $this->setTable('security_users');
        $this->setEntityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $this->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserNationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $this->hasMany('ExaminationCentreRoomsExaminationsInvigilators', ['className' => 'Examination.ExaminationCentreRoomsExaminationsInvigilators', 'foreignKey' => 'invigilator_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', ['className' => 'Examination.ExaminationCentreRoomsExaminationsStudents', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentresExaminationsInvigilators', ['className' => 'Examination.ExaminationCentresExaminationsInvigilators', 'foreignKey' => 'invigilator_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentresExaminationsStudents', ['className' => 'Examination.ExaminationCentresExaminationsStudents', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('HistoricalStaffLeave', ['className' => 'Historical.HistoricalStaffLeave', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionAssociationStaff', ['className' => 'Institution.InstitutionAssociationStaff', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionAssociationStudent', ['className' => 'Student.InstitutionAssociationStudent', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionCases', ['className' => 'Cases.InstitutionCases', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'foreignKey' => 'secondary_staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionCompetencyItemComments', ['className' => 'Institution.InstitutionCompetencyItemComments', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionCompetencyPeriodComments', ['className' => 'Institution.InstitutionCompetencyPeriodComments', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionCompetencyResults', ['className' => 'Institution.InstitutionCompetencyResults', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('CounsellingsCounselor', ['className' => 'Institution.Counsellings', 'foreignKey' => 'counselor_id', 'dependent' => true]);
        $this->hasMany('CounsellingsRequester', ['className' => 'Institution.Counsellings', 'foreignKey' => 'requester_id', 'dependent' => true]);
        $this->hasMany('CounsellingsStudent', ['className' => 'Institution.Counsellings', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionCurricularStaff', ['className' => 'Institution.InstitutionCurricularStaff', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionCurricularStudents', ['className' => 'Institution.InstitutionCurricularStudents', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionMealStudents', ['className' => 'Institution.InstitutionMealStudents', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionOutcomeResults', ['className' => 'Institution.InstitutionOutcomeResults', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionOutcomeSubjectComments', ['className' => 'Institution.InstitutionOutcomeSubjectComments', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionQualityRubrics', ['className' => 'Institution.InstitutionRubrics', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionQualityVisits', ['className' => 'Quality.InstitutionQualityVisits', 'foreignKey' => 'staff_id', 'dependent' => true]);
//        $this->hasMany('InstitutionStaff', ['className' => 'institution_staff', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAppraisalsAssignee', ['className' => 'Institution.Appraisals', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAppraisals', ['className' => 'Institution.Appraisals', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAttendanceActivities', ['className' => 'User.InstitutionStaffAttendanceActivities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAttendances', ['className' => 'Institution.InstitutionStaffAttendances', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffDuties', ['className' => 'Institution.InstitutionStaffDuties', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffLeave', ['className' => 'Institution.StaffLeave', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffReleasesAssignee', ['className' => 'Institution.InstitutionStaffReleases', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffReleases', ['className' => 'Institution.InstitutionStaffReleases', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffShifts', ['className' => 'Institution.InstitutionStaffShifts', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffTransfersAssignee', ['className' => 'Institution.InstitutionStaffTransfers', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffTransfers', ['className' => 'Institution.InstitutionStaffTransfers', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStandardStudentAbsences', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAbsenceDetails', ['className' => 'Student.StudentAbsences', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAbsences', ['className' => 'Institution.StudentAttendanceSummary', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAdmissionAssignee', ['className' => 'Institution.StudentAdmission', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAdmission', ['className' => 'Institution.StudentAdmission', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentRisks', ['className' => 'Student.StudentRisks', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentSurveys', ['className' => 'Student.StudentSurveys', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentTransfersAssignee', ['className' => 'Institution.InstitutionStudentTransfers', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentTransfers', ['className' => 'Institution.InstitutionStudentTransfers', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitRequestsAssignee', ['className' => 'Student.StudentVisitRequests', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitRequestsEvaluator', ['className' => 'Student.StudentVisitRequests', 'foreignKey' => 'evaluator_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitRequestsStudent', ['className' => 'Student.StudentVisitRequests', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitsEvaluator', ['className' => 'Student.StudentVisits', 'foreignKey' => 'evaluator_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitsStudent', ['className' => 'Student.StudentVisits', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentWithdrawAssignee', ['className' => 'Institution.StudentWithdraw', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'foreignKey' => 'student_id', 'dependent' => true]);
//        $this->hasMany('InstitutionStudents', ['className' => 'institution_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentsReportCardsCommentsStaff', ['className' => 'Institution.InstitutionStudentsReportCardsComments', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentsReportCardsCommentsStudent', ['className' => 'Institution.InstitutionStudentsReportCardsComments', 'foreignKey' => 'student_id', 'dependent' => true]);
       // not found
        // $this->hasMany('InstitutionStudentsTmp', ['className' => 'institution_students_tmp', 'foreignKey' => 'student_id', 'dependent' => true]);POCOR-8795
        $this->hasMany('InstitutionSubjectStaff', ['className' => 'Staff.StaffSubjects', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Student.StudentSubjects', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionTripPassengers', ['className' => 'Student.StudentTransport', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionVisitRequestsAssignee', ['className' => 'Quality.VisitRequests', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('MoodleApiCreatedUsers', ['className' => 'MoodleApi.MoodleApiCreatedUsers', 'foreignKey' => 'core_user_id', 'dependent' => true]);
        $this->hasMany('ReportCardEmailProcesses', ['className' => 'ReportCard.ReportCardEmailProcesses', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ReportCardProcesses', ['className' => 'ReportCard.ReportCardProcesses', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'foreignKey' => 'applicant_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'foreignKey' => 'applicant_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplications', ['className' => 'Profile.ScholarshipApplications', 'foreignKey' => 'applicant_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplicationsAssignee', ['className' => 'Profile.ScholarshipApplications', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('ScholarshipFinancialAssistancesModified', ['className' => 'Scholarship.ScholarshipFinancialAssistances', 'foreignKey' => 'modified_user_id', 'dependent' => true]);
        $this->hasMany('ScholarshipFinancialAssistancesCreated', ['className' => 'Scholarship.ScholarshipFinancialAssistances', 'foreignKey' => 'created_user_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientAcademicStandings', ['className' => 'Scholarship.RecipientAcademicStandings', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientActivities', ['className' => 'Scholarship.RecipientActivities', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientCollections', ['className' => 'Scholarship.RecipientCollections', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientDisbursements', ['className' => 'Scholarship.RecipientDisbursements', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientPaymentStructures', ['className' => 'Scholarship.RecipientPaymentStructures', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('SecurityUserPasswordRequests', ['className' => 'User.SecurityUserPasswordRequests', 'foreignKey' => 'user_id', 'dependent' => true]);
        $this->hasMany('StaffCustomFieldValues', ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffCustomTableCells', ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffEmploymentStatuses', ['className' => 'Staff.EmploymentStatuses', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffExtracurriculars', ['className' => 'Staff.Extracurriculars', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffMemberships', ['className' => 'Staff.Memberships', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffPayslips', ['className' => 'Staff.Payslips', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffQualifications', ['className' => 'Staff.Qualifications', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffReportCardEmailProcesses', ['className' => 'ReportCard.StaffReportCardEmailProcesses', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffReportCardProcesses', ['className' => 'ReportCard.StaffReportCardProcesses', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffReportCards', ['className' => 'Institution.StaffReportCards', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffSalaries', ['className' => 'Staff.Salaries', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingApplicationsAssignee', ['className' => 'Institution.StaffTrainingApplications', 'foreignKey' => 'assignee_id', 'dependent' => true]); //need to ask
        $this->hasMany('StaffTrainingApplications', ['className' => 'Institution.StaffTrainingApplications', 'foreignKey' => 'staff_id', 'dependent' => true]); //need to ask
        $this->hasMany('StaffTrainingNeedsAssignee', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingSelfStudies', ['className' => 'Staff.Achievements', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('StaffTrainings', ['className' => 'Staff.StaffTrainings', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StudentCustomFieldValues', ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentCustomTableCells', ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true]);
       // Undefined property `request` association on
        $this->hasMany('StudentExtracurriculars', ['className' => 'Student.Extracurriculars', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentGuardiansGuardian', ['className' => 'Student.StudentGuardians', 'foreignKey' => 'guardian_id', 'dependent' => true]);
        $this->hasMany('StudentGuardians', ['className' => 'Student.StudentGuardians', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentReportCardEmailProcesses', ['className' => 'ReportCard.StudentReportCardEmailProcesses', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentReportCardProcesses', ['className' => 'ReportCard.StudentReportCardProcesses', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentReportCards', ['className' => 'Student.Profiles', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentStatusUpdates', ['className' => 'Institution.StudentStatusUpdates', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionResults', ['className' => 'Training.TrainingSessionResults', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionTraineeResults', ['className' => 'Training.TrainingSessionTraineeResults', 'foreignKey' => 'trainee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionTrainers', ['className' => 'Training.TrainingSessionTrainers', 'foreignKey' => 'trainer_id', 'dependent' => true]);
        $this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionsTrainees', ['className' => 'Training.TrainingSessionsTrainees', 'foreignKey' => 'trainee_id', 'dependent' => true]);
        $this->hasMany('UserActivities', ['className' => 'User.UserActivities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserAttachments', ['className' => 'User.Attachments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserAwards', ['className' => 'User.Awards', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserBankAccounts', ['className' => 'User.BankAccounts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserBodyMasses', ['className' => 'Health.BodyMasses', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserComments', ['className' => 'User.Comments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserContacts', ['className' => 'user_contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserDemographics', ['className' => 'User.Demographics', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserEmployments', ['className' => 'User.UserEmployments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthAllergies', ['className' => 'Health.Allergies', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthConsultations', ['className' => 'Health.Consultations', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthFamilies', ['className' => 'Health.Families', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthHistories', ['className' => 'Health.Histories', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthImmunizations', ['className' => 'Health.Immunizations', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthMedications', ['className' => 'Health.Medications', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthTests', ['className' => 'Health.Tests', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealths', ['className' => 'Health.Healths', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserIdentities', ['className' => 'user_identities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserInsurances', ['className' => 'Health.Insurances', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserLanguages', ['className' => 'User.UserLanguages', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserNationalities', ['className' => 'user_nationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserSpecialNeedsAssessments', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsDevices', ['className' => 'SpecialNeeds.SpecialNeedsDevices', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsPlans', ['className' => 'SpecialNeeds.SpecialNeedsPlans', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsReferrals', ['className' => 'SpecialNeeds.SpecialNeedsReferrals', 'foreignKey' => 'referrer_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsReferrals', ['className' => 'SpecialNeeds.SpecialNeedsReferrals', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsServices', ['className' => 'SpecialNeeds.SpecialNeedsServices', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        // Undefined property `controller`. You have not defined the `control
        $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'Staff.StaffBehaviours', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffLicensesAssignee', ['className' => 'Staff.Licenses', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('StaffLicenses', ['className' => 'Staff.Licenses', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentsReportCards', ['className' => 'Institution.InstitutionStudentsReportCards', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionTextbooks', ['className' => 'Institution.InstitutionTextbooks', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentresExaminationsSubjectsStudents', ['className' => 'Examination.ExaminationCentresExaminationsSubjectsStudents', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ExaminationStudentSubjects', ['className' => 'Examination.ExaminationStudentSubjects', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionAssets', ['className' => 'Institution.InstitutionAssets', 'foreignKey' => 'user_id', 'dependent' => true]);

        $this->addBehavior('User.User');
        $this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
        $this->addBehavior('User.AdvancedIdentitySearch');
        $this->addBehavior('User.AdvancedContactNumberSearch');
        $this->addBehavior('User.AdvancedPositionSearch');
        $this->addBehavior('User.AdvancedSpecificNameTypeSearch');
        $this->addBehavior('User.MoodleCreateUser');
        $this->addBehavior('Directory.Merge');

        //specify order of advanced search fields
        $advancedSearchFieldOrder = [
            'user_type', 'first_name', 'middle_name', 'third_name', 'last_name',
            'openemis_no', 'gender_id', 'contact_number', 'birthplace_area_id', 'address_area_id', 'position',
            'identity_type', 'identity_number'
        ];
        $this->addBehavior('AdvanceSearch', [
            'include' => [
                'openemis_no'
            ],
            'order' => $advancedSearchFieldOrder,
            'showOnLoad' => 1,
            'customFields' => ['user_type']
        ]);

        $this->addBehavior('HighChart', [
            'user_gender' => [
                '_function' => 'getNumberOfUsersByGender'
            ]
        ]);
        //$this->addBehavior('Configuration.Pull'); //comment cakephp4
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportUsers']);
        $this->addBehavior('ControllerAction.Image');

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Directory.Directories.id']);
        $this->toggle('search', true);
        $this->setDeleteStrategy('restrict'); //POCOR-7083
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        // echo "<pre>";
        // print_r( $primary);die;
        if ($primary) {
            $schema = $this->getSchema();
            $fields = $schema->columns();
            foreach ($fields as $key => $field) {
                if ($schema->getColumn($field)['type'] == 'binary') {
                    unset($fields[$key]);
                }
            }
            return $query->select($fields);
        }
    }

    // POCOR-5684
    // public function onGetIdentityNumber(Event $event, Entity $entity)
    // {
    //     // Get user identity number
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['number'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();
    //     return $entity->identity_number = $user_id_data->number;
    // }

    // // POCOR-5684
    // public function onGetIdentityTypeID(Event $event, Entity $entity)
    // {
    //     // Get User Identity Type id
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['identity_type_id'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();

    //     // Get Identity Type Name
    //     $users_id_type = TableRegistry::get('identity_types');
    //     $user_id_name = $users_id_type->find()
    //     ->select(['name'])
    //     ->where([
    //         $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
    //     ])
    //     ->first();
    //     return $entity->identity_type_id = $user_id_name->name;
    // }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';
        $events['AdvanceSearch.onModifyConditions'] = 'onModifyConditions';
        $events['Model.AreaAdministrative.afterDelete'] = 'areaAdminstrativeAfterDelete';
        return $events;
    }

    public function validationNotEmptyNationality(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator->add('nationality');
        return $validator;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ->notEmpty('nationality');
        $BaseUsers = TableRegistry::get('User.Users');
        return $BaseUsers->setUserValidation($validator, $this);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // POCOR-4035 Check when the submit is not save then will not add the validation.
        $submit = isset($data['submit']) ? $data['submit'] : 'save';

        if ($submit == 'save') {
            $nationalityValidation = 'AddByAssociation';
            $identityValidation = 'AddByAssociation';
        } else {
            $nationalityValidation = false;
            $identityValidation = false;
        }

        $options['associated']['Nationalities'] = [
            'validate' => $nationalityValidation
        ];
        $options['associated']['Identities'] = [
            'validate' => $identityValidation
        ];
        // end POCOR-4035
    }

    public function onModifyConditions(Event $events, $key, $value)
    {
        if ($key == 'user_type') {
            $conditions = [];
            switch ($value) {
                case self::STUDENT:
                    $conditions = [$this->aliasField('is_student') => 1];
                    break;

                case self::STAFF:
                    $conditions = [$this->aliasField('is_staff') => 1];
                    break;

                case self::GUARDIAN:
                    $conditions = [$this->aliasField('is_guardian') => 1];
                    break;

                case self::OTHER:
                    $conditions = [
                        $this->aliasField('is_student') => 0,
                        $this->aliasField('is_staff') => 0,
                        $this->aliasField('is_guardian') => 0
                    ];
                    break;
            }
            return $conditions;
        }
    }


    public function areaAdminstrativeAfterDelete(Event $event, $areaAdministrative)
    {
        $subqueryOne = $this->AddressAreas
            ->find()
            ->select(1)
            ->where(function ($exp, $q) {
                return $exp->equalFields($this->AddressAreas->aliasField('id'), $this->aliasField('address_area_id'));
            });

        $query = $this->find()
            ->select('id')
            ->where(function ($exp, $q) use ($subqueryOne) {
                return $exp->notExists($subqueryOne);
            });


        foreach ($query as $row) {
            $this->updateAll(
                ['address_area_id' => null],
                ['id' => $row->id]
            );
        }

        $subqueryTwo = $this->BirthplaceAreas
            ->find()
            ->select(1)
            ->where(function ($exp, $q) {
                return $exp->equalFields($this->BirthplaceAreas->aliasField('id'), $this->aliasField('birthplace_area_id'));
            });


        $query = $this->find()
            ->select('id')
            ->where(function ($exp, $q) use ($subqueryTwo) {
                return $exp->notExists($subqueryTwo);
            });


        foreach ($query as $row) {
            $this->updateAll(
                ['birthplace_area_id' => null],
                ['id' => $row->id]
            );
        }

    }

    public function getCustomFilter(Event $event)
    {
        $filters['user_type'] = [
            'label' => __('User Type'),
            'options' => [
                self::STAFF => __('Staff'),
                self::STUDENT => __('Students'),
                self::GUARDIAN => __('Guardians'),
                self::OTHER => __('Others')
            ]
        ];
        return $filters;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $options)
    {
        // POCOR-8558 start
        $referer = $this->request->getEnv('HTTP_REFERER');
        $parsedUrl = parse_url($referer);

        // Check if 'page' is set in the query string or 'AdvanceSearch' is present
        if (isset($_GET['page']) || isset($_REQUEST['AdvanceSearch'])) {
            $this->behaviors()->get('AdvanceSearch')->setConfig([
                'showOnLoad' => 0,
            ]);
        } else {
            $event->stopPropagation();
            return;
        }
        // POCOR-8558 ends

        $conditions = [];

        $notSuperAdminCondition = [
            $this->aliasField('super_admin') => 0
        ];
        $conditions = array_merge($conditions, $notSuperAdminCondition);

        // POCOR-2547 sort list of staff and student by name
        $orders = [];

        if (!isset($this->request->getQuery['sort'])) {
            $orders = [
                $this->aliasField('first_name'),
                $this->aliasField('last_name')
            ];
        }

        $query->where($conditions)
            ->order($orders);
        $options['auto_search'] = true;
        //POCOR-6248 starts
        $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');
        if ($userType == self::STAFF || $userType == self::STUDENT) {
            $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
            $UserIdentities = TableRegistry::get('User.Identities');
            $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
            $ConfigItem = $ConfigItemTable
                ->find()
                ->where([
                    $ConfigItemTable->aliasField('code') => 'directory_identity_number',
                    $ConfigItemTable->aliasField('value') => 1
                ])
                ->first();
            if (!empty($ConfigItem)) {
                //value_selection
                //get data from Identity Type table
                $typesIdentity = $this->getIdentityTypeData($ConfigItem->value_selection);
                if (!empty($typesIdentity)) {
                    $query
                        ->select([
                            'identity_type' => $IdentityTypes->aliasField('name'),
                            // for POCOR-6561 changed $typesIdentity->identity_type to $typesIdentity->id below
                            $typesIdentity->id => $UserIdentities->aliasField('number')
                        ])
                        ->leftJoin(
                            [$UserIdentities->getAlias() => $UserIdentities->getTable()],
                            [
                                $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('id'),
                                $UserIdentities->aliasField('identity_type_id = ') . $typesIdentity->id
                            ]
                        )
                        ->leftJoin(
                            [$IdentityTypes->getAlias() => $IdentityTypes->getTable()],
                            [
                                $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id'),
                                $IdentityTypes->aliasField('id = ') . $typesIdentity->id
                            ]
                        );
                }
            }
        }//POCOR-6248 ends
        $this->dashboardQuery = clone $query;
    }

public function getIdentityTypeData($value_selection)
    {
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $typesIdentity = $IdentityTypes
            ->find()
            ->select([
                'id' => $IdentityTypes->aliasField('id'),
                'identity_type' => $IdentityTypes->aliasField('name')
            ])
            ->where([
                $IdentityTypes->aliasField('id') => $value_selection
            ])
            ->first();
        return $typesIdentity;
    }

    public function findStudentsInSchool(Query $query, array $options)
    {
        $institutionIds = (isset($options['institutionIds'])) ? $options['institutionIds'] : [];
        if (!empty($institutionIds)) {
            $query
                ->join([
                    [
                        'type' => 'INNER',
                        'table' => 'institution_students',
                        'alias' => 'InstitutionStudents',
                        'conditions' => [
                            'InstitutionStudents.institution_id' . ' IN (' . $institutionIds . ')',
                            'InstitutionStudents.student_id = ' . $this->aliasField('id')
                        ]
                    ]
                ])
                ->group('InstitutionStudents.student_id');
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStudentsNotInSchool(Query $query, array $options)
    {
        $InstitutionStudentTable = TableRegistry::get('Institution.Students');
        $allInstitutionStudents = $InstitutionStudentTable->find()
            ->select([
                $InstitutionStudentTable->aliasField('student_id')
            ])
            ->where([
                $InstitutionStudentTable->aliasField('student_id') . ' = ' . $this->aliasField('id')
            ])
            ->bufferResults(false);
        $query->where(['NOT EXISTS (' . $allInstitutionStudents->sql() . ')', $this->aliasField('is_student') => 1]);
        return $query;
    }

    public function findStaffInSchool(Query $query, array $options)
    {
        $institutionIds = (isset($options['institutionIds'])) ? $options['institutionIds'] : [];
        if (!empty($institutionIds)) {
            $query->join([
                [
                    'type' => 'INNER',
                    'table' => 'institution_staff',
                    'alias' => 'InstitutionStaff',
                    'conditions' => [
                        'InstitutionStaff.institution_id' . ' IN (' . $institutionIds . ')',
                        'InstitutionStaff.staff_id = ' . $this->aliasField('id')
                    ]
                ]
            ]);
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStaffNotInSchool(Query $query, array $options)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $allInstitutionStaff = $InstitutionStaffTable->find()
            ->select([
                $InstitutionStaffTable->aliasField('staff_id')
            ])
            ->where([
                $InstitutionStaffTable->aliasField('staff_id') . ' = ' . $this->aliasField('id')
            ])
            ->bufferResults(false);
        $query->where(['NOT EXISTS (' . $allInstitutionStaff->sql() . ')', $this->aliasField('is_staff') => 1]);
        return $query;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'add') {
            if ($this->controller->getName() != 'Students') {
                $this->field('user_type', ['type' => 'select', 'after' => 'photo_content']);
            } else {
                //$this->request->query['user_type'] = self::GUARDIAN;
                $this->request = $this->request->withQueryParams(['user_type' => self::GUARDIAN]);
            }
            $requestData = $this->request->getData();
            $userType = isset($requestData[$this->getAlias()]['user_type']) ? $requestData[$this->getAlias()]['user_type'] : $this->request->getQuery('user_type');
            $this->field('openemis_no', ['user_type' => $userType]);
            switch ($userType) {
                case self::STUDENT:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities',
//                        'Contacts'
                    ]]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Student.Students',
                        'behavior' => 'Student',
                        'fieldKey' => 'student_custom_field_id',
                        'tableColumnKey' => 'student_custom_table_column_id',
                        'tableRowKey' => 'student_custom_table_row_id',
                        'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
                        'formKey' => 'student_custom_form_id',
                        'filterKey' => 'student_custom_filter_id',
                        'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
                        'recordKey' => 'student_id',
                        'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::STAFF:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' => ['Identities', 'Nationalities',
//                        'Contacts'
                    ]]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Staff.Staff',
                        'behavior' => 'Staff',
                        'fieldKey' => 'staff_custom_field_id',
                        'tableColumnKey' => 'staff_custom_table_column_id',
                        'tableRowKey' => 'staff_custom_table_row_id',
                        'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
                        'formKey' => 'staff_custom_form_id',
                        'filterKey' => 'staff_custom_filter_id',
                        'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
                        'recordKey' => 'staff_id',
                        'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::GUARDIAN:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Guardian', 'roleFields' => ['Identities', 'Nationalities']]);
                    break;
                case self::OTHER:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Other', 'roleFields' => ['Identities', 'Nationalities']]);
                    break;
            }
            $this->field('nationality_id', ['visible' => false]);
            $this->field('identity_type_id', ['visible' => false]);
        }
        if ($this->action == 'edit') {
            $this->hideOtherInformationSection($this->controller->getName(), 'edit');
            $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');
            $this->field('openemis_no', ['user_type' => $userType]);
            $this->addCustomUserBehavior($userType);
        }
        if ($this->action == 'view') {
            $encodedParam = $this->request->getAttribute('params')['pass'][1];
            $securityUserId = $this->ControllerAction->paramsDecode($encodedParam)['id'];

            $userInfo = TableRegistry::get('User.Users')->get($securityUserId);
            if ($userInfo->is_student) {
                $userType = self::STUDENT;
                $this->addCustomUserBehavior($userType);
            } elseif ($userInfo->is_staff) {
                $userType = self::STAFF;
                $this->addCustomUserBehavior($userType);
            } elseif ($userInfo->is_guardian) {
                $userType = self::GUARDIAN;
                $this->addCustomUserBehavior($userType);
            }

        } elseif ( $this->action == 'index' && $this->Session->check('Directory')) {
            $this->Session->delete('Directory');
        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Directory', 'Overview', 'General');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function hideOtherInformationSection($controller, $action)
    {
        if (($action == "add") || ($action == "edit")) { //hide "other information" section on add/edit guardian because there wont be any custom field.
            if (($controller == "Students") || ($controller == "Directories")) {
                $this->field('other_information_section', ['visible' => false]);
            }
        }
    }

    private function addCustomUserBehavior($userType)
    {
        switch ($userType) {
            case self::STUDENT:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>
                    ['Identities',
                        'Nationalities',
//                        'Contacts'
                    ]]);
                $this->addBehavior('CustomField.Record', [
                    'model' => 'Student.Students',
                    'behavior' => 'Student',
                    'fieldKey' => 'student_custom_field_id',
                    'tableColumnKey' => 'student_custom_table_column_id',
                    'tableRowKey' => 'student_custom_table_row_id',
                    'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
                    'formKey' => 'student_custom_form_id',
                    'filterKey' => 'student_custom_filter_id',
                    'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
                    // 'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
                    'recordKey' => 'student_id',
                    'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
                    'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                ]);
                break;
            case self::STAFF:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Staff',
                    'roleFields' => ['Identities',
                        'Nationalities',
//                        'Contacts'
                    ]]);
                $this->addBehavior('CustomField.Record', [
                    'model' => 'Staff.Staff',
                    'behavior' => 'Staff',
                    'fieldKey' => 'staff_custom_field_id',
                    'tableColumnKey' => 'staff_custom_table_column_id',
                    'tableRowKey' => 'staff_custom_table_row_id',
                    'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
                    'formKey' => 'staff_custom_form_id',
                    'filterKey' => 'staff_custom_filter_id',
                    'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
                    'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
                    'recordKey' => 'staff_id',
                    'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
                    'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                ]);
                break;
            case self::GUARDIAN:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Guardian', 'roleFields' => ['Identities', 'Nationalities']]);
                break;
            case self::OTHER:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Other', 'roleFields' => ['Identities', 'Nationalities']]);
                break;
        }

        return;
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        // Case 1: if user has only one identity, show the same,
        // Case 2: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.
        // Case 3: if user has more than one identity (no one is linked to nationality), show the first

        $users_ids = TableRegistry::get('User.Identities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();

        $users_ids = TableRegistry::get('User.Identities');
        $user_id_data = $users_ids->find()
            ->select(['number'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            return $entity->identity_number = $user_id_data->number;
        } else {
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::get('FieldOption.Nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('User.Identities');
                $user_id_data_nat = $users_ids->find()
                    ->select(['number'])
                    ->where([
                        $users_ids->aliasField('security_user_id') => $entity->id,
                        $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                    ])
                    ->first();
                if ($user_id_data_nat != null) {
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }

            if (count($nationality_based_ids) > 0) {
                // Case 2 - returning value
                return $entity->identity_number = $nationality_based_ids[0]['number'];
            } else {
                // Case 3 - returning value, return again from Case 1
                return $entity->identity_number = $user_id_data->number;
            }
        }
    }

    // POCOR-5684
    public function onGetIdentityTypeID(Event $event, Entity $entity)
    {
        $users_ids = TableRegistry::get('User.Identities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();

        $users_ids = TableRegistry::get('User.Identities');
        $user_id_data = $users_ids->find()
            ->select(['number', 'identity_type_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            $users_id_type = TableRegistry::get('FieldOption.IdentityTypes');
            $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
                ])
                ->first();
            return $entity->identity_type_id = $user_id_name->name;
        } else {
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::get('FieldOption.Nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('User.Identities');
                $user_id_data_nat = $users_ids->find()
                    ->select(['number', 'identity_type_id'])
                    ->where([
                        $users_ids->aliasField('security_user_id') => $entity->id,
                        $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                    ])
                    ->first();
                if ($user_id_data_nat != null) {
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }
            if (count($nationality_based_ids) > 0) {
                // Case 2 - returning value
                $users_id_type = TableRegistry::get('FieldOption.IdentityTypes');
                $user_id_name = $users_id_type->find()
                    ->select(['name'])
                    ->where([
                        $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                    ])
                    ->first();
                return $entity->identity_type_id = $user_id_name->name;
            } else {
                // Case 3 - returning value, return again from Case 1
                if(!empty( $user_id_data)){
                    $users_id_type = TableRegistry::get('FieldOption.IdentityTypes');
                    $user_id_name = $users_id_type->find()
                        ->select(['name'])
                        ->where([
                            $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
                        ])
                        ->first();
                    return $entity->identity_type_id = $user_id_name->name;
                }
            }
        }
    }

    public function addBeforeAction(Event $event)
    {
        $requestData = $this->request->getData();
        if (!isset($requestData[$this->getAlias()]['user_type'])) {
            // $this->request->data[$this->getAlias()]['user_type'] = $this->request->query('user_type');
            $requestData['user_type'] = $this->request->getQuery('user_type');
            $this->request = $this->request->withData($this->getAlias(), $requestData);
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // need to find out order values because recordbehavior changes it
        $allOrderValues = [];
        foreach ($this->fields as $key => $value) {
            $allOrderValues[] = (isset($value['order']) && !empty($value['order'])) ? $value['order'] : 0;
        }
        $highestOrder = max($allOrderValues);

        $userType = $this->request->getQuery('user_type');

        $openemisNo = $this->getUniqueOpenemisId();

        $this->fields['openemis_no']['value'] = $openemisNo;
        $this->fields['openemis_no']['attr']['value'] = $openemisNo;
        // pr($this->request->data[$this->getAlias()]['username']);
        $requestData = $this->request->getData();
        $data['username'] = '';
        if (!isset($requestData[$this->getAlias()]['username'])) {
            $data['username'] = $openemisNo;
        } elseif ($requestData[$this->getAlias()]['username'] == $requestData[$this->getAlias()]['openemis_no']) {
            $data['username'] = $openemisNo;
        } elseif (empty($requestData[$this->getAlias()]['username'])) {
            $entity->invalid('username', $openemisNo, true);
            $data['username'] = $openemisNo;
        }

        $this->field('username', ['order' => ++$highestOrder, 'visible' => true]);
        $data['password'] = '';
        if (!isset($requestData[$this->getAlias()]['password'])) {
            // POCOR-8231 removed unnecessary call for user
            // Read the number of length of password from system config
            $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
            $data['password'] = $ConfigItems->getAutoGeneratedPassword();
        }
        $this->request = $this->request->withData($this->getAlias(), $data);

        $this->field('password', ['order' => ++$highestOrder, 'visible' => true, 'attr' => ['autocomplete' => 'off']]);
        $this->field('nationality', ['attr' => ['required' => true]]);//POCOR-5987
        $this->field('identity_number', ['visible' => true]);
        $this->setFieldOrder([
            'information_section', 'photo_content', 'user_type', 'openemis_no', 'first_name', 'middle_name',
            'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'nationality_id',
            'identity_type_id', 'location_section', 'address', 'postal_code', 'address_area_section', 'address_area_id',
            'birthplace_area_section', 'birthplace_area_id', 'other_information_section', 'contact_type', 'contact_value', 'nationality',
            'identity_type', 'identity_number',
            'username', 'password'
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['advance_search'] = [
            'type' => 'button',
            'attr' => [
                'class' => 'btn btn-default btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'title' => __('Advanced Search'),
                'id' => 'search-toggle',
                'escape' => false,
                'ng-click' => 'toggleAdvancedSearch()'
            ],
            'url' => '#',
            'label' => '<i class="fa fa-search-plus"></i>',
        ];
        if(isset($toolbarButtons['search']))//POCOr-8733
           unset($toolbarButtons['search']);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            // history button need to check permission ??
            if ($this->AccessControl->check(['DirectoryHistories', 'index'])) {
                $userId = $entity->id;

                $icon = '<i class="fa fa-history"></i>';
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Histories',
                    'index'
                ];

                $buttons['history'] = $buttons['view'];
                $buttons['history']['label'] = $icon . __('History');
                $buttons['history']['url'] = $this->ControllerAction->setQueryString($url, [
                    'security_user_id' => $userId,
                    'user_type' => $this->getAlias()
                ]);
            }
            // end history button
        }

        return $buttons;
    }

    public function onUpdateFieldUserType(Event $event, array $attr, $action, ServerRequest $request)
    {
        $options = [
            self::STUDENT => __('Student'),
            self::STAFF => __('Staff'),
            self::GUARDIAN => __('Guardian'),
            self::OTHER => __('Others')
        ];
        $attr['options'] = $options;
        $attr['onChangeReload'] = 'changeUserType';
        if (!$this->request->getQuery('user_type')) {
            //$this->request->Query['user_type'] = key($options);
            $this->request = $this->request->withQueryParams(['user_type' => key($options)]);
        }
        return $attr;
    }

    public function addOnChangeUserType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->getQuery['user_type']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $data)) {
                if (array_key_exists('user_type', $data[$this->getAlias()])) {
                    //$this->request->query['user_type'] = $data[$this->getAlias()]['user_type'];
                    $this->request = $this->request->withQueryParams(['user_type' => $data[$this->getAlias()]['user_type']]);

                }
            }

            if (isset($data[$this->getAlias()]['custom_field_values'])) {
                unset($data[$this->getAlias()]['custom_field_values']);
            }

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'Identities' => ['validate' => false],
                'Nationalities' => ['validate' => false],
                'SpecialNeeds' => ['validate' => false],
                'Contacts' => ['validate' => false]
            ];
        }
    }

    public function onUpdateFieldPassword(Event $event, array $attr, $action, ServerRequest $request)
    {
        // setting the tooltip message
        $tooltipMessagePassword = $this->getMessage('Users.tooltip_message_password');

        $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        $attr['attr']['label']['text'] = __(Inflector::humanize($attr['field'])) . $this->tooltipMessage($tooltipMessagePassword);

        return $attr;
    }
    //POCOR-7083 :: end

    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {
        $userType = $requestData[$this->getAlias()]['user_type'];
        $type = [
            'is_student' => '0',
            'is_staff' => '0',
            'is_guardian' => '0'
            // 'is_student' => intval(0),
            // 'is_staff' => intval(0),
            // 'is_guardian' => intval(0)
            // 'is_student' => 0,
            // 'is_staff' => 0,
            // 'is_guardian' => 0
        ];
        switch ($userType) {
            case self::STUDENT:
                $type['is_student'] = 1;
                break;
            case self::STAFF:
                $type['is_staff'] = 1;
                break;
            case self::GUARDIAN:
                $type['is_guardian'] = 1;
                break;
        }
        $directoryEntity = array_merge($requestData[$this->getAlias()], $type);
        $requestData[$this->getAlias()] = $directoryEntity;
    }

    public function indexAfterAction(Event $event)
    {
        // echo "<pre>";print_r($_REQUEST);die;
        // $data  = $event->getData();
        // $datas = $data['2'];
        // unset($datas['toolbarButtons']['view']['url']['?']);
        // unset($datas['indexButtons']['view']['url']['?']);
        // unset($datas['indexButtons']['view']['url']['page']);
        // unset($datas['toolbarButtons']['view']['url']['page']);
        // echo "<pre>";print_r($datas);exit;
        $this->fields = [];
        $this->controller->set('ngController', 'AdvancedSearchCtrl');

        $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');

        switch ($userType) {
            case self::ALL:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::STUDENT:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                $this->field('student_status', ['order' => 52]);
                break;
            case self::STAFF:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::GUARDIAN:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::OTHER:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
        }
        $this->fields['date_of_birth']['type'] = 'date';
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        // echo '<pre>';print_r($extra);die;
        if ($this->action == 'index') {
            $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');
            //POCOR-6248 starts
                $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
                $ConfigItem = $ConfigItemTable
                    ->find()
                    ->where([
                        $ConfigItemTable->aliasField('type') => 'Columns for Directory List Page'
                    ])
                    ->all();
                foreach ($ConfigItem as $item) {
                    if ($item->code == 'directory_photo') {
                        $this->field('photo_name', ['visible' => false]);
                        if ($item->value == 1) {
                            $this->field('photo_content', ['visible' => true]);
                        } else {
                            $this->field('photo_content', ['visible' => false]);
                        }
                    }
                    if ($item->code == 'directory_openEMIS_ID') {
                        if ($item->value == 1) {
                            $this->field('openemis_no', ['visible' => true, 'before' => 'name']);
                        } else {
                            $this->field('openemis_no', ['visible' => false, 'before' => 'name']);
                        }
                    }
                    if ($item->code == 'directory_name') {
                        if ($item->value == 1) {
                            $this->field('name', ['visible' => true, 'before' => 'institution']);
                        } else {
                            $this->field('name', ['visible' => false, 'before' => 'institution']);
                        }
                    }
                    if ($item->code == 'directory_institution') {
                        if ($item->value == 1) {
                            $this->field('institution', ['visible' => true, 'before' => 'date_of_birth']);
                        } else {
                            $this->field('institution', ['visible' => false, 'before' => 'date_of_birth']);
                        }
                    }
                    if ($item->code == 'directory_date_of_birth') {
                        if ($item->value == 1) {
                            $this->field('date_of_birth', ['visible' => true, 'before' => 'student_status']);
                        } else {
                            $this->field('date_of_birth', ['visible' => false, 'before' => 'student_status']);
                        }
                    }
                    if ($item->code == 'directory_identity_number') {
                        if ($item->value == 1) {
                            if (!empty($item->value_selection)) {
                                //get data from Identity Type table
                                $typesIdentity = $this->getIdentityTypeData($item->value_selection);
                                if (isset($typesIdentity)) { //POCOR-6679
                                    $this->field($typesIdentity->identity_type, ['visible' => true, 'after' => 'date_of_birth']);
                                }
                            }
                        } else {
                            $typesIdentity = $this->getIdentityTypeData($item->value_selection); //POCOR-6679
                            $this->field($typesIdentity->identity_type, ['visible' => false, 'after' => 'date_of_birth']);
                        }
                    }
                }
            }
            $this->field('student_status', ['visible' => false]);
            //POCOR-6248 ends

            switch ($userType) {
                case self::ALL:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
                case self::STUDENT:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth', 'student_status']);
                    break;
                case self::STAFF:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
                case self::GUARDIAN:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                case self::OTHER:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
                default: //POCOR-8850
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
            }
    }

    public function onGetStudentStatus(Event $event, Entity $entity)
    {
        return __($entity->student_status_name);
    }

    public function getNumberOfUsersByGender($params = [])
    {
        $query = isset($params['query']) ? $params['query'] : null;
        if (!is_null($query)) {
            $userRecords = clone $query;
        } else {
            $userRecords = $this->find();
        }
        $genderCount = $userRecords
            ->contain(['Genders'])
            ->select([
                'count' => $userRecords->func()->count($this->aliasField('id')),
                'gender' => 'Genders.name'
            ])
            ->group('gender', true)
            ->bufferResults(false);

        // Creating the data set
        $dataSet = [];
        foreach ($genderCount as $value) {
            //Compile the dataset
            if (is_null($value['gender'])) {
                $value['gender'] = 'Not Defined';
            }
            $dataSet[] = [__($value['gender']), $value['count']];
        }
        $params['dataSet'] = $dataSet;
        return $params;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities' => [
                'fields' => [
                    'MainNationalities.id',
                    'MainNationalities.name'
                ]
            ],
            'MainIdentityTypes' => [
                'fields' => [
                    'MainIdentityTypes.id',
                    'MainIdentityTypes.name'
                ]
            ],
            'Genders' => [
                'fields' => [
                    'Genders.id',
                    'Genders.name'
                ]
            ]
        ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $isSet = $this->setSessionAfterAction($event, $entity);

        if ($isSet) {
            $reload = $this->Session->read('Directory.Directories.reload');
            if (!isset($reload)) {
                $urlParams = $this->url('edit');
                $event->stopPropagation();
                return $this->controller->redirect($urlParams);
            }
        }

        $this->setupTabElements($entity);

        if ($entity->is_student) {
            /*$this->fields['gender_id']['type'] = 'readonly';
            $this->fields['gender_id']['attr']['value'] = $entity->has('gender') ? $entity->gender->name : '';
            $this->fields['gender_id']['value'] = $entity->has('gender') ? $entity->gender->id : '';*/
        }

        $this->fields['nationality_id']['type'] = 'readonly';
        if (!empty($entity->main_nationality)) {
            $this->fields['nationality_id']['attr']['value'] = $entity->main_nationality->name;
        }

        $this->fields['identity_type_id']['type'] = 'readonly';
        if (!empty($entity->main_identity_type)) {
            $this->fields['identity_type_id']['attr']['value'] = $entity->main_identity_type->name;
        }

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
    }

    private function setSessionAfterAction($event, $entity)
    {
        $this->Session->write('Directory.Directories.id', $entity->id);
        $this->Session->write('Directory.Directories.name', $entity->name);

        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();
            $this->Session->write('AccessControl.Institutions.ids', $institutionIds);
        }

        $isStudent = $entity->is_student;
        $isStaff = $entity->is_staff;
        $isGuardian = $entity->is_guardian;
        $isSet = false;
        $this->Session->delete('Directory.Directories.is_student');
        $this->Session->delete('Directory.Directories.is_staff');
        $this->Session->delete('Directory.Directories.is_guardian');
        if ($isStudent) {
            $this->Session->write('Directory.Directories.is_student', true);
            $this->Session->write('Student.Students.id', $entity->id);
            $this->Session->write('Student.Students.name', $entity->name);
            $isSet = true;
        }

        if ($isStaff) {
            $this->Session->write('Directory.Directories.is_staff', true);
            $this->Session->write('Staff.Staff.id', $entity->id);
            $this->Session->write('Staff.Staff.name', $entity->name);
            $isSet = true;
        }

        if ($isGuardian) {
            $this->Session->write('Directory.Directories.is_guardian', true);
            $this->Session->write('Guardian.Guardians.id', $entity->id);
            $this->Session->write('Guardian.Guardians.name', $entity->name);
            $isSet = true;
        }

        return $isSet;
    }

    private function setupTabElements($entity)
    {
        $id = !is_null($this->request->getQuery('id')) ? $this->request->getQuery('id') : 0;

        $options = [
            // 'userRole' => 'Student',
            // 'action' => $this->action,
            // 'id' => $id,
            // 'userId' => $entity->id
        ];
        $tabElements = $this->controller->getUserTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        //POCOR-6332 commented due to this function some error was occuring
        $isSet = $this->setSessionAfterAction($event, $entity);
        if ($isSet) {
            $reload = $this->Session->read('Directory.Directories.reload');
            if (!isset($reload)) {
                $urlParams = $this->url('view');
                $event->stopPropagation();
                return $this->controller->redirect($urlParams);
            }
        }

        $this->setupTabElements($entity);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-8059::start
        if ($entity->isNew()) {
            $entity->preferred_language = 'en';
        }else{
            if(!empty($entity->date_of_death)){
                $dob = $entity->date_of_birth->i18nFormat('yyyy-MM-dd');
                $dod = $entity->date_of_death->i18nFormat('yyyy-MM-dd');
                if($dob > $dod){
                    $entity->dod_range = "greater";
                }

                if(isset($entity->dod_range)){
                    $event->stopPropagation();
                    $this->Alert->warning('general.dodmsg' , ['reset' => true]);
                    $url = $this->url('edit');
                    return $this->controller->redirect($url);
                }
            }
        }
        //POCOR-8059 :: end
        //POCOR-8906 start
        if (!$entity->isNew()) {
            if (!$entity->is_student) {
                $dirty = $entity->getDirty();
                Log::debug(print_r($dirty,true));
                if (in_array('gender_id', $dirty)) {
                    $this->Alert->error(__('Gender is not editable in Directories') , ['type' => 'string', 'reset' => true]);
                    $entity->setErrors(['gender_id', __('Gender is not editable in Directories')]);
                    return false;
                }
            }
        }
        //POCOR-8906 end
    }

    public function onGetInstitution(Event $event, Entity $entity)
    {
        $userId = $entity->id;
        $isStudent = $entity->is_student;
        $isStaff = $entity->is_staff;
        $isGuardian = $entity->is_guardian;

        $studentInstitutions = [];
        if ($isStudent) {
            $InstitutionStudentTable = TableRegistry::get('Institution.Students');
            /**POCOR-6902 starts - modified query to fetch correct institution name*/
            $studentInstitutions = $InstitutionStudentTable->find()
                ->matching('StudentStatuses', function ($q) {
                    return $q->where(['StudentStatuses.code' => 'CURRENT']);
                })
                ->matching('Institutions')
                ->where([
                    $InstitutionStudentTable->aliasField('student_id') => $userId
                ])
                ->select(['id' => $InstitutionStudentTable->aliasField('institution_id'), 'name' => 'Institutions.name', 'student_status_name' => 'StudentStatuses.name'])
                ->first();
            /**POCOR-6902 ends*/

            $value = '';
            $name = '';
            if (!empty($studentInstitutions)) {
                $value = $studentInstitutions->student_status_name;
                $name = $studentInstitutions->name;
            }
            $entity->student_status_name = $value;

            return $name;
        }

        $staffInstitutions = [];
        if ($isStaff) {
            $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
            $today = date('Y-m-d');
            $staffInstitutions = $InstitutionStaffTable->find('list', [
                'keyField' => 'id',
                'valueField' => 'institutionName'
            ])
                ->find('inDateRange', ['start_date' => $today, 'end_date' => $today])
                ->matching('Institutions')
                ->where([$InstitutionStaffTable->aliasField('staff_id') => $userId])
                ->select(['id' => 'Institutions.id', 'institutionName' => 'Institutions.name'])
                ->group(['Institutions.id'])
                ->order(['Institutions.name'])
                ->toArray();
            return implode('<BR>', $staffInstitutions);
        }
    }

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
            //Inflector::humanize(Inflector::underscore());
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        } else {
            try {
                TableRegistry::get('StudentCustomField.StudentCustomFieldValues')->deleteAll(['student_id' => $entity->id]);
            } catch (\Exception $exception) {
                $this->log($exception->getMessage(), 'error');
            }
            $users = TableRegistry::get('Security.Users');
            $user = $users->get($entity->id);
            if ($users->delete($user)) {
                $this->Alert->success('general.delete.success', ['reset' => true]);
                return $this->controller->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'index']);
            }
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'photo_content') {
            return __('Photo Content');
        } elseif ($field == 'openemis_id') {
            return __('OpenEMIS ID');
        } elseif ($field == 'first_name') {
            return __('First Name');
        } elseif ($field == 'middle_name') {
            return __('Middle Name');
        } elseif ($field == 'third_name') {
            return __('Third Name');
        } elseif ($field == 'last_name') {
            return __('Last Name');
        } elseif ($field == 'preferred_name') {
            return __('Preferred Name');
        } elseif ($field == 'gender_id') {
            return __('Gender');
        } elseif ($field == 'date_of_birth') {
            return __('Date Of Birth');
        } elseif ($field == 'email') {
            return __('Email');
        } elseif ($field == 'details') {
            return __('Details');
        } elseif ($field == 'address') {
            return __('Address');
        } elseif ($field == 'staff_id') {
            return __('Staff');
        } elseif ($field == 'start_date') {
            return __('Start Date');
        } elseif ($field == 'end_date') {
            return __('End Date');
        } elseif ($field == 'staff_status_id') {
            return __('Staff Status');
        } elseif ($field == 'passport_no') {
            return __('Passport');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } elseif ($field == 'username') {
            return __('Username');
        } elseif ($field == 'address_area_id') {
            return __('Address');
        } elseif ($field == 'birthplace_area_id') {
            return __('Birth Area');
        } elseif ($field == 'username') {
            return __('Username');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    private function checkUsersChildRecords($entity)
    {
        $result = false;
        $securityUserId = $entity->id ?? 0;
        // First count child records and after that delete main record if there is no any child record found
        // Records to delete from tables-
        // institution_class_students (student_id),
        // user_activities (security_user_id),
        // student_custom_field_values (student_id),
        // institution_competency_results (student_id)
        // institution_student_absences (student_id),
        // institution_student_absence_days (student_id)
        // institution_student_absence_details (student_id),
        // institution_students (student_id)
        // student_risks_criterias
        // institution_student_risks (student_id)
        // institution_subject_students (student_id)
        // user_special_needs_devices (security_user_id)
        // user_special_needs_referrals (security_user_id)
        // user_special_needs_services (security_user_id)
        // institution_cases (assignee_id)
        // institution_staff_shifts (staff_id)
        // institution_student_admission (student_id)
        // institution_student_surveys (student_id)
        // institution_student_survey_answers (institution_student_survey_id)
        // institution_subject_students (student_id)
        // security_group_users  (security_user_id)
        // student_status_updates (security_user_id)


        if ($securityUserId) {
            // count all institution_class_students
            $institutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all user activities
            $userActivities = TableRegistry::get('User.UserActivities')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            // count all student_custom_field_values
            $studentCustomFieldValues = TableRegistry::get('StudentCustomField.StudentCustomFieldValues')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_competency_results
            $institutionCompetencyResults = TableRegistry::get('Institution.InstitutionCompetencyResults')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_absences
            $institutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_absence_days
            $institutionStudentAbsenceDays = TableRegistry::get('Institution.InstitutionStudentAbsenceDays')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_absence_details
            $institutionStudentAbsenceDetails = TableRegistry::get('Institution.InstitutionStandardStudentAbsenceType')//need to ask
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_students
            $institutionStudents = TableRegistry::get('Institution.InstitutionStudents')
                ->find()->where(['student_id' => $securityUserId])->count();

            // student_risks_criterias
            $students = TableRegistry::get('Institution.InstitutionStudentRisks');
            $query = $students->find()->select(['id'])->where(['student_id =' => $securityUserId]);

            $studentRiskIds = [];
            foreach ($query as $s) {
                $studentRiskIds[] = $s->id;
            }

            $studentRisksCriterias = 0;
            if (count($studentRiskIds)) {
                $studentRisksCriterias = TableRegistry::get('Institution.StudentRisksCriterias')
                    ->find()->where(['institution_student_risk_id IN' => $securityUserId])->count();
            }

            // count all institution_student_risks
            $institutionStudentRisks = TableRegistry::get('Institution.InstitutionStudentRisks')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_subject_students
            $institutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all user_special_needs_devices
            $userSpecialNeedsDevices = TableRegistry::get('SpecialNeeds.SpecialNeedsDevices')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            // count all user_special_needs_referrals
            $userSpecialNeedsReferrals = TableRegistry::get('SpecialNeeds.SpecialNeedsReferrals')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            // count all user_special_needs_services
            $userSpecialNeedsServices = TableRegistry::get('SpecialNeeds.SpecialNeedsServices')
                ->find()->where(['security_user_id' => $securityUserId])->count();
            // count all user_special_needs_services
            $userSpecialNeedsAssessments = TableRegistry::get('SpecialNeeds.SpecialNeedsAssessments')
                ->find()->where(['security_user_id' => $securityUserId])->count();


            // count all institution_cases
            $institutionCases = TableRegistry::get('Cases.InstitutionCases')
                ->find()->where(['assignee_id' => $securityUserId])->count();

            // count all institution_staff_shifts
            $institutionStaffShifts = TableRegistry::get('Institution.InstitutionStaffShifts')
                ->find()->where(['staff_id' => $securityUserId])->count();

            //// POCOR-7179[START]
            $userNationalities = TableRegistry::get('User.Nationalities')
                ->find()->where(['security_user_id' => $securityUserId])->count();
            // POCOR-7179[END]

            //POCOR-7540 start
            // count all institution_student_admission
            $institutionStudentAdmission = TableRegistry::get('Institution.StudentAdmission')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_surveys and institution_student_survey_answers
            $institutionStudentSurveys = TableRegistry::get('Student.StudentSurveys')
                ->find()->where(['student_id' => $securityUserId])->toArray();


            $institutionStudentSurveysIds = [];
            foreach ($institutionStudentSurveys as $s) {
                $institutionStudentSurveysIds[] = $s->id;
            }

            $institutionStudentSurveyAnswers = 0;
            if (count($institutionStudentSurveysIds)) {
                $institutionStudentSurveyAnswers = TableRegistry::get('Institution.InstitutionStudentSurveyAnswers')
                    ->find()->where(['institution_student_survey_id IN' => $institutionStudentSurveysIds])->count();
            }

            //count all security_group_users
            $securityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            //count all student_status_updates
            $studentStatusUpdates = TableRegistry::get('Institution.StudentStatusUpdates')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            //POCOR-7540 end

            if ($institutionClassStudents ||
                $userActivities ||
                $studentCustomFieldValues ||
                $institutionCompetencyResults ||
                $institutionStudentAbsences ||
                $institutionStudentAbsenceDays ||
                $institutionStudentAbsenceDetails ||
                $institutionStudents ||
                count($studentRiskIds) ||
                $studentRisksCriterias ||
                $institutionStudentRisks ||
                $institutionSubjectStudents ||
                $userSpecialNeedsDevices ||
                $userSpecialNeedsReferrals ||
                $userSpecialNeedsServices ||
                $userSpecialNeedsAssessments ||
                $institutionCases ||
                $institutionStaffShifts || $userNationalities ||
                $institutionStudentAdmission ||//POCOR-7540
                count($institutionStudentSurveys) ||//POCOR-7540
                $institutionStudentSurveyAnswers ||//POCOR-7540
                $securityGroupUsers ||//POCOR-7540
                $studentStatusUpdates//POCOR-7540
            ) {
                $result = true;
            }
        }

        return $result;
    }

    //POCOR-8743 Start
    public function onGetModifiedUserId(Event $event, Entity $entity)
    {
        if(!empty($entity->modified_user_id)) {
            $users = TableRegistry::get('Security.Users');
            // POCOR-9083 start
            try {
                $user = $users->get($entity->modified_user_id);
            } catch (\Exception $e) {
                return $entity->modified_user_id; // Handle the absence of the user gracefully
            }
            // POCOR-9083 end
            return $user->name;
        }
    }

    public function onGetCreatedUserId(Event $event, Entity $entity)
    {
        $users = TableRegistry::get('Security.Users');
        // POCOR-9083 start
        if(!empty($entity->created_user_id)) {
            try {
                $user = $users->get($entity->created_user_id);
            } catch (\Exception $e) {
                return $entity->created_user_id; // Handle the absence of the user gracefully
            }
            return $user->name;
        }
    }
    // POCOR-9083 end
    //POCOR-8743 End
}
