<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\EducationSystem;
use App\Models\EducationLevel;

class EducationSystemRepository extends Controller
{

    public function getAllEducationSystems($request)
    {
        try {
            $params = $request->all();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }


            $systems = EducationSystem::with(
                    'levels', 
                    'levels.cycles',
                    'levels.cycles.programmes',
                    'levels.cycles.programmes.grades',
                    'levels.cycles.programmes.grades.subjects'
            );

            if(isset($params['academic_period_id'])){
                $systems = $systems->where('academic_period_id', $params['academic_period_id']);
            }

            if(isset($params['order'])){
                $col = $params['order'];
                $systems = $systems->orderBy($col);
            }


            $list = $systems->paginate($limit)->toArray();
            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureSystems($systemId, $request)
    {
        try {
            $params = $request->all();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }


            $systems = EducationSystem::with(
                    'levels', 
                    'levels.cycles',
                    'levels.cycles.programmes',
                    'levels.cycles.programmes.grades',
                    'levels.cycles.programmes.grades.subjects'
            )->where('id', $systemId);

            if(isset($params['academic_period_id'])){
                $systems = $systems->where('academic_period_id', $params['academic_period_id']);
            }

            if(isset($params['order'])){
                $col = $params['order'];
                $systems = $systems->orderBy($col);
            }


            $list = $systems->paginate($limit)->toArray();
            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureLevel($systemId, $levelId, $request)
    {
        try {
            $params = $request->all();
            //dd($systemId, $levelId);
            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }


            $systems = EducationSystem::with([
                'levels' => function ($q) use ($levelId) {
                    $q->where('id', $levelId)
                        ->with(
                            'cycles', 
                            'cycles.programmes', 
                            'cycles.programmes.grades', 
                            'cycles.programmes.grades.subjects'
                        );
                }
            ])
            ->where('id', $systemId)
            ->whereHas('levels', function ($q) use ($levelId) {
                $q->where('education_levels.id', $levelId);
            });

            if(isset($params['academic_period_id'])){
                $systems = $systems->where('academic_period_id', $params['academic_period_id']);
            }

            if(isset($params['order'])){
                $col = $params['order'];
                $systems = $systems->orderBy($col);
            }

            /*$sql = $systems->toSql();
            dd($sql);*/
            $list = $systems->paginate($limit)->toArray();
            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


}

