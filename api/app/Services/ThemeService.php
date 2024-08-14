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

            if($data['data']){
                foreach ($data['data'] as $k => $d) {
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['value'] = $d['value'];
                    $resp[$k]['content'] = $d['content'];
                    
                    //For POCOR-8445 Start...
                    if(isset($d['content'])){
                        $resp[$k]['content'] = base64_encode($d['content']);
                    }
                    $resp[$k]['default_value'] = $d['default_value'];
                    $resp[$k]['default_content'] = "";
                    if(isset($d['default_value'])){
                        $resp[$k]['default_content'] = base64_encode($d['default_content']);
                    }
                    //For POCOR-8445 End...

                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                }

            }

            if (isset($params['limit'])) {
                $data['data'] = $resp;
                return $data;
            } else {
                return $resp;
            }
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
            $data = $this->themeRepository->getThemeViaId($id);
            
            $resp = [];
            if(!empty($data)){
                $resp['id'] = $data['id'];
                $resp['name'] = $data['name'];
                $resp['value'] = $data['value'];
                $resp['content'] = $data['content'];
                
                //For POCOR-8445 Start...
                if(isset($data['content'])){
                    $resp['content'] = base64_encode($data['content']);
                }
                $resp['default_value'] = $data['default_value'];
                $resp['default_content'] = "";
                if(isset($data['default_value'])){
                    $resp['default_content'] = base64_encode($data['default_content']);
                }
                //For POCOR-8445 End...

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