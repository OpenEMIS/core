<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\InstitutionScheduleTimetables;
use App\Models\InstitutionScheduleLessons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use JWTAuth;


//POCOR-8616
class TimetableOverviewRepository extends Controller
{

   public function getTimetableOverview($params)
   {
        try {
         $timeSystem =  InstitutionScheduleTimetables::with('academicPeriod',
                        'institution',
                        'scheduleTerm',
                        'scheduleInterval.shift.shiftOption',
                        'institutionClass.grades.educationGrades'
                     );

          if(isset($params['academic_period_id'])){
                $timeSystem = $timeSystem->where('academic_period_id', $params['academic_period_id'])->where('institution_id', $params['institution_id'])->where('institution_class_id', $params['institution_class_id'])->where('institution_schedule_term_id', $params['institution_schedule_term_id']);
            }
            //POCOR-9594: allow filtering by specific timetable ID to avoid returning a sibling timetable
            if (isset($params['timetable_id']) && $params['timetable_id']) {
                $timeSystem = $timeSystem->where('id', $params['timetable_id']);
            }
           
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $timeSystem = $timeSystem->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $timeSystem->paginate($limit)->toArray();
            } else {
                $list['data'] = $timeSystem->get()->toArray();

            }
            return $list;

        } catch (\Exception $e) {
            throw $e;
        }
   }

}