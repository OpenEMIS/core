<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SurveyService;
use App\Http\Requests\UploadXformRequest;

class SurveyController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyService $surveyService) 
    {
        $this->surveyService = $surveyService;
    }



    /**
     * @OA\Get(
     *     path="/api/v4/surveys",
     *     summary="Get surveys list.",
     *     description="Returns a list of surveys.",
     *     tags={"Surveys"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *                 @OA\Property(property="data", type="object",
     *                      @OA\Property(property="data", type="array",
     *                          @OA\Items(
     *                         type="object",
     *                              @OA\Property(property="id", type="string", example="17"),
     *                              @OA\Property(property="code", type="string", example="38ba70ef"),
     *                              @OA\Property(property="name", type="string", example="Staff List Test"),
     *                              @OA\Property(property="description", type="string", example=""),
     *                              @OA\Property(property="custom_module_id", type="integer", example=1),
     *                              @OA\Property(property="custom_module", type="string", example="Institution > Overview"),
     *                              @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                              @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                              @OA\Property(property="created_user_id", type="integer", example=2),
     *                              @OA\Property(property="created", type="string", format="date-time", example="2018-05-30 07:29:11")
     *                          )
     *                      )
     *                 )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getSurveys(Request $request)
    {
        try {
            $data = $this->surveyService->getSurveys($request);
            return $this->sendSuccessResponse("Surveys List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/survey/download/xform/{surveyFormId}",
     *     summary="Get surveys list.",
     *     description="Returns a xml content of survey form.",
     *     tags={"Surveys"},
     *     @OA\Parameter(
     *         name="surveyFormId",
     *         in="path",
     *         required=true,
     *         description="Survey Form Id",
     *         @OA\Schema(type="integer", example="11")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\MediaType(
     *             mediaType="application/xml",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="html",
     *                     type="object",
     *                     @OA\Property(property="head", type="object",
     *                         @OA\Property(property="title", type="string", example="Staff List Test"),
     *                         @OA\Property(property="meta", type="object",
     *                             @OA\Property(property="name", type="string", example="description"),
     *                             @OA\Property(property="content", type="string", example="")
     *                         ),
     *                         @OA\Property(property="xf:model", type="object",
     *                             @OA\Property(property="xf:instance", type="object",
     *                                 @OA\Property(property="id", type="string", example="xform"),
     *                                 @OA\Property(property="oe:SurveyForms", type="object",
     *                                     @OA\Property(property="id", type="integer", example=17),
     *                                     @OA\Property(property="oe:Institutions", type="string", example=""),
     *                                     @OA\Property(property="oe:AcademicPeriods", type="integer", example=""),
     *                                     @OA\Property(property="oe:SurveyQuestions", type="object",
     *                                         @OA\Property(property="id", type="integer", example=111)
     *                                     )
     *                                 )
     *                             ),
     *                             @OA\Property(property="xf:bind", type="object",
     *                                 @OA\Property(property="ref", type="string", example="instance('xform')/SurveyForms/Institutions"),
     *                                 @OA\Property(property="type", type="string", example="string"),
     *                                 @OA\Property(property="required", type="boolean", example="true()")
     *                             ),
     *                             @OA\Property(property="xsd:schema", type="object"),
     *                             @OA\Property(property="xf:bind[2]", type="object",
     *                                 @OA\Property(property="ref", type="string", example="instance('xform')/SurveyForms/AcademicPeriods"),
     *                                 @OA\Property(property="type", type="string", example="integer"),
     *                                 @OA\Property(property="required", type="boolean", example="true()")
     *                             ),
     *                             @OA\Property(property="xf:bind[3]", type="object",
     *                                 @OA\Property(property="ref", type="string", example="instance('xform')/SurveyForms/SurveyQuestions[1]"),
     *                                 @OA\Property(property="type", type="string", example="string"),
     *                                 @OA\Property(property="required", type="boolean", example="false()")
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(property="body", type="object",
     *                         @OA\Property(property="xf:input", type="object",
     *                             @OA\Property(property="ref", type="string", example="instance('xform')/SurveyForms/Institutions"),
     *                             @OA\Property(property="oe-type", type="string", example="string"),
     *                             @OA\Property(property="xf:label", type="string", example="Institution Code")
     *                         ),
     *                         @OA\Property(property="xf:select1", type="object",
     *                             @OA\Property(property="ref", type="string", example="instance('xform')/SurveyForms/AcademicPeriods"),
     *                             @OA\Property(property="oe-type", type="string", example="integer"),
     *                             @OA\Property(property="oe-dependency", type="string", example="instance('xform')/SurveyForms/Institutions"),
     *                             @OA\Property(property="xf:label", type="string", example="Academic Period"),
     *                             @OA\Property(property="xf:item", type="object",
     *                                 @OA\Property(property="xf:label", type="string", example="2024"),
     *                                 @OA\Property(property="xf:value", type="integer", example=33)
     *                             )
     *                         ),
     *                         @OA\Property(property="xf:staff_list", type="object",
     *                             @OA\Property(property="ref", type="string", example="instance('xform')/SurveyForms/SurveyQuestions[1]"),
     *                             @OA\Property(property="xf:label", type="string", example="Staff List")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function downloadXform(Request $request, $surveyFormId, $action=1)
    {
        try {
            $data = $this->surveyService->downloadXform($request, $surveyFormId);
            
            if(!empty($data)){
                if($action == 1){
                    return response($data, 200,['Content-Type' => 'application/xml']);
                } else {
                    $fileName = 'xform_' . date('Ymdhis');
                    $fileWithExt = $fileName.".xml";

                    return response($data)
                        ->header('Content-type', 'application/xml')
                        ->header('Content-Disposition', 'attachment; filename='.$fileWithExt);
                }
            } else {
                return $this->sendErrorResponse('Failed to download survey xform.');
            }
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to download survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to download survey xform.');
        }
    }



    public function checkInsXform(Request $request, $surveyFormId, $insCode, $academicPeriod)
    {
        try {
            $params = $request->all();
            $data = $this->surveyService->checkInsXform($params, $surveyFormId, $insCode, $academicPeriod);
            
            if(!empty($data)){
                return $this->sendSuccessResponse("Successful.", $data);
            } else {
                return $this->sendErrorResponse('Survey not exists for selected Institution.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to check survey form.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to check survey form.');
        }
    }


    public function getStudentListForSurvey(Request $request, $surveyFormId, $insCode, $academicPeriod)
    {
        try {
            $params = $request->all();
            $data = $this->surveyService->getStudentListForSurvey($params, $surveyFormId, $insCode, $academicPeriod);
            
            if(!empty($data)){
                return $this->sendSuccessResponse("Successful.", $data);
            } else {
                return $this->sendErrorResponse('Student list not found.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find student list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find student list.');
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v4/survey/upload",
     *     summary="Get surveys list.",
     *     description="Returns a list of surveys.",
     *     tags={"Surveys"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/xml",
     *             @OA\Schema(
     *                 type="object",
     *                 example="<xf:instance id='xform'>
     *                              <oe:SurveyForms id='19'>
     *                                  <oe:Institutions>P1002</oe:Institutions>
     *                                  <oe:AcademicPeriods>33</oe:AcademicPeriods>
     *                                  <oe:SurveyQuestions id='107' />
     *                                  <oe:SurveyQuestions id='106'>-122.083922 37.4220936</oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='7'>10</oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='14'>3</oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='9'>
     *                                      <oe:SurveyTableRows id='95'>
     *                                          <oe:SurveyTableColumns0 id='0'>test row</oe:SurveyTableColumns0>
     *                                      </oe:SurveyTableRows>
     *                                  </oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='103'>0.2</oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='112'>
     *                                      <oe:RepeatBlock>
     *                                          <oe:SurveyQuestions1 id='7'>5</oe:SurveyQuestions1>
     *                                          <oe:SurveyQuestions2 id='10'>2024-07-27</oe:SurveyQuestions2>
     *                                          <oe:SurveyQuestions3 id='17'>description</oe:SurveyQuestions3>
     *                                          <oe:SurveyQuestions4 id='22'>15</oe:SurveyQuestions4>
     *                                          <oe:SurveyQuestions5 id='109'>upload test</oe:SurveyQuestions5>
     *                                      </oe:RepeatBlock>
     *                                  </oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='110'>96 97</oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='109'>upload test text</oe:SurveyQuestions>
     *                                  <oe:SurveyQuestions id='104'>upload test description</oe:SurveyQuestions>
     *                              </oe:SurveyForms>
     *                          </xf:instance>"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *          )
     *      ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function uploadXform(UploadXformRequest $request)
    {
        try {
            $data = $this->surveyService->uploadXform($request);
            
            if($data == 1){
                return $this->sendSuccessResponse('Survey xfrom uploaded successfully.');
            } elseif($data == 2){
                return $this->sendErrorResponse('No record found for institution for the form for the period.');
            } elseif($data == 3){
                return $this->sendErrorResponse('Survey is already expired.');
            } elseif($data == 4){
                return $this->sendErrorResponse('Survey is already completed.');
            } elseif($data == 5){
                return $this->sendErrorResponse("You're not allowed to upload survey for this institution.");
            } elseif($data == 6){
                return $this->sendErrorResponse('Invalid institution code.');
            } else {
                return $this->sendErrorResponse('Failed to upload survey xform.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to upload survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to upload survey xform.');
        }
    }
}
