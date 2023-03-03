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
                        "identity_number" => $item['identity_number'],
                        "external_reference" => $item['external_reference'],
                        "super_admin" => $item['super_admin'],
                        "external_reference" => $item['external_reference'],
                        "status" => $item['status'],
                        "last_login" => $item['last_login'],
                        "photo_name" => $item['photo_name'],
                        "photo_content" => $item['photo_content'],
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
                        ]
                    ];
                    
                });
            //dd($data);
            return $data;
            
        } catch (\Exception $e) {
            dd($e);
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Data Not Found');
        }
    }

}
