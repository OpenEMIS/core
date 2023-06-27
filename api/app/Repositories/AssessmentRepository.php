<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AssessmentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;

class AssessmentRepository extends Controller
{

    public function getAssessmentItemList($request)
    {
        try {

            // $params = $request->all();

            //     $limit = config('constants.defaultPaginateLimit');

            //     if(isset($params['limit'])){
            //     $limit = $params['limit'];
            //     }
            //     $assessmentItem = new AssessmentItem();
            //     $list = $assessmentItem->paginate($limit);
            //     return $list;

            $assessmentItem = AssessmentItem::limit(5)->get();
            // dd($assessmentItem);
            return $assessmentItem;
        
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Item List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);

            return $this->sendErrorResponse('Failed to get Assessment Item List.');
        }
    }

}