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

    public function getTextbookCondition(){
    
            try {
                $data = $this->textbookService->getTextbookCondition();
                return $this->sendSuccessResponse("Textbook condition List Found", $data);
                
            } catch (\Exception $e) {
                Log::error(
                    'Failed to fetch list from DB',
                    ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
                );
    
                return $this->sendErrorResponse('Textbook condition  List Not Found');
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

}