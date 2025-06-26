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

    public function academicPeriodsList($params)
    {
        try {
            $data = $this->registrationRepository->academicPeriodsList($params);

            $resp = [];
            if(count($data) > 0){
                $academicPeriodYears = array_merge($data['current_academic_year'], $data['rest_academic_year']);


                foreach($academicPeriodYears as $k => $year){
                    $resp[$k]['id'] = $year['id'];
                    $resp[$k]['name'] = $year['name'];
                } 
            }
            
            
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Academic Period List Not Found');
        }
    }



    public function educationGradesList($request)
    {
        try {
            $data = $this->registrationRepository->educationGradesList($request);

            if ($request['limit']) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->educaiton_grade_id,
                        "name" => $item->educaiton_grade_name
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->educaiton_grade_id,
                            "name" => $item->educaiton_grade_name
                        ];
                    }
                );
            }

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
        }
    }


    public function institutionDropdown($params)
    {
        try {
            $data = $this->registrationRepository->institutionDropdown($params);

            $resp = [];
            if(!empty($data)){
                foreach ($data['data'] as $k => $d) {
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['code'].' - '.$d['name'];
                }
            }

            //For POCOR-8215/8216 start...          
            if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data;
            } else {
                return $resp;
            }
            //For POCOR-8215/8216 end...

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function administrativeAreasList($params)
    {
        try {
            $data = $this->registrationRepository->administrativeAreasList($params);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
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


    public function autocompleteOpenemisNo($params, $id)
    {
        try {
            $data = $this->registrationRepository->autocompleteOpenemisNo($params, $id);
            $resp = [];

            foreach ($data['data'] as $key => $d) {
                $resp[$key]['key'] = $d['key'];
                $resp[$key]['value'] = $d['value'];
            }
            

            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data;
            } else {
                return $resp;
            }
            //For POCOR-8215/8216 end...

            
            
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
            $data = $this->registrationRepository->autocompleteIdentityNo($identityTypeId, $identityNumber);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function detailsByEmis($params, $id)
    {
        try {
            $data = $this->registrationRepository->detailsByEmis($params, $id);

            $resp = [];
            if(!empty($data)){
                foreach ($data['data'] as $k => $item) {
                    $resp[$k] = [
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
                                "key" => (!empty($item["institution_student"]["institution"]["id"]))?$item["institution_student"]["institution"]["id"]:'',
                            "value" => (!empty($item["institution_student"]["institution"]["name"]))?$item["institution_student"]["institution"]["name"]:'',
                        ],
                    ];
                }
            }
            
            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data;
            } else {
                return $resp;
            }
            //For POCOR-8215/8216 end...

        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function nationalityList($params)
    {
        try {
            $data = $this->registrationRepository->nationalityList($params);

            if (isset($params['limit'])) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name,
                        "is_refugee" => $item->is_refugee,
                        "national_code" => $item->national_code,
                        "international_code" => $item->international_code,
                        "modified_user_id" => $item->modified_user_id,
                        "modified" => $item->modified,
                        "created_user_id" => $item->created_user_id,
                        "created" => $item->created,
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->id,
                            "name" => $item->name,
                            "is_refugee" => $item->is_refugee,
                            "national_code" => $item->national_code,
                            "international_code" => $item->international_code,
                            "modified_user_id" => $item->modified_user_id,
                            "modified" => $item->modified,
                            "created_user_id" => $item->created_user_id,
                            "created" => $item->created,
                        ];
                    }
                );
            }
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


    public function getStudentCustomFields($params)
    {
        try {
            $data = $this->registrationRepository->getStudentCustomFields($params);
            $resp = [];
            
            foreach($data['data'] as $k => $d){
                //dd($d);
                $section = $d['section'];
                $arr['student_custom_form_id'] = $d['student_custom_form_id'];
                $arr['student_custom_field_id'] = $d['student_custom_field_id'];
                $arr['section'] = $d['section'];
                $arr['name'] = $d['name'];
                $arr['is_mandatory'] = $d['is_mandatory'];
                $arr['is_unique'] = $d['is_unique'];
                $arr['order'] = $d['order'];
                $arr['is_unique'] = $d['is_unique'];
                //$arr['params'] = $d['studentCustomField']['params']??Null;
                $arr['params'] = $d['student_custom_field']['params']??Null;
                //$arr['field_type'] = $d['studentCustomField']['field_type']??Null;
                $arr['field_type'] = $d['student_custom_field']['field_type']??Null;
                //$arr['options'] = $d['studentCustomField']['studentCustomFieldOption']??Null;
                $arr['options'] = $d['student_custom_field']['student_custom_field_option']??Null;
                $arr['description'] = $d['student_custom_field']['description']??Null;


                $resp[$section][] = $arr;
            }

            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data;
                
            } else {
                return $resp;
            }
            //For POCOR-8215/8216 end...

        } catch (\Exception $e) {
            Log::error(
                'Failed to find custom fields list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find custom fields list.');
        }
    }


    public function identityTypeList($params)
    {
        try {
            $data = $this->registrationRepository->identityTypeList($params);

            if (isset($params['limit'])) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name,
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->id,
                            "name" => $item->name,
                        ];
                    }
                );
            }
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to find identity type list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find identity type list.');
        }
    }


    public function getInstitutionGradesList($request, $gradeId)
    {
        try {
            $data = $this->registrationRepository->getInstitutionGradesList($request, $gradeId);

            if ($request['limit']) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name.' ('.$item->code.')',
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->id,
                            "name" => $item->name.' ('.$item->code.')',
                        ];
                    }
                );
            }

            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function institutionTypesDropdown($params)
    {
        try {
            $data = $this->registrationRepository->institutionTypesDropdown($params);

            $resp = [];
            if(!empty($data)){
                foreach ($data['data'] as $k => $d) {
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                }
            }


            //For POCOR-8215/8216 start...
            if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data;
                
            } else {
                return $data;
            }
            //For POCOR-8215/8216 end...
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Types List Not Found');
        }
    }


    public function areaLevelsDropdown($params)
    {
        try {
            $data = $this->registrationRepository->areaLevelsDropdown($params);

            if (isset($params['limit'])) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name,
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->id,
                            "name" => $item->name,
                        ];
                    }
                );
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Levels List Not Found');
        }
    }


    public function areasDropdown($request)
    {
        try {
            $data = $this->registrationRepository->areasDropdown($request);

            if (isset($request['limit'])) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name,
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->id,
                            "name" => $item->name,
                        ];
                    }
                );
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Names List Not Found');
        }
    }



    public function areaAdministrativeLevelsDropdown($params)
    {
        try {
            $data = $this->registrationRepository->areaAdministrativeLevelsDropdown($params);

            if (isset($params['limit'])) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name,
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->id,
                            "name" => $item->name,
                        ];
                    }
                );
            }

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administrative Levels List Not Found');
        }
    }


    public function areasAdministrativeDropdown($request)
    {
        try {
            $data = $this->registrationRepository->areasAdministrativeDropdown($request);

            if (isset($request['limit'])) {
                $data->getCollection()->transform(function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name,
                    ];
                });
            } else {
                $data = $data->map(
                    function ($item, $key) {
                        return [
                            "id" => $item->id,
                            "name" => $item->name,
                        ];
                    }
                );
            }

            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administrative Names List Not Found');
        }
    }



    public function storecustomfieldfile($request)
    {
        try {
            $data = $this->registrationRepository->storecustomfieldfile($request);
            
            return $data;
        } catch (\Exception $e) {
            
            Log::error(
                'Failed to store file.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store file.');
        }
    }

}