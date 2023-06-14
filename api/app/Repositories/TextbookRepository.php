<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\TextbookConditions;
use App\Models\Textbooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;


class TextbookRepository extends Controller
{

    public function getTextbookCondition(){
        
        try {
            $data = TextbookConditions::get();
            return $this->sendSuccessResponse("Textbook condition List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook condition List Not Found');
        }
    }

    public function getTextbookByID($id){
        
        try {
            $data = Textbooks::where('id', $id)->first();
            return $this->sendSuccessResponse("Textbook Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Not Found');
        }
    }


}