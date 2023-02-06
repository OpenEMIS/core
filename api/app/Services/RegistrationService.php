<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\RegistrationRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class RegistrationService extends Controller
{

    protected $registrationRepository;

    public function __construct(
    RegistrationRepository $registrationRepository) {
        $this->registrationRepository = $registrationRepository;
    }

    public function academicPeriodsList()
    {
        try {
            $data = $this->registrationRepository->academicPeriodsList()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name
                    ];
                }
            );
            
            return $data;
            
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
            $data = $this->registrationRepository->educationGradesList()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->educaiton_grade_id,
                        "name" => $item->educaiton_grade_name
                    ];
                }
            );
            
            return $data;
            
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
            $data = $this->registrationRepository->institutionDropdown()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->code.' - '.$item->name,
                    ];
                }
            );
            
            return $data;
            
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
            $data = $this->registrationRepository->administrativeAreasList();
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Administrative Areas List Not Found');
        }
    }


    public function generateOtp($request)
    {
        try {
            $data = $this->registrationRepository->generateOtp($request);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to sent otp on email.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to sent otp on email.');
        }
    }


    public function verifyOtp($request)
    {
        try {
            $data = $this->registrationRepository->verifyOtp($request);
            
            return $data;
            
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
            $data = $this->registrationRepository->autocompleteOpenemisNo($id);
            
            return $data;
            
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
            $data = $this->registrationRepository->autocompleteIdentityNo($id);

            return $data;
            
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
            $data = $this->registrationRepository->detailsByEmis($id)
                ->map(function ($item, $key) {
                    return [
                        "openemis_no" => $item['openemis_no'],
                        "first_name" => $item['first_name'],
                        "middle_name" => $item['middle_name'],
                        "third_name" => $item['third_name'],
                        "last_name" => $item['last_name'],
                        "preferred_name" => $item['preferred_name'],
                        "email" => $item['email'],
                        "address" => $item['address'],
                        "postal_code" => $item['postal_code'],
                        "address_area_id" => $item['address_area_id'],
                        "birthplace_area_id" => $item['birthplace_area_id'],
                        "identity_number" => $item['identity_number'],
                        "gender_id" => [
                            "key" => $item["gender"]["id"],
                            "value" => $item["gender"]["name"],
                        ],
                        "date_of_birth" => $item['date_of_birth'],
                        "nationality_id" => [
                            "key" => (!empty($item["nationality"]["id"]))?$item["nationality"]["id"]:'',
                            "value" => (!empty($item["nationality"]["name"]))?$item["nationality"]["name"]:'',
                        ],
                        "institution" => [
                            "key" => (!empty($item["institutionStudent"]["institution"]["id"]))?$item["institutionStudent"]["institution"]["id"]:'',
                            "value" => (!empty($item["institutionStudent"]["institution"]["name"]))?$item["institutionStudent"]["institution"]["name"]:'',
                        ],
                    ];
                });
            
            return $data;
            
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
            $data = $this->registrationRepository->nationalityList()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name,
                    ];
                }
            );
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to find nationality list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find nationality list.');
        }
    }


    public function institutionStudents($request)
    {
        try {
            $data = $this->registrationRepository->institutionStudents($request);

            return $data;
            
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
            $data = $this->registrationRepository->getStudentCustomFields()->map(
                function ($item, $key) {
                    
                    return [
                        "student_custom_form_id" => $item->student_custom_form_id,
                        "student_custom_field_id" => $item->student_custom_field_id,
                        "section" => $item->section,
                        "name" => $item->name,
                        "is_mandatory" => $item->is_mandatory,
                        "is_unique" => $item->is_unique,
                        "order" => $item->order,
                        "params" => $item->studentCustomField->params??Null,
                        "field_type" => $item->studentCustomField->field_type??Null,
                        "description" => $item->studentCustomField->description??Null
                    ];
                }
            );

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find custom fields list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find custom fields list.');
        }
    }

}
