<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\EducationSystem;
use App\Models\EducationLevel;
use App\Models\ReportCard;
use App\Models\CompetencyCriterias;
use App\Models\CompetencyGradingOptions;
use App\Models\CompetencyGradingTypes;
use App\Models\CompetencyItems;
use App\Models\CompetencyItemPeriods;
use App\Models\CompetencyPeriods;
use App\Models\CompetencyTemplates;

class EducationSystemRepository extends Controller
{

    public function getAllEducationSystems($request)
    {
        try {
            $params = $request->all();


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

            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $systems = $systems->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $systems->paginate($limit)->toArray();
            } else {
                $list['data'] = $systems->get()->toArray();

            }
            //For POCOR-8215/8216 end...

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


            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $systems = $systems->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $systems->paginate($limit)->toArray();
            } else {
                $list['data'] = $systems->get()->toArray();

            }
            //For POCOR-8215/8216 end...


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

            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $systems = $systems->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $systems->paginate($limit)->toArray();
            } else {
                $list['data'] = $systems->get()->toArray();

            }
            //For POCOR-8215/8216 end...


            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureCycle($systemId, $levelId, $cycleId, $request)
    {
        try {
            $params = $request->all();

            $systems = EducationSystem::with([
                'levels' => function ($q) use ($levelId, $cycleId) {
                    $q->where('id', $levelId)
                        ->with([
                            'cycles' =>function ($q) use ($cycleId){
                                $q->where('id', $cycleId)
                                ->with(
                                    'programmes',
                                    'programmes.grades',
                                    'programmes.grades.subjects'
                                );
                            }
                        ]);

                }
            ])
            ->where('id', $systemId)
            ->whereHas('levels', function ($q) use ($levelId) {
                $q->where('education_levels.id', $levelId);
            })
            ->whereHas('levels.cycles', function ($q) use ($cycleId) {
                $q->where('education_cycles.id', $cycleId);
            });

            if(isset($params['academic_period_id'])){
                $systems = $systems->where('academic_period_id', $params['academic_period_id']);
            }


            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $systems = $systems->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $systems->paginate($limit)->toArray();
            } else {
                $list['data'] = $systems->get()->toArray();

            }
            //For POCOR-8215/8216 end...


            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureProgramme($systemId, $levelId, $cycleId, $programmeId, $request)
    {
        try {
            $params = $request->all();

            $systems = EducationSystem::with([
                'levels' => function ($q) use ($levelId, $cycleId, $programmeId) {
                    $q->where('id', $levelId)
                    ->with([
                        'cycles' =>function ($q) use ($levelId, $cycleId, $programmeId)
                        {
                            $q->where('id', $cycleId)
                            ->with([
                                'programmes' => function ($q) use ($levelId, $cycleId, $programmeId){
                                    $q->where('id', $programmeId)
                                    ->with(
                                        'grades',
                                        'grades.subjects'
                                    );
                                }
                            ]);
                        }
                    ]);
                }
            ])
            ->where('id', $systemId)
            ->whereHas('levels', function ($q) use ($levelId) {
                $q->where('education_levels.id', $levelId);
            })
            ->whereHas('levels.cycles', function ($q) use ($cycleId) {
                $q->where('education_cycles.id', $cycleId);
            })
            ->whereHas('levels.cycles.programmes', function ($q) use ($programmeId) {
                $q->where('education_programmes.id', $programmeId);
            });

            if(isset($params['academic_period_id'])){
                $systems = $systems->where('academic_period_id', $params['academic_period_id']);
            }



            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $systems = $systems->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $systems->paginate($limit)->toArray();
            } else {
                $list['data'] = $systems->get()->toArray();

            }
            //For POCOR-8215/8216 end...

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }



    public function getEducationStructureGrade($systemId, $levelId, $cycleId, $programmeId, $gradeId, $request)
    {
        try {
            $params = $request->all();


            $systems = EducationSystem::with([
                'levels' => function ($q) use ($levelId, $cycleId, $programmeId, $gradeId) {
                    $q->where('id', $levelId)
                    ->with([
                        'cycles' =>function ($q) use ($levelId, $cycleId, $programmeId, $gradeId)
                        {
                            $q->where('id', $cycleId)
                            ->with([
                                'programmes' => function ($q) use ($levelId, $cycleId, $programmeId, $gradeId){
                                    $q->where('id', $programmeId)
                                    ->with([
                                        'grades' => function ($q) use ($levelId, $cycleId, $programmeId, $gradeId){
                                            $q->where('id', $gradeId)
                                            ->with(
                                                'subjects'
                                            );
                                        }
                                    ]);
                                }
                            ]);
                        }
                    ]);
                }
            ])
            ->where('id', $systemId)
            ->whereHas('levels', function ($q) use ($levelId) {
                $q->where('education_levels.id', $levelId);
            })
            ->whereHas('levels.cycles', function ($q) use ($cycleId) {
                $q->where('education_cycles.id', $cycleId);
            })
            ->whereHas('levels.cycles.programmes', function ($q) use ($programmeId) {
                $q->where('education_programmes.id', $programmeId);
            })
            ->whereHas('levels.cycles.programmes.grades', function ($q) use ($gradeId)
            {
                $q->where('education_grades.id', $gradeId);
            });

            if(isset($params['academic_period_id'])){
                $systems = $systems->where('academic_period_id', $params['academic_period_id']);
            }


            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $systems = $systems->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $systems->paginate($limit)->toArray();
            } else {
                $list['data'] = $systems->get()->toArray();

            }
            //For POCOR-8215/8216 end...

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }



    public function getEducationStructureSubject($systemId, $levelId, $cycleId, $programmeId, $gradeId, $subjectId, $request)
    {
        try {
            $params = $request->all();


            $systems = EducationSystem::with([
                'levels' => function ($q) use ($levelId, $cycleId, $programmeId, $gradeId, $subjectId) {
                    $q->where('id', $levelId)
                    ->with([
                        'cycles' =>function ($q) use ($levelId, $cycleId, $programmeId, $gradeId, $subjectId)
                        {
                            $q->where('id', $cycleId)
                            ->with([
                                'programmes' => function ($q) use ($levelId, $cycleId, $programmeId, $gradeId, $subjectId){
                                    $q->where('id', $programmeId)
                                    ->with([
                                        'grades' => function ($q) use ($levelId, $cycleId, $programmeId, $gradeId, $subjectId){
                                            $q->where('id', $gradeId)
                                            ->with([
                                                'subjects' => function ($q) use ($levelId, $cycleId, $programmeId, $gradeId, $subjectId){
                                                    $q->where('education_grades_subjects.education_subject_id', $subjectId);
                                                }
                                            ]);
                                        }
                                    ]);
                                }
                            ]);
                        }
                    ]);
                }
            ])
            ->where('id', $systemId)
            ->whereHas('levels', function ($q) use ($levelId) {
                $q->where('education_levels.id', $levelId);
            })
            ->whereHas('levels.cycles', function ($q) use ($cycleId) {
                $q->where('education_cycles.id', $cycleId);
            })
            ->whereHas('levels.cycles.programmes', function ($q) use ($programmeId) {
                $q->where('education_programmes.id', $programmeId);
            })
            ->whereHas('levels.cycles.programmes.grades', function ($q) use ($gradeId)
            {
                $q->where('education_grades.id', $gradeId);
            })
            ->whereHas('levels.cycles.programmes.grades.subjects', function ($q) use ($subjectId)
            {
                $q->where('education_subjects.id', $subjectId);
            });

            if(isset($params['academic_period_id'])){
                $systems = $systems->where('academic_period_id', $params['academic_period_id']);
            }


            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $systems = $systems->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $systems->paginate($limit)->toArray();
            } else {
                $list['data'] = $systems->get()->toArray();

            }
            //For POCOR-8215/8216 end...

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }



    public function reportCardLists($params, $systemId, $levelId, $cycleId, $programmeId, $gradeId)
    {
        try {
            $reportCards = ReportCard::where('education_grade_id', $gradeId);

            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $reportCards = $reportCards->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $reportCards->paginate($limit)->toArray();
            } else {
                $list['data'] = $reportCards->get()->toArray();

            }
            //For POCOR-8215/8216 end...

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }



    public function getCompetencies($systemId, $levelId, $cycleId, $programmeId, $gradeId, $request)
    {
        try {
            $params = $request->all();

            $comptencies = CompetencyTemplates::with('competencyCriteria', 'competencyCriteria.competencyItem', 'competencyCriteria.competencyItem.competencyPeriods', 'competencyCriteria.competencyGradingtype')->where('education_grade_id', $gradeId);


            if(isset($params['academic_period_id'])){
                $comptencies = $comptencies->where('academic_period_id', $params['academic_period_id']);
            }


            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $comptencies = $comptencies->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $comptencies->paginate($limit)->toArray();
            } else {
                $list['data'] = $comptencies->get()->toArray();

            }
            //For POCOR-8215/8216 end...


            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }
}

