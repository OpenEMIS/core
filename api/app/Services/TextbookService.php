<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\TextbookRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class TextbookService extends Controller
{

    protected $textbookRepository;

    public function __construct(TextbookRepository $textbookRepository) {
        $this->textbookRepository = $textbookRepository;
    }

    
    public function getTextbookCondition(){
    
        try {
            $data = $this->textbookRepository->getTextbookCondition();
            return $this->sendSuccessResponse("Textbook condition  List Found", $data);
            
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
            $data = $this->textbookRepository->getTextbookByID($id);
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
