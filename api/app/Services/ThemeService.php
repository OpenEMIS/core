<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\ThemeRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class ThemeService extends Controller
{

    protected $themeRepository;

    public function __construct(ThemeRepository $themeRepository)
    {
        $this->themeRepository = $themeRepository;
    }

    public function getAllThemes($params)
    {
        try {
            $data = $this->themeRepository->getAllThemes($params);
            $resp = [];

            if(!empty($data)){
                foreach($data as $k => $d){
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['value'] = $d['value'];
                    $resp[$k]['content'] = $d['content'];
                    $resp[$k]['default_value'] = $d['default_value'];
                    $resp[$k]['default_content'] = "";
                    if(isset($d['default_value'])){
                        $resp[$k]['default_content'] = json_encode($d['default_content'], true);
                    }
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                }
            }

            return $resp;
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
            $data = $this->themeRepository->getThemeId($id);
            $resp = [];
            if(!empty($data)){
                $resp['id'] = $data['id'];
                $resp['name'] = $data['name'];
                $resp['value'] = $data['value'];
                $resp['content'] = $data['content'];
                $resp['default_value'] = $data['default_value'];
                $resp['default_content'] = "";
                if(isset($data['default_value'])){
                    $resp['default_content'] = json_encode($data['default_content'], true);
                }
                $resp['modified_user_id'] = $data['modified_user_id'];
                $resp['modified'] = $data['modified'];
                $resp['created_user_id'] = $data['created_user_id'];
                $resp['created'] = $data['created'];
            }

            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Themes Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Themes Data Not Found.');
        }
    }
}