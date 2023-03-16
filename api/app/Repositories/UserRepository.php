<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
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

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            $users = SecurityUsers::with('nationalities', 'identities');
            if(isset($params['order'])){
                $col = $params['order'];
                $users = $users->orderBy($col);
            }
            $list = $users->paginate($limit);
            //dd($list);
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
                    'nationality'
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
}

