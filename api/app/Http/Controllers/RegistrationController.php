<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RegistrationService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\GenerateOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\InstitutionStudentStoreRequest;
use App\Http\Requests\StoreCustomFileRequest;

class RegistrationController extends Controller
{
    
    protected $registrationService;

    public function __construct(
        RegistrationService $registrationService
    ) {
        $this->registrationService = $registrationService;
    }

    /**
     * @OA\Get(
     *      path="/api/v4/academic-periods/list",
     *      summary="Get list of education grades",
     *      tags={"Academic Period"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=33),
     *                     @OA\Property(property="name", type="string", example="2024")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/systems/levels/cycles/programmes/grades/list",
     *      summary="Get list of education grades",
     *      tags={"Education Structure"},
     *      @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         description="Id of academic period",
     *         @OA\Schema(type="integer", example=30)
     *      ),
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Kindergarten 1")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/list",
     *      summary="Get list of Institutions on behalf of Institution Type Id and area id.",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="institution_type_id",
     *         in="query",
     *         description="Id of Institution type",
     *         @OA\Schema(type="integer", example=2)
     *      ),
     *      @OA\Parameter(
     *         name="area_id",
     *         in="query",
     *         description="Id of area",
     *         @OA\Schema(type="integer", example=4)
     *      ),
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="P1002 - Avory Primary School")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/areas/list",
     *      summary="Get a list of institution's areas",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Pre-primary"),
     *                     @OA\Property(property="parent_id", type="integer", example=2),
     *                 )
     *             )
     *          )
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/users/openemis_id/{openemisId}",
     *      summary="Get a list of users",
     *      tags={"Users"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="key", type="integer", example=11),
     *                     @OA\Property(property="value", type="string", example="1522271973"),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/details-by-emis/{id}",
     *      summary="Get detail of user by open emis id or identity number",
     *      tags={"Users"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="openemis_no", type="integer", example=1522271973),
     *                     @OA\Property(property="first_name", type="string", example="trushy"),
     *                     @OA\Property(property="middle_name", type="string", example=""),
     *                     @OA\Property(property="third_name", type="string", example="bait"),
     *                     @OA\Property(property="last_name", type="string", example="emilley"),
     *                     @OA\Property(property="preferred_name", type="string", example=null),
     *                     @OA\Property(property="email", type="string", example=null),
     *                     @OA\Property(property="address", type="string", example=null),
     *                     @OA\Property(property="postal_code", type="string", example=null),
     *                     @OA\Property(property="address_area_id", type="string", example=null),
     *                     @OA\Property(property="birthplace_area_id", type="string", example=null),
     *                     @OA\Property(property="identity_number", type="string", example=null),
     *                     @OA\Property(property="gender_id",  type="object",
     *                         @OA\Property(property="key", type="integer", example=2),
     *                         @OA\Property(property="value", type="string", example="Female"),
     *                     ),
     *                     @OA\Property(property="date_of_birth", type="string", example="2011-01-01T00:00:00.000000Z"),
     *                     @OA\Property(property="nationality_id",  type="object",
     *                         @OA\Property(property="key", type="integer", example=""),
     *                         @OA\Property(property="value", type="string", example=""),
     *                     ),
     *                     @OA\Property(property="institution",  type="object",
     *                         @OA\Property(property="key", type="integer", example=2),
     *                         @OA\Property(property="value", type="string", example="Windhaven Primary School"),
     *                     )
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/nationalities",
     *      summary="Get a list of nationalities",
     *      tags={"Nationalities"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Algerian"),
     *                     @OA\Property(property="is_refugee", type="integer", example=0),
     *                     @OA\Property(property="national_code", type="string", example=""),
     *                     @OA\Property(property="international_code", type="string", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", example=2),
     *                     @OA\Property(property="modified", type="dateTime", example="2018-04-19 06:03:39"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="dateTime", example="2018-04-19 06:03:39"),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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
            //dd($request->all());
            $resp = $this->registrationService->institutionStudents($request);

            if(is_array($resp)){
                if(isset($resp['msg'])){
                    return $this->sendErrorResponse($resp['msg']);
                }
                //return $this->sendErrorResponse("The student should be between ".$resp['loweAgeLimit']." to ".$resp['upperAgeLimit']. " years old.");
            }


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
            }elseif($resp == 8){
                return $this->sendErrorResponse("Please fill required custom fields.");
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

    /**
     * @OA\Get(
     *      path="/api/v4/identity-types/list",
     *      summary="Get a list of identity types",
     *      tags={"Identity types"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="School"),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/grades/{gradeId}/list",
     *      summary="Get a list of institution by grade id",
     *      tags={"Institution Grades"},
     *      @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="Id of the institution",
     *         @OA\Schema(type="integer", example=59)
     *      ),
     *      @OA\Parameter(
     *         name="institution_type_id",
     *         in="query",
     *         description="Id of Institution type",
     *         @OA\Schema(type="integer", example=2)
     *      ),
     *      @OA\Parameter(
     *         name="area_id",
     *         in="query",
     *         description="Id of area",
     *         @OA\Schema(type="integer", example=4)
     *      ),
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Avory Primary School (P1002)")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/institution-types/list",
     *      summary="Get a list of institution's type",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Pre-primary")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/area-levels/list",
     *      summary="Get a list of area levels",
     *      tags={"Areas"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Country")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/areas/list",
     *      summary="Get a list of area's",
     *      tags={"Areas"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="District 1")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/area-administrative-levels/list",
     *      summary="Get area administrative level list",
     *      tags={"Areas"},
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Continent")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function areaAdministrativeLevelsDropdown()
    {
        try {
            $data = $this->registrationService->areaAdministrativeLevelsDropdown();
            
            return $this->sendSuccessResponse("Area Administrative Levels List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administrative Levels List Not Found');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/area-administratives/list",
     *      summary="Get area administrative list",
     *      tags={"Areas"},
     *      @OA\Parameter(
     *         name="area_level_id",
     *         in="query",
     *         description="Id of area level",
     *         @OA\Schema(type="integer", example=4)
     *      ),
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Continent")
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function areasAdministrativeDropdown(Request $request)
    {
        try {
            $data = $this->registrationService->areasAdministrativeDropdown($request);
            
            return $this->sendSuccessResponse("Area Administrative Names List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administrative Names List Not Found');
        }
    }


    public function storecustomfieldfile(StoreCustomFileRequest $request)
    {
        try {
            $data = $this->registrationService->storecustomfieldfile($request);
            if(is_array($data)){
                return $this->sendSuccessResponse("File stored successfully", $data);
            } else {
                return $this->sendErrorResponse("Failed to store file.");
            }
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to store file.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store file.');
        }
    }
}