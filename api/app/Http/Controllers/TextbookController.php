<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TextbookService;

class TextbookController extends Controller
{

    protected $textbookService;

    public function __construct(TextbookService $textbookService) {
        $this->textbookService = $textbookService;
    }

    public function getTextbookConditions(){
    
            try {
                $data = $this->textbookService->getTextbookConditions();
                return $this->sendSuccessResponse("Textbook Conditions Found", $data);
                
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
            $data = $this->textbookService->getTextbookByID($id);
            return $this->sendSuccessResponse("Textbook Found", $data);
            
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
            $data = $this->textbookService->getTextbookStatuses();
            return $this->sendSuccessResponse("Textbook Statuses Found", $data);
            
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
            $data = $this->textbookService->getTextbookDimensions();
            return $this->sendSuccessResponse("Textbook Dimensions Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Dimensions Not Found');
        }
    }


}