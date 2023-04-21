<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\EducationSystemRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class EducationSystemService extends Controller
{

    protected $educationSystemRepository;

    public function __construct(
    EducationSystemRepository $educationSystemRepository) {
        $this->educationSystemRepository = $educationSystemRepository;
    }

    
    public function getAllEducationSystems($request)
    {
        try {
            $data = $this->educationSystemRepository->getAllEducationSystems($request);
            $resp = [];
            
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['order'] = $d['order'];
                    $resp[$k]['visible'] = $d['visible'];
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];

                    
                    $resp[$k]['levels'] = $d['levels'];
                    foreach($d['levels'] as $lk => $l){
                        foreach($l['cycles'] as $ck => $c){
                            foreach($c['programmes'] as $pk => $p){
                                foreach($p['grades'] as $gk => $g){
                                    $subArr = [];
                                    foreach($g['subjects'] as $sk => $s){
                                        $subArr[$sk]['id'] = $s['id'];
                                        $subArr[$sk]['name'] = $s['name'];
                                        $subArr[$sk]['code'] = $s['code'];
                                        $subArr[$sk]['order'] = $s['order'];
                                        $subArr[$sk]['visible'] = $s['visible'];
                                        $subArr[$sk]['hours_required'] = $s['pivot']['hours_required'];
                                        $subArr[$sk]['auto_allocation'] = $s['pivot']['auto_allocation'];
                                        $subArr[$sk]['modified_user_id'] = $s['modified_user_id'];
                                        $subArr[$sk]['modified'] = $s['modified'];
                                        $subArr[$sk]['created_user_id'] = $s['created_user_id'];
                                        $subArr[$sk]['created'] = $s['created'];
                                    }
                                    
                                    $resp[$k]['levels'][$lk]['cycles'][$ck]['programmes'][$pk]['grades'][$gk]['subjects'] = $subArr;
                                }
                            }
                        }
                    }

                    

                }
            }

            $data['data'] = $resp;
            return $data;
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
            $data = $this->educationSystemRepository->getEducationStructureSystems($systemId, $request);
            //dd($data);
            $resp = [];
            
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['order'] = $d['order'];
                    $resp[$k]['visible'] = $d['visible'];
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                    
                    
                    $resp[$k]['levels'] = $d['levels'];
                    foreach($d['levels'] as $lk => $l){
                        foreach($l['cycles'] as $ck => $c){
                            foreach($c['programmes'] as $pk => $p){
                                foreach($p['grades'] as $gk => $g){
                                    $subArr = [];
                                    foreach($g['subjects'] as $sk => $s){
                                        $subArr[$sk]['id'] = $s['id'];
                                        $subArr[$sk]['name'] = $s['name'];
                                        $subArr[$sk]['code'] = $s['code'];
                                        $subArr[$sk]['order'] = $s['order'];
                                        $subArr[$sk]['visible'] = $s['visible'];
                                        $subArr[$sk]['hours_required'] = $s['pivot']['hours_required'];
                                        $subArr[$sk]['auto_allocation'] = $s['pivot']['auto_allocation'];
                                        $subArr[$sk]['modified_user_id'] = $s['modified_user_id'];
                                        $subArr[$sk]['modified'] = $s['modified'];
                                        $subArr[$sk]['created_user_id'] = $s['created_user_id'];
                                        $subArr[$sk]['created'] = $s['created'];
                                    }
                                    
                                    $resp[$k]['levels'][$lk]['cycles'][$ck]['programmes'][$pk]['grades'][$gk]['subjects'] = $subArr;
                                }
                            }
                        }
                    }
                }
            }

            $data['data'] = $resp;
            return $data;
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
            $data = $this->educationSystemRepository->getEducationStructureLevel($systemId, $levelId, $request);
            //dd($data);
            $resp = [];
            
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['order'] = $d['order'];
                    $resp[$k]['visible'] = $d['visible'];
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                    
                    
                    $resp[$k]['levels'] = $d['levels'];
                    foreach($d['levels'] as $lk => $l){
                        foreach($l['cycles'] as $ck => $c){
                            foreach($c['programmes'] as $pk => $p){
                                foreach($p['grades'] as $gk => $g){
                                    $subArr = [];
                                    foreach($g['subjects'] as $sk => $s){
                                        $subArr[$sk]['id'] = $s['id'];
                                        $subArr[$sk]['name'] = $s['name'];
                                        $subArr[$sk]['code'] = $s['code'];
                                        $subArr[$sk]['order'] = $s['order'];
                                        $subArr[$sk]['visible'] = $s['visible'];
                                        $subArr[$sk]['hours_required'] = $s['pivot']['hours_required'];
                                        $subArr[$sk]['auto_allocation'] = $s['pivot']['auto_allocation'];
                                        $subArr[$sk]['modified_user_id'] = $s['modified_user_id'];
                                        $subArr[$sk]['modified'] = $s['modified'];
                                        $subArr[$sk]['created_user_id'] = $s['created_user_id'];
                                        $subArr[$sk]['created'] = $s['created'];
                                    }
                                    
                                    $resp[$k]['levels'][$lk]['cycles'][$ck]['programmes'][$pk]['grades'][$gk]['subjects'] = $subArr;
                                }
                            }
                        }
                    }
                }
            }

            $data['data'] = $resp;
            return $data;
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
            $data = $this->educationSystemRepository->getEducationStructureCycle($systemId, $levelId, $cycleId, $request);
            //dd($data);
            $resp = [];
            
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['order'] = $d['order'];
                    $resp[$k]['visible'] = $d['visible'];
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                    
                    
                    $resp[$k]['levels'] = $d['levels'];
                    foreach($d['levels'] as $lk => $l){
                        foreach($l['cycles'] as $ck => $c){
                            foreach($c['programmes'] as $pk => $p){
                                foreach($p['grades'] as $gk => $g){
                                    $subArr = [];
                                    foreach($g['subjects'] as $sk => $s){
                                        $subArr[$sk]['id'] = $s['id'];
                                        $subArr[$sk]['name'] = $s['name'];
                                        $subArr[$sk]['code'] = $s['code'];
                                        $subArr[$sk]['order'] = $s['order'];
                                        $subArr[$sk]['visible'] = $s['visible'];
                                        $subArr[$sk]['hours_required'] = $s['pivot']['hours_required'];
                                        $subArr[$sk]['auto_allocation'] = $s['pivot']['auto_allocation'];
                                        $subArr[$sk]['modified_user_id'] = $s['modified_user_id'];
                                        $subArr[$sk]['modified'] = $s['modified'];
                                        $subArr[$sk]['created_user_id'] = $s['created_user_id'];
                                        $subArr[$sk]['created'] = $s['created'];
                                    }
                                    
                                    $resp[$k]['levels'][$lk]['cycles'][$ck]['programmes'][$pk]['grades'][$gk]['subjects'] = $subArr;
                                }
                            }
                        }
                    }
                }
            }

            $data['data'] = $resp;
            return $data;
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
            $data = $this->educationSystemRepository->getEducationStructureProgramme($systemId, $levelId, $cycleId, $programmeId, $request);
            //dd($data);
            $resp = [];
            
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['order'] = $d['order'];
                    $resp[$k]['visible'] = $d['visible'];
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                    
                    
                    $resp[$k]['levels'] = $d['levels'];
                    foreach($d['levels'] as $lk => $l){
                        foreach($l['cycles'] as $ck => $c){
                            foreach($c['programmes'] as $pk => $p){
                                foreach($p['grades'] as $gk => $g){
                                    $subArr = [];
                                    foreach($g['subjects'] as $sk => $s){
                                        $subArr[$sk]['id'] = $s['id'];
                                        $subArr[$sk]['name'] = $s['name'];
                                        $subArr[$sk]['code'] = $s['code'];
                                        $subArr[$sk]['order'] = $s['order'];
                                        $subArr[$sk]['visible'] = $s['visible'];
                                        $subArr[$sk]['hours_required'] = $s['pivot']['hours_required'];
                                        $subArr[$sk]['auto_allocation'] = $s['pivot']['auto_allocation'];
                                        $subArr[$sk]['modified_user_id'] = $s['modified_user_id'];
                                        $subArr[$sk]['modified'] = $s['modified'];
                                        $subArr[$sk]['created_user_id'] = $s['created_user_id'];
                                        $subArr[$sk]['created'] = $s['created'];
                                    }
                                    
                                    $resp[$k]['levels'][$lk]['cycles'][$ck]['programmes'][$pk]['grades'][$gk]['subjects'] = $subArr;
                                }
                            }
                        }
                    }
                }
            }

            $data['data'] = $resp;
            return $data;
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
            $data = $this->educationSystemRepository->getEducationStructureGrade($systemId, $levelId, $cycleId, $programmeId, $gradeId, $request);
            
            $resp = [];
            
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['order'] = $d['order'];
                    $resp[$k]['visible'] = $d['visible'];
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                    
                    
                    $resp[$k]['levels'] = $d['levels'];
                    foreach($d['levels'] as $lk => $l){
                        foreach($l['cycles'] as $ck => $c){
                            foreach($c['programmes'] as $pk => $p){
                                foreach($p['grades'] as $gk => $g){
                                    $subArr = [];
                                    foreach($g['subjects'] as $sk => $s){
                                        $subArr[$sk]['id'] = $s['id'];
                                        $subArr[$sk]['name'] = $s['name'];
                                        $subArr[$sk]['code'] = $s['code'];
                                        $subArr[$sk]['order'] = $s['order'];
                                        $subArr[$sk]['visible'] = $s['visible'];
                                        $subArr[$sk]['hours_required'] = $s['pivot']['hours_required'];
                                        $subArr[$sk]['auto_allocation'] = $s['pivot']['auto_allocation'];
                                        $subArr[$sk]['modified_user_id'] = $s['modified_user_id'];
                                        $subArr[$sk]['modified'] = $s['modified'];
                                        $subArr[$sk]['created_user_id'] = $s['created_user_id'];
                                        $subArr[$sk]['created'] = $s['created'];
                                    }
                                    
                                    $resp[$k]['levels'][$lk]['cycles'][$ck]['programmes'][$pk]['grades'][$gk]['subjects'] = $subArr;
                                }
                            }
                        }
                    }
                }
            }

            $data['data'] = $resp;
            return $data;
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
            $data = $this->educationSystemRepository->getEducationStructureSubject($systemId, $levelId, $cycleId, $programmeId, $gradeId, $subjectId, $request);
            
            $resp = [];
            
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['order'] = $d['order'];
                    $resp[$k]['visible'] = $d['visible'];
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                    
                    
                    $resp[$k]['levels'] = $d['levels'];
                    foreach($d['levels'] as $lk => $l){
                        foreach($l['cycles'] as $ck => $c){
                            foreach($c['programmes'] as $pk => $p){
                                foreach($p['grades'] as $gk => $g){
                                    $subArr = [];
                                    foreach($g['subjects'] as $sk => $s){
                                        $subArr[$sk]['id'] = $s['id'];
                                        $subArr[$sk]['name'] = $s['name'];
                                        $subArr[$sk]['code'] = $s['code'];
                                        $subArr[$sk]['order'] = $s['order'];
                                        $subArr[$sk]['visible'] = $s['visible'];
                                        $subArr[$sk]['hours_required'] = $s['pivot']['hours_required'];
                                        $subArr[$sk]['auto_allocation'] = $s['pivot']['auto_allocation'];
                                        $subArr[$sk]['modified_user_id'] = $s['modified_user_id'];
                                        $subArr[$sk]['modified'] = $s['modified'];
                                        $subArr[$sk]['created_user_id'] = $s['created_user_id'];
                                        $subArr[$sk]['created'] = $s['created'];
                                    }
                                    
                                    $resp[$k]['levels'][$lk]['cycles'][$ck]['programmes'][$pk]['grades'][$gk]['subjects'] = $subArr;
                                }
                            }
                        }
                    }
                }
            }

            $data['data'] = $resp;
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }



    public function reportCardLists($systemId, $levelId, $cycleId, $programmeId, $gradeId)
    {
        try {
            $data = $this->educationSystemRepository->reportCardLists($systemId, $levelId, $cycleId, $programmeId, $gradeId)->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->code.' - '.$item->name,
                    ];
                }
            );
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Report Cards List Not Found');
        }
    }

}
