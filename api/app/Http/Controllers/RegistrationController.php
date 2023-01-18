<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RegistrationService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\GenerateOtpRequest;

class RegistrationController extends Controller
{
    
    protected $registrationService;

    public function __construct(
        RegistrationService $registrationService
    ) {
        $this->registrationService = $registrationService;
    }

    public function academicPeriodsList()
    {
        try {
            $data = $this->registrationService->academicPeriodsList();
            
            return $this->sendSuccessResponse("Academic Period List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Academic Period List Not Found');
        }
    }


    public function educationGradesList()
    {
        try {
            $data = $this->registrationService->educationGradesList();
            
            return $this->sendSuccessResponse("Education Grade List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
        }
    }


    public function institutionDropdown()
    {
        try {
            $data = $this->registrationService->institutionDropdown();
            
            return $this->sendSuccessResponse("Institutions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function administrativeAreasList()
    {
        try {
            $data = $this->registrationService->administrativeAreasList();
            
            return $this->sendSuccessResponse("Administrative Areas List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Administrative Areas List Not Found');
        }
    }


    public function generateOtp(GenerateOtpRequest $request)
    {
        try {
            $resp = $this->registrationService->generateOtp($request);


            if($resp == 0){
                return $this->sendSuccessResponse("Email is not registered.", $resp);
            } else {
                return $this->sendSuccessResponse("Otp sent successfully.", $resp);
            }
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to sent otp on email.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to sent otp on email.');
        }
    }
}
