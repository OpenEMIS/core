<?php

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Areas;
use App\Models\SecurityGroupUsers;
use App\Models\SecurityRoleFunction;
use App\Models\SecurityGroupAreas;
use App\Models\Institutions;
use App\Models\ConfigItem;
use App\Models\SecurityUsers;
use App\Models\OpenemisTemp;
use App\Models\AcademicPeriod;
use App\Models\InstitutionClassStudents;
use App\Models\MealProgrammes;
use App\Models\MealReceived;
use App\Models\MealBenefits;
use App\Models\StudentAttendanceType;
use App\Models\InstitutionClassSubjects;
use App\Models\AbsenceTypes;
use App\Models\StudentAbsenceReason;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;
// POCOR-8915 start
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;


// POCOR-8915 start
//	if(!function_exists('checkAccess')){
//		function checkAccess($additionalParam = [])
//		{
//			try {
//				$user = JWTAuth::user();
//				$userId = $user->id;
//				$super_admin = $user->super_admin??0;
//				//$userId = 8813;
//				$groupIds = [];
//				$roleIds = [];
//				$institutionIds = [];
//
//				$securityGroupUsers = SecurityGroupUsers::with(
//						'securityGroup',
//						'securityGroup.institutions',
//					)
//					->where('security_user_id', $userId)
//					->groupby('security_group_users.security_role_id')
//					->groupby('security_group_users.security_group_id')
//					->get()
//					->toArray();
//
//				foreach ($securityGroupUsers as $key => $sGU) {
//					array_push($groupIds, $sGU['security_group_id']);
//					array_push($roleIds, $sGU['security_role_id']);
//					foreach($sGU['security_group']['institutions'] as $institution){
//						array_push($institutionIds, $institution['institution_id']);
//					}
//				}
//
//
//
//				$groupIds = array_unique($groupIds);
//				$roleIds = array_unique($roleIds);
//
//
//				//For POCOR-8077 Start...
//				$groupAreaInstitutions = getGroupAreaInstitutions($groupIds);
//
//				$allowAllInstitutions = $groupAreaInstitutions['allowAllInstitutions']??0;
//				$otherInstitutionIds = $groupAreaInstitutions['institutionIds']??[];
//
//				$institutionIds = array_merge($institutionIds, $otherInstitutionIds);
//
//				//For POCOR-8077 End...
//
//
//				$institutionIds = array_unique($institutionIds);
//
//				$roleFunctions = SecurityRoleFunction::join('security_functions', 'security_functions.id', '=', 'security_role_functions.security_function_id')
//					->select(
//						'security_role_functions._view',
//						'security_role_functions._edit',
//						'security_role_functions._add',
//						'security_role_functions._delete',
//						'security_role_functions._execute',
//						'security_role_id',
//						'security_function_id',
//						'security_functions.name',
//						'security_functions.controller',
//						'security_functions.module',
//						'security_functions.category',
//						'security_functions._view as security_function_view',
//						'security_functions._edit as security_function_edit',
//						'security_functions._add as security_function_add',
//						'security_functions._delete as security_function_delete',
//						'security_functions._execute as security_function_execute',
//					)
//					->whereIn('security_role_id', $roleIds)
//					->get()
//					->toArray();
//
//				$accessArray = [];
//				if(count($roleFunctions) > 0){
//					foreach($roleFunctions as $key => $func){
//						$controller = $func['controller'];
//
//
//						$secFuncView = $func['security_function_view'];
//						if($secFuncView != ""){
//
//							$accessArray = getRoleAccess($controller, $secFuncView, $func['_view'], $func['security_role_id'], $accessArray);
//
//						}
//
//						$secFuncAdd = $func['security_function_add'];
//						if($secFuncAdd != ""){
//							$accessArray = getRoleAccess($controller, $secFuncAdd, $func['_add'], $func['security_role_id'], $accessArray);
//						}
//
//
//						$secFuncEdit = $func['security_function_edit'];
//						if($secFuncEdit != ""){
//							$accessArray = getRoleAccess($controller, $secFuncEdit, $func['_edit'], $func['security_role_id'], $accessArray);
//						}
//
//
//						$secFuncDelete = $func['security_function_delete'];
//						if($secFuncDelete != ""){
//							$accessArray = getRoleAccess($controller, $secFuncDelete, $func['_delete'], $func['security_role_id'], $accessArray);
//						}
//
//
//						$secFuncExecute = $func['security_function_execute'];
//						if($secFuncExecute != ""){
//							$accessArray = getRoleAccess($controller, $secFuncExecute, $func['_execute'], $func['security_role_id'], $accessArray);
//						}
//
//					}
//
//
//				}
//
//				if(count($additionalParam) > 0){
//					if(isset($additionalParam['institution_id'])){
//						if(!in_array($additionalParam['institution_id'], $institutionIds)){
//							return 0;
//						}
//					}
//				}
//
//
//				//$permissions = session()->all();
//
//				$data['userId'] = $userId;
//				$data['super_admin'] = $super_admin;
//				$data['groupIds'] = $groupIds;
//				$data['roleIds'] = $roleIds;
//				$data['institutionIds'] = $institutionIds;
//				$data['permissions'] = $accessArray;
//
//				//For POCOR-8077 Start...
//				if($super_admin == 1){
//					$data['allowAllInstitutions'] = 1;
//				} else {
//					$data['allowAllInstitutions'] = $allowAllInstitutions??0;
//				}
//				//For POCOR-8077 End...
//				//$setSession = session(['Permissions' => $data]);
//				return $data;
//				//return true;
//			} catch (\Exception $e) {
//				Log::error(
//	                'Failed to set permissions in session.',
//	                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
//	            );
//	            return 0;
//			}
//		}
//	}
//
//	if(!function_exists('getRoleAccess')){
//		function getRoleAccess($controller, $accessType, $roleId, $accessArray, $action = 0)
//		{
//			$accessArr = explode("|", $accessType);
//
//			if(count($accessArr) > 1){
//
//				foreach($accessArr as $access){
//
//					$arr = explode(".", $access);
//					//dd($vAArr);
//					if(count($arr) > 1){
//
//						if($action == 1){
//							$accessArray[$controller][$arr[0]][$arr[1]][] = $roleId;
//						}
//
//					} else {
//						if($action == 1){
//							$accessArray[$controller][$arr[0]][] = $roleId;
//						}
//					}
//				}
//			} else {
//				$access = $accessArr[0];
//				$arr = explode(".", $access);
//				if(count($arr) > 1){
//					if($action == 1){
//						$accessArray[$controller][$arr[0]][$arr[1]][] = $roleId;
//					}
//				} else {
//					if($action == 1){
//						$accessArray[$controller][$arr[0]][] = $roleId;
//					}
//				}
//
//			}
//
//			return $accessArray;
//		}
//	}
//
//	if(!function_exists('checkPermission')){
//		function checkPermission($params = [], $additionalParams = []){
//			$loggedInUser = JWTAuth::user();
//
//			$permissions = checkAccess($params); //Fetching role and permissions.
//
//            if($loggedInUser['super_admin'] != 1){ //Checking if not admin.
//
//                if($permissions){
//                    if(isset($permissions['permissions'][$params[0]])){
//                    	if(isset($permissions['permissions'][$params[0]][$params[1]])){
//
//                    		if(isset($permissions['permissions'][$params[0]][$params[1]][$params[2]]) && isset($params[2])){
//
//
//                    			if(count($additionalParams) > 0) {
//                    				if(isset($additionalParams['institution_id'])){
//
//                    					//FOR POCOR-8077 Start...
//                    					if($permissions['allowAllInstitutions'] == 1){
//                    						return true;
//                    					}
//                    					//FOR POCOR-8077 End...
//
//
//
//                    					if(in_array($additionalParams['institution_id'], $permissions['institutionIds'])){
//                    						return true;
//                    					} else {
//                    						return false;
//                    					}
//
//                    				} else {
//                    					return false;
//                    				}
//                    			} else {
//                    				return true;
//                    			}
//                    		}
//                    	}
//                    }
//
//                    return false;
//                } else {
//
//                    return false;
//                }
//            } else {
//            	return true;
//            }
//		}
//	}
//


