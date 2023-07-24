<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\SecurityUsers;

class UserRepository extends Controller
{
    public function getUsersList($request)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            $users = SecurityUsers::with('identityType', 'nationalities', 'identities');
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $users = $users->orderBy($col, $orderBy);
            }
            $list = $users->paginate($limit)->toArray();
            
            return $list;
            
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
            
            $users = SecurityUsers::with(
                    'gender',
                    'nationalities',
                    'institutionStudent',
                    'institutionStudent.institution',
                    'institutionStudent.educationGrade',
                    'institutionStudent.studentStatus',
                    'identities',
                    'nationality',
                    'identityType'
                )
                    ->where('id', $userId)
                    ->get();
            
            return $users;
            
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
            
            $usersGender = Gender::get();
            
            return $usersGender;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Users Gender list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Gender Data Not Found');
        }
    }
}

