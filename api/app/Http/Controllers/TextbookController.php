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

    /**
     * @OA\Get(
     *      path="/api/v4/textbooks-conditions",
     *      summary="Get list of textbook conditions",
     *      description="Get list of textbook conditions",
     *      tags={"Textbook"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
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
     *                     @OA\Property(property="name", type="string", example="New"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="default", type="integer", example=0),
     *                     @OA\Property(property="international_code", type="string", example=""),
     *                     @OA\Property(property="national_code", type="string", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", example=2),
     *                     @OA\Property(property="modified", type="string", example="2018-04-18 08:46:12"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", example="2016-11-29 20:51:21")
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
    public function getTextbookConditions(Request $request){
    
            try {
                $params = $request->all();
                $data = $this->textbookService->getTextbookConditions($params);
                return $this->sendSuccessResponse("Textbook Conditions Found", $data);
                
            } catch (\Exception $e) {
                Log::error(
                    'Failed to fetch list from DB',
                    ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
                );
    
                return $this->sendErrorResponse('Textbook Conditions Not Found');
            }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/textbooks/{textbookId}",
     *      summary="Get textbook detail by id",
     *      description="Get textbook detail by id",
     *      tags={"Textbook"},
     *      @OA\Parameter(
     *         name="textbookId",
     *         in="path",
     *         required=true,
     *         description="Id of textbook",
     *         @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="LAC001"),
     *                 @OA\Property(property="title", type="string", example="Large Print Language Arts Textbook"),
     *                 @OA\Property(property="author", type="string", example="Steven Janson"),
     *                 @OA\Property(property="publisher", type="string", example="khan"),
     *                 @OA\Property(property="year_published", type="integer", example=2000),
     *                 @OA\Property(property="ISBN", type="integer", example="15345645"),
     *                 @OA\Property(property="expiry_date", type="null", example=null),
     *                 @OA\Property(property="academic_period_id", type="integer", example=29),
     *                 @OA\Property(property="education_grade_id", type="integer", example=1),
     *                 @OA\Property(property="education_subject_id", type="integer", example=1),
     *                 @OA\Property(property="dimension_id", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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


    /**
     * @OA\Get(
     *      path="/api/v4/textbooks-statuses",
     *      summary="Get list of textbook statuses",
     *      description="Get list of textbook statuses",
     *      tags={"Textbook"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
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
     *                     @OA\Property(property="code", type="string", example="AVAILABLE"),
     *                     @OA\Property(property="name", type="string", example="Available"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="default", type="integer", example=0),
     *                     @OA\Property(property="international_code", type="string", example=""),
     *                     @OA\Property(property="national_code", type="string", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", example=2),
     *                     @OA\Property(property="modified", type="string", example="2018-04-18 08:46:12"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", example="2016-11-29 20:51:21")
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
    public function getTextbookStatuses(Request $request){
    
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'Textbooks', 'view'], ['institution_id' => $request['institution_id']]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            
            //For POCOR-7772 End
            $params = $request->all();
            $data = $this->textbookService->getTextbookStatuses($params);
            return $this->sendSuccessResponse("Textbook Statuses Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Statuses Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/textbooks-dimensions",
     *      summary="Get list of textbook dimensions",
     *      description="Get list of textbook dimensions",
     *      tags={"Textbook"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
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
     *                     @OA\Property(property="name", type="string", example="50mmx76mm"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="default", type="integer", example=0),
     *                     @OA\Property(property="international_code", type="string", example=""),
     *                     @OA\Property(property="national_code", type="string", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", example=2),
     *                     @OA\Property(property="modified", type="string", example="2018-04-18 08:46:12"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", example="2016-11-29 20:51:21")
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
    public function getTextbookDimensions(Request $request){
    
        try {
            $params = $request->all();
            $data = $this->textbookService->getTextbookDimensions($params);
            return $this->sendSuccessResponse("Textbook Dimensions Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Dimensions Not Found');
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v4/textbooks",
     *      summary="Add textbook",
     *      description="Add textbook",
     *      tags={"Textbook"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="code", type="string", example="MCT 2023"),
     *              @OA\Property(property="academic_period_id", type="integer", example=33),
     *              @OA\Property(property="title", type="string", example="teststaffUser first name"),
     *              @OA\Property(property="author", type="string", example="teststaffUser last name"),
     *              @OA\Property(property="publisher", type="string", example="teststaffUser last name"),
     *              @OA\Property(property="year_published", type="string", example="teststaffUser last name"),
     *              @OA\Property(property="ISBN", type="string", example="teststaffUser last name"),
     *              @OA\Property(property="expiry_date", type="string", example="2000-01-01"),
     *              @OA\Property(property="education_grade_id", type="integer", example=5),
     *              @OA\Property(property="education_subject_id", type="integer", example=4),
     *              @OA\Property(property="dimension_id", type="integer", example=1),
     *          )
     *      ),
     *      @OA\Response(
     *           response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function addTextbooks(TextbookAddRequest $request){
        
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Textbooks', 'Textbooks', 'add']);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

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

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/textbooks/{textbookId}",
     *      summary="Get detail of institution textbook by institution id and textbook id",
     *      description="Get detail of institution textbook by institution id and textbook id",
     *      tags={"Textbook"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of institution",
     *         @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Parameter(
     *         name="textbookId",
     *         in="path",
     *         required=true,
     *         description="Id of textbook",
     *         @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="MA001-1"),
     *                 @OA\Property(property="comment", type="string", example=""),
     *                 @OA\Property(property="textbook_status_id", type="integer", example=1),
     *                 @OA\Property(property="textbook_condition_id", type="integer", example=1),
     *                 @OA\Property(property="institution_id", type="integer", example=1),
     *                 @OA\Property(property="academic_period_id", type="integer", example=1),
     *                 @OA\Property(property="education_grade_id", type="integer", example=0),
     *                 @OA\Property(property="education_subject_id", type="integer", example=1),
     *                 @OA\Property(property="security_user_id", type="integer", example=1),
     *                 @OA\Property(property="textbook_id", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", example="2018-04-18 08:46:12"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", example="2016-11-29 20:51:21")
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getInstitutionTextbookdata(int $institutionId, int $textbookId)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'Textbooks', 'add'], ['institution_id' => $institutionId]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End
            
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

    /**
     * @OA\Post(
     *      path="/api/v4/institutions/{institutionId}/textbooks",
     *      summary="Add textbook to institution",
     *      description="Add textbook to institution",
     *      tags={"Textbook"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of institution",
     *         @OA\Schema(type="integer", example=6)
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="academic_period_id", type="integer", example=33),
     *              @OA\Property(property="education_grade_id", type="integer", example=5),
     *              @OA\Property(property="education_subject_id", type="integer", example=4),
     *              @OA\Property(property="institution_id", type="integer", example=6),
     *              @OA\Property(property="textbook_id", type="integer", example=4),
     *          )
     *      ),
     *      @OA\Response(
     *           response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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