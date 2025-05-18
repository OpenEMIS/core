<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Log;

class ThemeController extends Controller
{
    protected $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * @OA\Get(
     *     path="/api/v4/themes",
     *     summary="Get all the themes available",
     *     description="Returns a list of all the themes available",
     *     tags={"Themes"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items to return per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=76),
     *                 @OA\Property(property="name", type="string", example="Application Name"),
     *                 @OA\Property(property="value", type="integer", example=null),
     *                 @OA\Property(property="content", type="integer", example=null),
     *                 @OA\Property(property="default_value", type="string", example="OpenEMIS Core"),
     *                 @OA\Property(property="default_content", type="integer", example=null),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", example="2018-03-28 15:22:40"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", example="2016-05-25 09:52:26")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAllThemes(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->themeService->getAllThemes($params);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Themes List Not Found.");
            }

            return $this->sendSuccessResponse("Themes List Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Themes List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Themes List Not Found.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/themes/{themeId}",
     *     summary="Get theme by theme id",
     *     description="Returns a theme by theme id",
     *     tags={"Themes"},
     *     @OA\Parameter(
     *         name="themeId",
     *         in="path",
     *         required=true,
     *         description="Theme Id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=76),
     *                 @OA\Property(property="name", type="string", example="Application Name"),
     *                 @OA\Property(property="value", type="integer", example=null),
     *                 @OA\Property(property="content", type="integer", example=null),
     *                 @OA\Property(property="default_value", type="string", example="OpenEMIS Core"),
     *                 @OA\Property(property="default_content", type="integer", example=null),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", example="2018-03-28 15:22:40"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", example="2016-05-25 09:52:26")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getThemeViaId($id)
    {
        try {
            $data = $this->themeService->getThemeViaId($id);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Theme Data Not Found.");
            }

            return $this->sendSuccessResponse("Theme Data Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Themes Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Themes Data Not Found.');
        }
    }
}