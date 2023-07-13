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

    public function addTextbooks($request){
        try {
            $data = $this->textbookRepository->addTextbooks($request);
            
            return $data;

        }
        catch(\Exception $e) {
            Log::error(
                'Failed to add Textbook.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add Textbook.');
        }
    }

    public function getInstitutionTextbookdata(int $institutionId, int $textbookId)
    {
        try {
            $data = $this->textbookRepository->getInstitutionTextbookdata($institutionId, $textbookId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Textbook Data Not Found');
        }
    }

    public function addInstitutionTextbooks($request, $institutionId)
    {
        try {
            $data = $this->textbookRepository->addInstitutionTextbooks($request, $institutionId);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add Institution Textbook.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add Institution Textbook.');
        }
    }

}
