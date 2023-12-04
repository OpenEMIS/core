<?php
	
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Areas;
use App\Models\SecurityGroupUsers;
use App\Models\SecurityRoleFunction;
use Illuminate\Http\Request;



if(!function_exists('checkAccess')){
	function checkAccess($additionalParam = [])
	{
		try {
			$user = JWTAuth::user();
			$userId = $user->id;
			//$userId = 8813;
			$groupIds = [];
			$roleIds = [];
			$institutionIds = [];

			$securityGroupUsers = SecurityGroupUsers::with(
					'securityGroup',
					'securityGroup.institutions', 
				)
				->where('security_user_id', $userId)
				->groupby('security_group_users.security_role_id')
				->groupby('security_group_users.security_group_id')
				->get()
				->toArray();
			
			foreach ($securityGroupUsers as $key => $sGU) {
				array_push($groupIds, $sGU['security_group_id']);
				array_push($roleIds, $sGU['security_role_id']);
				foreach($sGU['security_group']['institutions'] as $institution){
					array_push($institutionIds, $institution['institution_id']);
				}
			}

			$groupIds = array_unique($groupIds);
			$roleIds = array_unique($roleIds);
			$institutionIds = array_unique($institutionIds);

			$roleFunctions = SecurityRoleFunction::join('security_functions', 'security_functions.id', '=', 'security_role_functions.security_function_id')
				->select(
					'security_role_functions._view',
					'security_role_functions._edit',
					'security_role_functions._add',
					'security_role_functions._delete',
					'security_role_functions._execute',
					'security_role_id',
					'security_function_id',
					'security_functions.name',
					'security_functions.controller',
					'security_functions.module',
					'security_functions.category',
					'security_functions._view as security_function_view',
					'security_functions._edit as security_function_edit',
					'security_functions._add as security_function_add',
					'security_functions._delete as security_function_delete',
					'security_functions._execute as security_function_execute',
				)
				->whereIn('security_role_id', $roleIds)
				->get()
				->toArray();
			
			$accessArray = [];
			if(count($roleFunctions) > 0){
				foreach($roleFunctions as $key => $func){
					$controller = $func['controller'];
					

					$secFuncView = $func['security_function_view'];
					if($secFuncView != ""){

						$accessArray = getRoleAccess($controller, $secFuncView, $func['_view'], $func['security_role_id'], $accessArray);

					}

					$secFuncAdd = $func['security_function_add'];
					if($secFuncAdd != ""){
						$accessArray = getRoleAccess($controller, $secFuncAdd, $func['_add'], $func['security_role_id'], $accessArray);
					}


					$secFuncEdit = $func['security_function_edit'];
					if($secFuncEdit != ""){
						$accessArray = getRoleAccess($controller, $secFuncEdit, $func['_edit'], $func['security_role_id'], $accessArray);
					}


					$secFuncDelete = $func['security_function_delete'];
					if($secFuncDelete != ""){
						$accessArray = getRoleAccess($controller, $secFuncDelete, $func['_delete'], $func['security_role_id'], $accessArray);
					}


					$secFuncExecute = $func['security_function_execute'];
					if($secFuncExecute != ""){
						$accessArray = getRoleAccess($controller, $secFuncExecute, $func['_execute'], $func['security_role_id'], $accessArray);
					}
					
				}

				
			}
				
			if(count($additionalParam) > 0){
				if(isset($additionalParam['institution_id'])){
					if(!in_array($additionalParam['institution_id'], $institutionIds)){
						return 0;
					}
				}
			}
			

			//$permissions = session()->all();
			
			$data['userId'] = $userId;
			$data['groupIds'] = $groupIds;
			$data['roleIds'] = $roleIds;
			$data['institutionIds'] = $institutionIds;
			$data['permissions'] = $accessArray;

			//$setSession = session(['Permissions' => $data]);
			return $data;
			//return true;
		} catch (\Exception $e) {
			Log::error(
                'Failed to set permissions in session.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return 0;
		}
	}


	
	if(!function_exists('getRoleAccess')){
		function getRoleAccess($controller, $accessType, $action = 0, $roleId, $accessArray)
		{
			$accessArr = explode("|", $accessType);
						
			if(count($accessArr) > 1){
				
				foreach($accessArr as $access){
					
					$arr = explode(".", $access);
					//dd($vAArr);
					if(count($arr) > 1){
						
						if($action == 1){
							$accessArray[$controller][$arr[0]][$arr[1]][] = $roleId;
						}
						
					} else {
						if($action == 1){
							$accessArray[$controller][$arr[0]][] = $roleId;
						}
					}
				}
			} else {
				$access = $accessArr[0];
				$arr = explode(".", $access);
				if(count($arr) > 1){
					if($action == 1){
						$accessArray[$controller][$arr[0]][$arr[1]][] = $roleId;
					}
				} else {
					if($action == 1){
						$accessArray[$controller][$arr[0]][] = $roleId;
					}
				}
				
			}

			return $accessArray;
		}
	}
	
	if(!function_exists('checkPermission')){
		function checkPermission($params = [], $additionalParams = []){
			
			$permissions = checkAccess($params); //Fetching role and permissions.

            if(JWTAuth::user()->id > 2){ //Checking if not admin.
                if($permissions){
                    if(isset($permissions['permissions'][$params[0]])){
                    	if(isset($permissions['permissions'][$params[0]][$params[1]])){
                    		
                    		if(isset($permissions['permissions'][$params[0]][$params[1]][$params[2]]) && isset($params[2])){
                    			

                    			if(count($additionalParams) > 0) {
                    				if(isset($additionalParams['institution_id'])){
                    					if(in_array($additionalParams['institution_id'], $permissions['institutionIds'])){
                    						return true;
                    					} else {
                    						return false;
                    					}
                    					
                    				} else {
                    					return false;
                    				}
                    			} else {
                    				return true;
                    			}
                    		}
                    	}
                    }
                    
                    return false;
                } else {
                	
                    return false;
                }  
            } else {
            	return true;
            }
		}
	}
}
