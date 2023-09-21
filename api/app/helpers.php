<?php
	
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Areas;
use App\Models\SecurityGroupUsers;


if(!function_exists('checkAccess')){
	function checkAccess($additionalParam = [])
	{
		try {
			$user = JWTAuth::user();
			$userId = $user->id;
			$userId = 8813;
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

			dd($roleIds);

		} catch (\Exception $e) {
			dd($e);
			Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return 0;
		}
	}
		
}
