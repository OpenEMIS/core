<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RegistrationService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\GenerateOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\InstitutionStudentStoreRequest;

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
            return $this->sendSuccessResponse("Otp sent successfully.", $resp);
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to sent otp on email.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to sent otp on email.');
        }
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            $resp = $this->registrationService->verifyOtp($request);

            if($resp == 1){
                return $this->sendSuccessResponse("OTP verified.");
            } elseif($resp == 2) {
                return $this->sendSuccessResponse("Invalid OTP.");
            } elseif($resp == 0){
                return $this->sendSuccessResponse("OTP expired.");
            } else {
                return $this->sendSuccessResponse("OTP not verified.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to verify otp.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to verify otp.');
        }
    }


    public function autocompleteOpenemisNo($id)
    {
        try {
            $data = $this->registrationService->autocompleteOpenemisNo($id);

            return $this->sendSuccessResponse("Candidate data found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function autocompleteIdentityNo($id)
    {
        try {
            $data = $this->registrationService->autocompleteIdentityNo($id);

            return $this->sendSuccessResponse("Candidate data found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function detailsByEmis($id)
    {
        try {
            $data = $this->registrationService->detailsByEmis($id);

            return $this->sendSuccessResponse("Candidate data found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function nationalityList()
    {
        try {
            $data = $this->registrationService->nationalityList();

            return $this->sendSuccessResponse("Nationality list found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find nationality list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find nationality list.');
        }
    }



    public function institutionStudents(InstitutionStudentStoreRequest $request)
    {
        try {
            $resp = $this->registrationService->institutionStudents($request);

            if($resp == 1){
                return $this->sendSuccessResponse("Registration successful. We will contact you shortly.");
            }elseif($resp == 2){
                return $this->sendErrorResponse("Student details do not match.");
            }elseif($resp == 3){
                return $this->sendErrorResponse("Openemis number not found.");
            }elseif($resp == 4){
                return $this->sendErrorResponse("Student already enrolled.");
            }elseif($resp == 5){
                return $this->sendErrorResponse("Identity number not found.");
            }elseif($resp == 6){
                return $this->sendErrorResponse("Not able to create new student.");
            }else{
                return $this->sendErrorResponse("Something went wrong.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to register student.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to register student.');
        }
    }
}
