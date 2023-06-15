<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\TextbookConditions;
use App\Models\TextbookDimensions;
use App\Models\Textbooks;
use App\Models\TextbookStatuses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;


class TextbookRepository extends Controller
{

    public function getTextbookConditions(){
        
        try {
            $data = TextbookConditions::get();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Conditions Not Found');
        }
    }

    public function getTextbookByID($id){
        
        try {
            $data = Textbooks::where('id', $id)->first();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Not Found');
        }
    }

    public function getTextbookStatuses(){
        
        try {
            $data = TextbookStatuses::get();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Statuses Not Found');
        }
    }

    public function getTextbookDimensions(){
        
        try {
            $data = TextbookDimensions::get();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Dimensions Not Found');
        }
    }


}