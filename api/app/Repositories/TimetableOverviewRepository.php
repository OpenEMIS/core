<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\InstitutionScheduleTimetables;
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

   public function getScheduleTimeTableExport($params)
    {
        try {
            $list = InstitutionScheduleTimetables::join('academic_periods', 'academic_periods.id', '=', 'institution_schedule_timetables.academic_period_id')
                    ->join('institution_classes', 'institution_classes.id', '=', 'institution_schedule_timetables.institution_class_id')
                    ->join('institutions', 'institutions.id', '=', 'institution_schedule_timetables.institution_id')
                    ->join('institution_schedule_intervals', 'institution_schedule_intervals.id', '=', 'institution_schedule_timetables.institution_schedule_interval_id')
                    ->join('institution_schedule_terms', 'institution_schedule_terms.id', '=', 'institution_schedule_timetables.institution_schedule_term_id')
                    ->select(
                        'academic_periods.name as academic_period_name',
                        'institutions.name as institution_name',
                        'institutions.code as institution_code',
                        'institution_classes.name as class_name',
                        'institution_schedule_intervals.name as institution_schedule_interval_name',
                        'institution_schedule_terms.name as institution_schedule_term_name',
                        'institution_schedule_timetables.name',
                        'institution_schedule_timetables.status',
                    )
                    ->get()
                    ->toArray();
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export Timetable from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export Timetable from DB.');
        }
    }

}