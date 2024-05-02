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


    public function getThemeId($id)
    {
        try {
            $data = $this->themeService->getThemeId($id);
            
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
