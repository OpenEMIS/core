<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\SurveyForms;
use App\Models\InstitutionSurveys;

class SurveyRepository extends Controller
{
    public function getSurveys($request)
    {
        try {
            $params = $request->all();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $surveys = InstitutionSurveys::with('surveyForms','surveyForms.customModule');

            if(isset($params['institution_id'])){
                $surveys = $surveys->where('institution_id', $params['institution_id']);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $surveys = $surveys->orderBy($col, $orderBy);
            }

            $list = $surveys->paginate($limit)->toArray();
            
            return $list;
        } catch (\Exception $e) {
            
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }
}

