<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class UserService extends Controller
{

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getUsersList($request)
    {
        try {
            $data = $this->userRepository->getUsersList($request);
            //dd('data', $data);
            $resp = [];
            foreach($data['data'] as $k => $d){
                //For POCOR-8536 Start...
                $staffIntitutions = [];
                $studIntitutions = [];

                if(isset($d['is_staff']) && $d['is_staff'] == 1){
                    $staffIntitutions = $this->userRepository->getStaffIntitutions($d['id']);
                }

                if(isset($d['is_student']) && $d['is_student'] == 1){
                    $studIntitutions = $this->userRepository->getStudentIntitutions($d['id']);
                }
                //For POCOR-8536 End...


                $resp[$k]['id'] = $d['id'];
                $resp[$k]['username'] = $d['username'];
                $resp[$k]['password'] = $d['password'];
                $resp[$k]['openemis_no'] = $d['openemis_no'];
                $resp[$k]['first_name'] = $d['first_name'];
                $resp[$k]['middle_name'] = $d['middle_name'];
                $resp[$k]['third_name'] = $d['third_name'];
                $resp[$k]['last_name'] = $d['last_name'];
                $resp[$k]['preferred_name'] = $d['preferred_name'];
                $resp[$k]['email'] = $d['email'];
                $resp[$k]['address'] = $d['address'];
                $resp[$k]['postal_code'] = $d['postal_code'];
                $resp[$k]['address_area_id'] = $d['address_area_id'];
                $resp[$k]['birthplace_area_id'] = $d['birthplace_area_id'];
                $resp[$k]['gender_id'] = $d['gender_id'];
                $resp[$k]['date_of_birth'] = $d['date_of_birth'];
                $resp[$k]['date_of_death'] = $d['date_of_death'];
                $resp[$k]['nationality_id'] = $d['nationality_id'];
                $resp[$k]['identity_type_id'] = $d['identity_type_id'];
                $resp[$k]['identity_type_name'] = $d['identity_type']['name']??null;
                $resp[$k]['identity_number'] = $d['identity_number'];
                $resp[$k]['external_reference'] = $d['external_reference'];
                $resp[$k]['status'] = $d['status'];
                $resp[$k]['last_login'] = $d['last_login'];
                $resp[$k]['photo_name'] = $d['photo_name'];
                if($d['photo_content']){
                    $resp[$k]['photo_content'] = base64_encode($d['photo_content']);
                } else {
                    $resp[$k]['photo_content'] = Null;
                }

                $resp[$k]['preferred_language'] = $d['preferred_language'];
                $resp[$k]['is_student'] = $d['is_student'];
                $resp[$k]['is_staff'] = $d['is_staff'];
                $resp[$k]['is_guardian'] = $d['is_guardian'];

                // For POCOR-8398 start...
                $resp[$k]['staff_position_grade_id'] = null;
                $resp[$k]['staff_position_grade_name'] = null;

                if(isset($d['institution_staff'])){
                    $resp[$k]['staff_position_grade_id'] = $d['institution_staff']['staff_position_grade']['id'];
                    $resp[$k]['staff_position_grade_name'] = $d['institution_staff']['staff_position_grade']['name'];
                }
                // For POCOR-8398 end...

                $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                $resp[$k]['modified'] = $d['modified'];
                $resp[$k]['created_user_id'] = $d['created_user_id'];
                $resp[$k]['created'] = $d['created'];
                $resp[$k]['nationalities'] = $d['nationalities'];
                $resp[$k]['identities'] = $d['identities'];

                //For POCOR-8536 Start...
                $resp[$k]['institution-student'] = $studIntitutions;
                $resp[$k]['institution-staff'] = $staffIntitutions;
                //For POCOR-8536 End...

            }

            $data['data'] = $resp;
            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users List Not Found');
        }
    }


    public function getUsersData(int $userId)
    {
        try {
            $data = $this->userRepository->getUsersData($userId);

            $resp = [];
            if(isset($data)){
                if($data['photo_content']){
                    $photo_content = base64_encode($data['photo_content']);
                } else {
                    $photo_content = Null;
                }

                // For POCOR-8398 start...
                $staff_position_grade_id = null;
                $staff_position_grade_name = null;

                if(isset($item['institutionStaff'])){
                    $staff_position_grade_id = $item['institutionStaff']['staffPositionGrade']['id'];
                    $staff_position_grade_name = $item['institutionStaff']['staffPositionGrade']['name'];
                }

                //POCOR-9697: password and super_admin omitted from the
                //response. Both are sensitive (password is a bcrypt hash,
                //super_admin tells an attacker which accounts to target) and
                //no frontend feature reads them from /api/v4/users/{id}.
                $resp = [
                        "id" => $data['id'],
                        "username" => $data['username'],
                        "openemis_no" => $data['openemis_no'],
                        "first_name" => $data['first_name'],
                        "middle_name" => $data['middle_name'],
                        "third_name" => $data['third_name'],
                        "last_name" => $data['last_name'],
                        "preferred_name" => $data['preferred_name'],
                        "email" => $data['email'],
                        "user_contact" => $data['mobile_number'],//POCOR-8639
                        "address" => $data['address'],
                        "postal_code" => $data['postal_code'],
                        "address_area_id" => $data['address_area_id'],
                        "birthplace_area_id" => $data['birthplace_area_id'],
                        "gender_id" => $data['gender_id'],
                        "date_of_birth" => $data['date_of_birth'],
                        "date_of_death" => $data['date_of_death'],
                        "nationality_id" => $data['nationality_id'],
                        "identity_type_id" => $data['identity_type_id'],
                        "identity_type_name" => $data['identityType']['name']??null,
                        "identity_number" => $data['identity_number'],
                        "external_reference" => $data['external_reference'],
                        "status" => $data['status'],
                        "last_login" => $data['last_login'],
                        "photo_name" => $data['photo_name'],
                        "photo_content" => $photo_content,
                        "photo_name" => $data['photo_name'],
                        "preferred_language" => $data['preferred_language'],
                        "is_student" => $data['is_student'],
                        "is_staff" => $data['is_staff'],
                        "is_guardian" => $data['is_guardian'],
                        "modified_user_id" => $data['modified_user_id'],
                        "modified" => $data['modified'],
                        "created_user_id" => $data['created_user_id'],
                        "created" => $data['created'],
                        "nationalities" => $data['nationalities'],
                        "identities" => $data['identities'],
                        "genderData" => [
                            "key" => $data["gender"]["id"],
                            "value" => $data["gender"]["name"],
                        ],
                        "nationality_id" => [
                            "key" => (!empty($data["nationality"]["id"]))?$data["nationality"]["id"]:'',
                            "value" => (!empty($data["nationality"]["name"]))?$data["nationality"]["name"]:'',
                        ],
                        /*"institution" => [
                            "key" => (!empty($item["institutionStudent"]["institution"]["id"]))?$item["institutionStudent"]["institution"]["id"]:'',
                            "value" => (!empty($item["institutionStudent"]["institution"]["name"]))?$item["institutionStudent"]["institution"]["name"]:'',
                        ],*/
                        "educationGrade" => [
                            "key" => (!empty($data["institutionStudent"]["educationGrade"]["id"]))?$data["institutionStudent"]["educationGrade"]["id"]:'',
                            "value" => (!empty($data["institutionStudent"]["educationGrade"]["name"]))?$data["institutionStudent"]["educationGrade"]["name"]:'',
                        ],
                        "studentStatus" => [
                            "key" => (!empty($data["institutionStudent"]["studentStatus"]["id"]))?$data["institutionStudent"]["studentStatus"]["id"]:'',
                            "value" => (!empty($data["institutionStudent"]["studentStatus"]["name"]))?$data["institutionStudent"]["studentStatus"]["name"]:'',
                        ],
                        "staff_position_grade_id" => $staff_position_grade_id,
                        "staff_position_grade_name" => $staff_position_grade_name,
                        "institution-staff" => $data['institution_staff'],
                        "institution-students" => $data['institution_students'],
                         //POCOR-8639
                    ];
            }

            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Users Data Not Found');
        }
    }


    //POCOR-8862 start
    public function getUserIdByUsername(string $username)
    {
        try {
            $user_id = $this->userRepository->getUserIdByUsername($username);

            return $user_id;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('User Not Found');
        }
    }
    //POCOR-8862 end


    //POCOR-8840 start
    public function getUserIdByOpenemisNo(string $openemisNo)
    {
        try {
            $user_id = $this->userRepository->getUserIdByOpenemisNo($openemisNo);

            return $user_id;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('User Not Found');
        }
    }

    public function getGuardianWithStudents(int $guardianId)
    {
        try {
            $guardianData = $this->userRepository->getGuardianWithStudents($guardianId);

            return $guardianData;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('User Not Found');
        }
    }


    //POCOR-8840 end


    public function saveStudentData($request)
    {
        try {
            $data = $this->userRepository->saveStudentData($request);

            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to store student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store student data.');
        }
    }


    public function getUsersGender($request)
    {
        try {
            $data = $this->userRepository->getUsersGender($request);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Users Gender list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Gender List Not Found');
        }
    }



    public function saveStaffData($request)
    {
        try {
            $data = $this->userRepository->saveStaffData($request);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to store staff data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store staff data.');
        }
    }


    public function saveGuardianData($request)
    {
        try {
            $data = $this->userRepository->saveGuardianData($request);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to store guardian data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store guardian data.');
        }
    }



    //pocor-7545 starts
    public function addUsers($request)
    {
        try {
            $data = $this->userRepository->addUsers($request);
            return $data;

        } catch (\Exception $e) {
            Log::error(
                'User is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('User is not created/updated successfully.');
        }
    }
    //pocor-7545 ends
    //POCOR-7716 start
    public function getStudentAdmissionStatus()
    {
        try {
            $data = $this->userRepository->getStudentAdmissionStatus();

            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Default Student Admission Status',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Default Student Admission Status Not Found');
        }
    }
    //POCOR-7716 end


    //POCOR-8136 start
    public function getUserPermissions()
    {
        try {
            $data = $this->userRepository->getUserPermissions();
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get User Permissions List.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('User Permissions List Not Found.');
        }
    }
    //POCOR-8136 end


    //POCOR-8139 Starts

    public function externalDataSources($request)
    {
        try {
            $data = $this->userRepository->externalDataSources($request);
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get data from external data sources.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get data from external data sources.');
        }
    }

    //POCOR-8139 Ends

    //POCOR-8896 starts
    /**
     * Updates user data after validation and processing.
     *
     * @param array $userData User update data
     * @return mixed JSON response or repository result
     */
    public function patchUser(array $userData)
    {
        try {
            // Validate required fields before updating

            // Process sensitive data before saving (e.g., password hashing)

            // Update the user in the repository
            return $this->userRepository->patchUser($userData);

        } catch (\Exception $e) {
            Log::error(
                'User update failed.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('User update failed.');
        }
    }



    // POCOR-8896 end


    //POCOR-8912 start
    public function getUserIdByEmail(string $email)
    {
        try {
            $user_id = $this->userRepository->getUserIdByEmail($email);

            return $user_id;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('User Not Found');
        }
    }
    //POCOR-8912 end
}
