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
                /*if($d['id'] == 8815){
                    dd($d);
                }*/
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
                $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                $resp[$k]['modified'] = $d['modified'];
                $resp[$k]['created_user_id'] = $d['created_user_id'];
                $resp[$k]['created'] = $d['created'];
                $resp[$k]['nationalities'] = $d['nationalities'];
                $resp[$k]['identities'] = $d['identities'];
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
            $data = $this->userRepository->getUsersData($userId)
                ->map(function ($item, $key) {

                    if($item['photo_content']){
                        $photo_content = base64_encode($item['photo_content']);
                    } else {
                        $photo_content = Null;
                    }


                    return [
                        "id" => $item['id'],
                        "username" => $item['username'],
                        "password" => $item['password'],
                        "openemis_no" => $item['openemis_no'],
                        "first_name" => $item['first_name'],
                        "middle_name" => $item['middle_name'],
                        "third_name" => $item['third_name'],
                        "last_name" => $item['last_name'],
                        "preferred_name" => $item['preferred_name'],
                        "email" => $item['email'],
                        "address" => $item['address'],
                        "postal_code" => $item['postal_code'],
                        "address_area_id" => $item['address_area_id'],
                        "birthplace_area_id" => $item['birthplace_area_id'],
                        "gender_id" => $item['gender_id'],
                        "date_of_birth" => $item['date_of_birth'],
                        "date_of_death" => $item['date_of_death'],
                        "nationality_id" => $item['nationality_id'],
                        "identity_type_id" => $item['identity_type_id'],
                        "identity_type_name" => $item['identityType']['name']??null,
                        "identity_number" => $item['identity_number'],
                        "external_reference" => $item['external_reference'],
                        "super_admin" => $item['super_admin'],
                        "external_reference" => $item['external_reference'],
                        "status" => $item['status'],
                        "last_login" => $item['last_login'],
                        "photo_name" => $item['photo_name'],
                        "photo_content" => $photo_content,
                        "photo_name" => $item['photo_name'],
                        "preferred_language" => $item['preferred_language'],
                        "is_student" => $item['is_student'],
                        "is_staff" => $item['is_staff'],
                        "is_guardian" => $item['is_guardian'],
                        "modified_user_id" => $item['modified_user_id'],
                        "modified" => $item['modified'],
                        "created_user_id" => $item['created_user_id'],
                        "created" => $item['created'],
                        "nationalities" => $item['nationalities'],
                        "identities" => $item['identities'],
                        "genderData" => [
                            "key" => $item["gender"]["id"],
                            "value" => $item["gender"]["name"],
                        ],
                        "nationality_id" => [
                            "key" => (!empty($item["nationality"]["id"]))?$item["nationality"]["id"]:'',
                            "value" => (!empty($item["nationality"]["name"]))?$item["nationality"]["name"]:'',
                        ],
                        "institution" => [
                            "key" => (!empty($item["institutionStudent"]["institution"]["id"]))?$item["institutionStudent"]["institution"]["id"]:'',
                            "value" => (!empty($item["institutionStudent"]["institution"]["name"]))?$item["institutionStudent"]["institution"]["name"]:'',
                        ],
                        "educationGrade" => [
                            "key" => (!empty($item["institutionStudent"]["educationGrade"]["id"]))?$item["institutionStudent"]["educationGrade"]["id"]:'',
                            "value" => (!empty($item["institutionStudent"]["educationGrade"]["name"]))?$item["institutionStudent"]["educationGrade"]["name"]:'',
                        ],
                        "studentStatus" => [
                            "key" => (!empty($item["institutionStudent"]["studentStatus"]["id"]))?$item["institutionStudent"]["studentStatus"]["id"]:'',
                            "value" => (!empty($item["institutionStudent"]["studentStatus"]["name"]))?$item["institutionStudent"]["studentStatus"]["name"]:'',
                        ]
                    ];
                    
                });
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Data Not Found');
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

}
