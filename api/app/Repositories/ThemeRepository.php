<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;

class ThemeRepository extends Controller
{

    public function getAllThemes($params)
    {
        try {
            $list = new Theme;

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }
            
            if (isset($params['limit'])) {
                $list = $list->paginate($params['limit']);
            } else {
                $list = $list->get();
            }
            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Themes List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Themes List Not Found.');
        }
    }


    public function getThemeViaId($id)
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