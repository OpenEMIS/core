<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SystemConfigurationService;
use Illuminate\Support\Facades\Log;

class SystemConfigurationController extends Controller
{
    protected $configService;

    public function __construct(SystemConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @OA\Get(
     *      path="/api/v4/system-configurations",
     *      summary="Get list of system configuration ",
     *      description="Get list of system configuration ",
     *      tags={"System Configuration"},
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
     *      ),
     *      @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *      ),
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
     *                     @OA\Property(property="name", type="string", example="Date Format"),
     *                     @OA\Property(property="code", type="string", example="date_format"),
     *                     @OA\Property(property="type", type="string", example="System"),
     *                     @OA\Property(property="label", type="string", example="Date Format"),
     *                     @OA\Property(property="value", type="string", example="F d, Y"),
     *                     @OA\Property(property="value_selection", type="string", example=""),
     *                     @OA\Property(property="default_value", type="string", example="Y-m-d"),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="field_type", type="string", example="Dropdown"),
     *                     @OA\Property(property="option_type", type="string", example="date_format"),
     *                     @OA\Property(property="modified_user_id", type="integer", example=108),
     *                     @OA\Property(property="modified", type="string", example="2014-04-02 16:48:23"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="string", example="1970-01-01 00:00:00"),
     *                     @OA\Property(property="item_options", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="option_type", type="string", example="date_format"),
     *                              @OA\Property(property="option", type="string", example="date('Y-n-j')"),
     *                              @OA\Property(property="value", type="string", example="Y-m-d"),
     *                              @OA\Property(property="order", type="integer", example=1),
     *                              @OA\Property(property="visible", type="integer", example=1)
     *                          )
     *                     )
     *                 )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function allConfigurationItems(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->configService->getAllConfigurationItems($params);

            if ($data->isEmpty()) {
                return $this->sendErrorResponse("System Configuration List Not Found.");
            }

            return $this->sendSuccessResponse("System Configuration List Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch System Configuration List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('System Configuration List Not Found.');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/system-configurations/{configId}",
     *      summary="Get detail of system configuration by config id",
     *      description="Get detail of system configuration by config id",
     *      tags={"System Configuration"},
     *      @OA\Parameter(
     *         name="configId",
     *         in="path",
     *         required=true,
     *         description="config id",
     *         @OA\Schema(type="integer", example="1")
     *      ),
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
     *                     @OA\Property(property="name", type="string", example="Date Format"),
     *                     @OA\Property(property="code", type="string", example="date_format"),
     *                     @OA\Property(property="type", type="string", example="System"),
     *                     @OA\Property(property="label", type="string", example="Date Format"),
     *                     @OA\Property(property="value", type="string", example="F d, Y"),
     *                     @OA\Property(property="value_selection", type="string", example=""),
     *                     @OA\Property(property="default_value", type="string", example="Y-m-d"),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="field_type", type="string", example="Dropdown"),
     *                     @OA\Property(property="option_type", type="string", example="date_format"),
     *                     @OA\Property(property="modified_user_id", type="integer", example=108),
     *                     @OA\Property(property="modified", type="string", example="2014-04-02 16:48:23"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="string", example="1970-01-01 00:00:00"),
     *                     @OA\Property(property="item_options", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="option_type", type="string", example="date_format"),
     *                              @OA\Property(property="option", type="string", example="date('Y-n-j')"),
     *                              @OA\Property(property="value", type="string", example="Y-m-d"),
     *                              @OA\Property(property="order", type="integer", example=1),
     *                              @OA\Property(property="visible", type="integer", example=1)
     *                          )
     *                     )
     *                 )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function configurationItemById($configId)
    {
        try {
            $data = $this->configService->getConfigurationItemById($configId);

            if ($data->isEmpty()) {
                return $this->sendErrorResponse("System Configuration Not Found.");
            }

            return $this->sendSuccessResponse("System Configuration Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch System Configuration List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('System Configuration Not Found.');
        }
    }
}