if (!function_exists('checkAccess')) {
    function checkAccess(): array|bool
    {
        try {
            $user = JWTAuth::user();
            if (!$user) return false;

            $userId = $user->id;
            $superAdmin = $user->super_admin ?? 0;

            // 🔹 Fetch security groups and roles in one query
            $securityData = DB::table('security_group_users')
                ->join('security_groups', 'security_groups.id', '=', 'security_group_users.security_group_id')
                ->leftJoin('security_group_institutions', 'security_group_institutions.security_group_id', '=', 'security_groups.id')
                ->where('security_group_users.security_user_id', $userId)
                ->select(
                    'security_group_users.security_group_id',
                    'security_group_users.security_role_id',
                    'security_group_institutions.institution_id'
                )
                ->get();

            // 🔹 Extract group IDs, role IDs, and institution IDs
            $groupIds = $securityData->pluck('security_group_id')->unique()->toArray();
            $roleIds = $securityData->pluck('security_role_id')->unique()->toArray();
            $institutionIds = $securityData->pluck('institution_id')->filter()->unique()->toArray();

            // 🔹 Fetch institution permissions for group areas
            $groupAreaInstitutions = getGroupAreaInstitutions($groupIds);
            $allowAllInstitutions = $groupAreaInstitutions['allowAllInstitutions'] ?? 0;
            $institutionIds = array_unique(array_merge($institutionIds, $groupAreaInstitutions['institutionIds'] ?? []));

            // 🔹 Fetch role-based permissions
            $roleFunctions = DB::table('security_role_functions')
                ->join('security_functions', 'security_functions.id', '=', 'security_role_functions.security_function_id')
                ->select(
                    'security_role_functions._view',
                    'security_role_functions._edit',
                    'security_role_functions._add',
                    'security_role_functions._delete',
                    'security_role_functions._execute',
                    'security_functions.controller',
                    'security_functions.module',
                    'security_functions._view as security_function_view',
                    'security_functions._edit as security_function_edit',
                    'security_functions._add as security_function_add',
                    'security_functions._delete as security_function_delete',
                    'security_functions._execute as security_function_execute'
                )
                ->whereIn('security_role_functions.security_role_id', $roleIds)
                ->where(function ($query) {
                    $query->where('security_role_functions._view', 1)
                        ->orWhere('security_role_functions._edit', 1)
                        ->orWhere('security_role_functions._add', 1)
                        ->orWhere('security_role_functions._delete', 1)
                        ->orWhere('security_role_functions._execute', 1);
                })
                ->get()->toArray();

            // 🔹 Process role permissions
            $accessArray = [];
            foreach ($roleFunctions as $func) {
                $func = (array)$func;
                foreach (['_view', '_edit', '_add', '_delete', '_execute'] as $perm) {
                    if ($func["$perm"]) {
                        $accessArray = getRoleAccess(
                            $func["controller"],
                            $func["security_function$perm"] ?? "",
                            $func["$perm"],
                            $accessArray
                        );
                    }
                }
            }

            // 🔹 If additional institution checks are needed


            // 🔹 Prepare final data structure
            $all_permissions = [
                'userId' => $userId,
                'super_admin' => $superAdmin,
                'groupIds' => $groupIds,
                'roleIds' => $roleIds,
                'institutionIds' => $institutionIds,
                'permissions' => $accessArray,
                'allowAllInstitutions' => $superAdmin ? 1 : $allowAllInstitutions
            ];
//            Log::info("Permissions: " . print_r($all_permissions,true));
            return $all_permissions;
        } catch (\Exception $e) {
            Log::error("Error in checkAccess: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getRoleAccess')) {
    function getRoleAccess(string $controller, string $accessType, int $roleId, array $accessArray): array
    {
        // 🔹 Convert permissions into an array
        $accessList = array_filter(array_map('trim', explode('|', $accessType)));

        foreach ($accessList as $access) {
            $parts = explode('.', $access);
            if (count($parts) > 1) {
                $accessArray[$controller][$parts[0]][$parts[1]][] = $roleId;
            } else {
                $accessArray[$controller][$parts[0]][] = $roleId;
            }
        }

        return $accessArray;
    }
}

if (!function_exists('checkPermission')) {
    function checkPermission(array $params, array $additionalParams = []): bool
    {

        $user = JWTAuth::user();

        // POCOR-8965
        // Super admin bypasses all checks
        if ($user['super_admin'] == 1) {
            return true;
        }
        // Cache key based on user ID
        $cacheKey = "user_permissions_{$user->id}";

        // Cache permissions for a certain time (e.g., 10 minutes)
        $permissions = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return checkAccess();
        });

        // POCOR-8965 start
        // If institution ID is required, check it
        $institutionId = $params['institution_id'] ?? ($additionalParams['institution_id'] ?? null);
        if (!empty($institutionId)) {

            $permission = $permissions['allowAllInstitutions'] == 1 || in_array($institutionId, $permissions['institutionIds']);
            if(!$permission){
                return false;
            }
        }
        // POCOR-8965 end

        if (!$permissions || !isset($permissions['permissions'][$params[0]][$params[1]][$params[2]])) {
            return false;
        }

        return true;
    }
}

if (!function_exists('getGroupAreaInstitutions')) {
    function getGroupAreaInstitutions(array $groupIds): array
    {
        try {
            if (empty($groupIds)) return ['allowAllInstitutions' => 0, 'institutionIds' => []];

            $groupAreas = DB::table('security_group_areas')
                ->whereIn('security_group_id', $groupIds)
                ->pluck('area_id')
                ->toArray();

            if (in_array(1, $groupAreas, true)) {
                return ['allowAllInstitutions' => 1, 'institutionIds' => []];
            }

            $areaIds = getChildrenIdFromDb($groupAreas);

            $institutionIds = DB::table('institutions')
                ->whereIn('area_id', $areaIds)
                ->pluck('id')
                ->toArray();

            return ['allowAllInstitutions' => 0, 'institutionIds' => $institutionIds];
        } catch (\Exception $e) {
            Log::error("Error in getGroupAreaInstitutions: " . $e->getMessage());
            return ['allowAllInstitutions' => 0, 'institutionIds' => []];
        }
    }
}

if (!function_exists('getChildrenIdFromDb')) {
    function getChildrenIdFromDb(array $parentAreaIds): array
    {
        $areaIds = $parentAreaIds;

        do {
            $newAreas = DB::table('areas')
                ->whereIn('parent_id', $areaIds)
                ->pluck('id')
                ->toArray();

            $newCount = count($newAreas);
            $areaIds = array_merge($areaIds, $newAreas);
        } while ($newCount > 0);

        return array_unique($areaIds);
    }
}
// POCOR-8915 end

if(!function_exists('removeNonColumnFields')){
    function removeNonColumnFields($params = [], $table = ""){
        try {
            $cols = Schema::getColumnListing($table);

            $values = [];
            if(count($cols) > 0){
                foreach ($params as $key => $param) {
                    if(in_array($key, $cols)){
                        $values[$key] = $param;
                    }
                }
            } else {
                $values = $params;
            }
            return $values;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get columns listing from helper funtion.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return false;
        }
    }
}



if(!function_exists('paramsEncode')){
    function paramsEncode($params = []){
        try {
            $session_id = \Session::getId();



            $sessionId = hashing('session_id', 'sha256');

            $jsonParam = json_encode($params);

            $base64Param = urlsafeB64Encode($jsonParam);

            $params[$sessionId] = $session_id??"";
            $jsonParamWithSessionTocken = json_encode($params);
            $signature = hashing($jsonParamWithSessionTocken, 'sha256', true);
            $base64Signature = urlsafeB64Encode($signature);
            return "$base64Param.$base64Signature";
        } catch (\Exception $e) {
            Log::error(
                'Failed to generate URL dats from helper funtion.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return false;
        }
    }
}


if(!function_exists('urlsafeB64Encode')){
    function urlsafeB64Encode($input){
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
}


if(!function_exists('hashing')){
    function hashing($string, $type = null, $salt = false){

        if (empty($type)) {
            $type = 'sha1';
        }
        $type = strtolower($type);

        if ($salt) {
            if (!is_string($salt)) {
                $salt = config('constantvalues.SALT');
            }
            $string = $salt . $string;
        }

        return hash($type, $string);
    }
}


// POCOR-8915 start
//For POCOR-8077 Start...
//	if(!function_exists('getGroupAreaInstitutions')){
//		function getGroupAreaInstitutions($groupIds){
//			try {
//				$resp = [];
//				$groupAreas = [];
//				$areas = [];
//				$areaIdArray = [];
//				$allowAllInstitutions = 0;
//				if(!empty($groupIds)){
//					$groupAreas = SecurityGroupAreas::whereIn('security_group_id', $groupIds)->pluck('area_id')->toArray();
//
//				}
//
//				if(!empty($groupAreas)){
//
//					if(in_array(1, $groupAreas)){ //1 for all areas...
//						$allowAllInstitutions = 1;
//					}
//					//$allowAllInstitutions = 1;
//					if($allowAllInstitutions == 1){
//						$resp['allowAllInstitutions'] = $allowAllInstitutions;
//						$resp['institutionIds'] = [];
//						return $resp;
//					}
//
//					$allAreas = Areas::select('id', 'parent_id')->with('allChildren:id,parent_id')->whereIn('id', $groupAreas)->get()->toArray();
//
//					getChildrenId($allAreas, $areaIdArray);
//
//					if(!empty($areaIdArray)){
//						$institutionIds = Institutions::whereIn('area_id', $areaIdArray)->pluck('id')->toArray();
//						$resp['allowAllInstitutions'] = 0;
//						$resp['institutionIds'] = $institutionIds;
//
//					}
//
//				}
//				return $resp;
//			} catch (\Exception $e) {
//				return false;
//			}
//
//		}
//	}
//
//
//	if(!function_exists('getChildrenId')){
//		function getChildrenId($array, &$result)
//		{
//		    foreach ($array as $item) {
//		        $result[] = $item['id'];
//		        if (!empty($item['all_children'])) {
//		            getChildrenId($item['all_children'], $result);
//		        }
//		    }
//		}
//	}

//For POCOR-8077 End...
// POCOR-8915 end

//For POCOR-8104 Start...
if(!function_exists('getNewOpenemisNo')){
    function getNewOpenemisNo()
    {
        $configItem = ConfigItem::where('code', 'openemis_id_prefix')->first();
        if($configItem){
            $value = $configItem->value;
            $prefix = explode(",", $value);
            if($prefix[1] > 0){
                $prefix = $prefix[1];
            } else {
                $prefix = '';
            }

            $latest = SecurityUsers::orderBy('id', 'DESC')->first();
            $latestOpenemisNo = $latest->openemis_no;


            if (empty($prefix)) {
                $latestDbStamp = $latestOpenemisNo;
            } else {
                $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
            }

            $latestOpenemisNoLastValue = substr($latestOpenemisNo, -1);


            $currentStamp = time();
            if ($latestDbStamp <= $currentStamp && is_numeric($latestOpenemisNoLastValue)) {
                $newStamp = $latestDbStamp + 1;
            } else {
                $newStamp = $currentStamp;
            }
            $newOpenemisNo = $prefix.$newStamp;

            $resultOpenemisTemp = OpenemisTemp::orderBy('id', 'DESC')->first();

            if(strlen($resultOpenemisTemp->openemis_no) < 5){
                $resultOpenemisTemp = SecurityUsers::orderBy('id', 'DESC')->first();
            }

            $resultOpenemisNoTemp = substr($resultOpenemisTemp->openemis_no, strlen($prefix));

            $newOpenemisNo = $resultOpenemisNoTemp+1;
            $newOpenemisNo=$prefix.$newOpenemisNo;

            $resultOpenemisTemps = OpenemisTemp::where('openemis_no', $newOpenemisNo)->first();

            if(empty($resultOpenemisTemps->openemis_no)){
                $storeOpenemisTemp = OpenemisTemp::insert([
                    'openemis_no' => $newOpenemisNo,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'created' => Carbon::now()->toDateTimeString()
                ]);
            }

            return $newOpenemisNo;
        }
    }
}


//For POCOR-8104 End...


//For POCOR-8205 Start...
if(!function_exists('currentAcademicYear')){
    function currentAcademicYear()
    {
        $currentAcademicYear = AcademicPeriod::where("current", 1)->first()->toArray();

        return $currentAcademicYear;
    }
}
//For POCOR-8208 End...


//For POCOR-8348 Start...
if(!function_exists('getClassStudents')){
    function getClassStudents($institution_id, $institution_class_id)
    {
        $getClassStudents = getInstutionClassStudentData($institution_id, $institution_class_id);

        $resp = [];
        foreach($getClassStudents as $k => $student){
            $resp[$k]['Name'] = $student['first_name']. ' '.$student['last_name'];
            $resp[$k]['OpenEMIS ID'] = $student['openemis_no'];
        }
        return $resp;
    }
}


if(!function_exists('getMealProgrammes')){
    function getMealProgrammes()
    {
        $currentAcademicYear = AcademicPeriod::where('current', 1)->first();
        $getMealProgrammes = MealProgrammes::where('academic_period_id', $currentAcademicYear->id??0)->get()->toArray();

        $resp = [];
        foreach($getMealProgrammes as $k => $mealProgramme){
            $resp[$k]['Name'] = $mealProgramme['name'];
            $resp[$k]['Code'] = $mealProgramme['code'];
        }
        return $resp;
    }
}

if(!function_exists('getMealReceived')){
    function getMealReceived()
    {
        $getMealReceived = MealReceived::get()->toArray();

        $resp = [];
        foreach($getMealReceived as $k => $mealReceived){
            $resp[$k]['Name'] = $mealReceived['name'];
            $resp[$k]['Code'] = $mealReceived['code'];
        }
        return $resp;
    }
}

if(!function_exists('getMealBenefits')){
    function getMealBenefits()
    {
        $getMealBenefits = MealBenefits::where('visible', 1)->orderBy('order', 'ASC')->get()->toArray();

        $resp = [];
        foreach($getMealBenefits as $k => $mealReceived){
            $resp[$k]['Name'] = $mealReceived['name'];
            $resp[$k]['Id'] = $mealReceived['id'];
        }
        return $resp;
    }
}


if(!function_exists('getStudentAttendanceType')){
    function getStudentAttendanceType()
    {
        $getStudentAttendanceType = StudentAttendanceType::get()->toArray();

        $resp = [];
        foreach($getStudentAttendanceType as $k => $attendanceType){
            $resp[$k]['Name'] = $attendanceType['name'];
            $resp[$k]['Code'] = $attendanceType['code'];
        }
        return $resp;
    }
}


if(!function_exists('getNumberOfPeriods')){
    function getNumberOfPeriods()
    {
        $resp[] = [
            'Number Of Periods' => "Period 1",
            'Id' => "1",
        ];

        return $resp;
    }
}


if(!function_exists('getInstutionClassSubject')){
    function getInstutionClassSubject($institution_id, $institution_class_id)
    {
        $getInstutionClassSubject = InstitutionClassSubjects::select('institution_subjects.*')
            ->join('institution_subjects', 'institution_subjects.id', '=', 'institution_class_subjects.institution_subject_id')
            ->where('institution_class_subjects.institution_class_id', $institution_class_id)
            ->get()
            ->toArray();

        $resp = [];
        foreach($getInstutionClassSubject as $k => $subject){
            $resp[$k]['Subject'] = $subject['name'];
            $resp[$k]['Id'] = $subject['id'];
        }
        return $resp;
    }
}


if(!function_exists('getInstutionClassStudent')){
    function getInstutionClassStudent($institution_id, $institution_class_id)
    {
        $results = getInstutionClassStudentData($institution_id, $institution_class_id);
        $resp = [];
        foreach($results as $k => $result){
            $resp[$k]['Institution'] = $result['institution_name'];
            $resp[$k]['Academic Period'] = $result['academic_period_year'];
            $resp[$k]['Education Grade'] = $result['education_grade_name'];
            $resp[$k]['Name'] = $result['first_name']. " ".$result['last_name'];
            $resp[$k]['OpenEMIS ID'] = $result['openemis_no'];
        }
        return $resp;
    }
}


if(!function_exists('getInstutionClassStudentData')){
    function getInstutionClassStudentData($institution_id, $institution_class_id)
    {
        $getClassStudents = InstitutionClassStudents::select(
            'security_users.first_name',
            'security_users.last_name',
            'security_users.openemis_no',
            'academic_periods.name as academic_period_year',
            'education_grades.name as education_grade_name',
            'institutions.name as institution_name',
        )
            ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
            ->join('academic_periods', 'academic_periods.id', '=', 'institution_class_students.academic_period_id')
            ->join('education_grades', 'education_grades.id', '=', 'institution_class_students.education_grade_id')
            ->join('institutions', 'institutions.id', '=', 'institution_class_students.institution_id')
            ->where("institution_id", $institution_id)
            ->where('institution_class_id', $institution_class_id)
            ->get()
            ->toArray();
        return $getClassStudents;
    }
}


if(!function_exists('getAbsenceTypes')){
    function getAbsenceTypes()
    {
        $getAbsenceTypes = AbsenceTypes::get()->toArray();
        $resp = [];

        foreach ($getAbsenceTypes as $key => $absenceType) {
            $resp[$key]['Name'] = $absenceType['name'];
            $resp[$key]['Code'] = $absenceType['code'];
        }

        return $resp;
    }
}


if(!function_exists('getStudentAbsenceReason')){
    function getStudentAbsenceReason()
    {
        $getStudentAbsenceReason = StudentAbsenceReason::get()->toArray();
        $resp = [];

        foreach ($getStudentAbsenceReason as $key => $studentAbsenceReason) {
            $resp[$key]['Name'] = $studentAbsenceReason['name'];
            $resp[$key]['National Code'] = $studentAbsenceReason['id'];
        }

        return $resp;
    }
}
//For POCOR-8348 End...


//For POCOR-7429 Start...
if(!function_exists('getPrimaryKey')){
    function getPrimaryKey($table)
    {
        try {
            $cols = Schema::getColumnListing($table);
            $primaryKey = "";

            foreach ($cols as  $col) {
                //dd($table,$col);
                $columnType = \Schema::getColumnType($table, $col, true);
                dd("columnType: ", $columnType);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }
}
//For POCOR-7429 End...


