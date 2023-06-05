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


    public function educationGradesList(Request $request)
    {
        try {
            $data = $this->registrationService->educationGradesList($request);
            
            return $this->sendSuccessResponse("Education Grade List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
        }
    }


    public function institutionDropdown(Request $request)
    {
        try {
            $data = $this->registrationService->institutionDropdown($request);
            
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
            
            return $this->sendSuccessResponse("Areas List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
        }
    }


    public function generateOtp(GenerateOtpRequest $request)
    {
        try {
            $resp = $this->registrationService->generateOtp($request);
            if($resp == 1){
                return $this->sendSuccessResponse("Otp sent successfully.", $resp);
            } elseif ($resp == 2) {
                return $this->sendErrorResponse("Email not registered.");
            } else {
                return $this->sendErrorResponse("Failed to sent otp on email.");
            }
            
            
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


    public function autocompleteIdentityNo($identityTypeId, $identityNumber)
    {
        try {
            $data = $this->registrationService->autocompleteIdentityNo($identityTypeId, $identityNumber);

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
            }elseif($resp == 7){
                return $this->sendErrorResponse("Invalid OTP.");
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


    public function getStudentCustomFields()
    {
        try {
            $data = $this->registrationService->getStudentCustomFields();

            return $this->sendSuccessResponse("Custom fields list found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find custom fields list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find custom fields list.');
        }
    }


    public function identityTypeList()
    {
        try {
            $data = $this->registrationService->identityTypeList();

            return $this->sendSuccessResponse("Identity type list found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find identity type list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find identity type list.');
        }
    }



    public function getInstitutionGradesList(Request $request, $gradeId)
    {
        try {
            $data = $this->registrationService->getInstitutionGradesList($request, $gradeId);
            
            return $this->sendSuccessResponse("Institutions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function institutionTypesDropdown()
    {
        try {
            $data = $this->registrationService->institutionTypesDropdown();
            
            return $this->sendSuccessResponse("Institution Types List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Types List Not Found');
        }
    }


    public function areaLevelsDropdown()
    {
        try {
            $data = $this->registrationService->areaLevelsDropdown();
            
            return $this->sendSuccessResponse("Area Levels List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Levels List Not Found');
        }
    }


    public function areasDropdown(Request $request)
    {
        try {
            $data = $this->registrationService->areasDropdown($request);
            
            return $this->sendSuccessResponse("Area Names List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Names List Not Found');
        }
    }
}
