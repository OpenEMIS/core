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

    
    public function getTextbookConditions(){
    
        try {
            $data = $this->textbookRepository->getTextbookConditions();
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
            $data = $this->textbookRepository->getTextbookByID($id);
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
            $data = $this->textbookRepository->getTextbookStatuses();
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
            $data = $this->textbookRepository->getTextbookDimensions();
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
