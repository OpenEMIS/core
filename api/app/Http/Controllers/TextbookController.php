<?php

namespace App\Http\Controllers;

use App\Http\Requests\TextbookAddRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TextbookService;
use App\Http\Requests\InstitutionTextbookAddRequest;

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

    public function addTextbooks(TextbookAddRequest $request){
        
        try {
            $data = $this->textbookService->addTextbooks($request);
            if($data == 1){
                return $this->sendSuccessResponse("Textbook Added successfully.");
            } else {
                return $this->sendErrorResponse("Textbook not Added successfully.");
            }

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
            $data = $this->textbookService->getInstitutionTextbookdata($institutionId, $textbookId);
            
            return $this->sendSuccessResponse("Institution Textbook Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Textbook Data Not Found');
        }
    }

    public function addInstitutionTextbooks(InstitutionTextbookAddRequest $request, int $institutionId)
    {
        try {

            $data = $this->textbookService->addInstitutionTextbooks($request, $institutionId);
            if($data == 1){
                return $this->sendSuccessResponse("Institution Textbook added successfully.");
            } else {
                return $this->sendErrorResponse("Institution Textbook not added successfully.");
            }
        }
        catch(\Exception $e) {
            Log::error(
                'Failed to add Institution Textbook',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add Institution Textbook.');

        }
    }


}