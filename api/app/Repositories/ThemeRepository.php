<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;

class ThemeRepository
{

    public function getAllThemes($params)
    {
        try {
            $list = Theme::get()->toArray();
            return $list;
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
            $list = Theme::where('id', $id)->first();
            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Themes Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Themes Data Not Found.');
        }
    }

}