<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\SaveStudentDataRequest;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService) 
    {
        $this->userService = $userService;
    }


    public function getUsersList(Request $request)
    {
        try {
            $data = $this->userService->getUsersList($request);
            return $this->sendSuccessResponse("Users List Found", $data);
            
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
            $data = $this->userService->getUsersData($userId);
            return $this->sendSuccessResponse("Users Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Data Not Found');
        }
    }



    public function saveStudentData(SaveStudentDataRequest $request)
    {
        try {
            $data = $this->userService->saveStudentData($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Student data stored successfully.");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            }else {
                return $this->sendErrorResponse("Student data not stored.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to store student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store student data.');
        }
    }


    public function saveStaffData(SaveStudentDataRequest $request)
    {
        try {
            $data = $this->userService->saveStaffData($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Student data stored successfully.");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            }else {
                return $this->sendErrorResponse("Student data not stored.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to store student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store student data.');
        }
    }
}
