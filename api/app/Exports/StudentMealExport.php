<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\InstitutionClassStudents;
use Illuminate\Support\Facades\DB;

class StudentMealExport implements FromCollection, WithHeadings
{
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {   
        $options = $this->params;
        $institutionId = $options['institution_id'];
        $mealProgramId = $options['meal_program_id'];
        $institutionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $weekId = $options['week_id']??0;
        $weekStartDay = $options['week_start_day']??NULL;
        $weekEndDay = $options['week_end_day']??NULL;
        $day = $options['day_id'];

        $query = InstitutionClassStudents::join('student_statuses', 'student_statuses.id', '=', 'institution_class_students.student_status_id')
                    ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
                    ->where('student_statuses.code', 'CURRENT')
                    ->where([
                        'institution_class_students.academic_period_id' => $academicPeriodId,
                        'institution_class_students.institution_class_id' => $institutionClassId,
                        'institution_class_students.institution_id' => $institutionId,
                    ])
                    ->orderBy('security_users.first_name')
                    ->orderBy('security_users.last_name')
                    ->leftJoin('student_meal_marked_records', function ($q) use ($mealProgramId, $day) {
                    $q->on('institution_class_students.institution_class_id', '=', 'student_meal_marked_records.institution_class_id')
                        ->on('institution_class_students.institution_id', '=', 'student_meal_marked_records.institution_id')
                        ->where('student_meal_marked_records.meal_programmes_id', $mealProgramId)
                        ->where('student_meal_marked_records.date', '=', $day);
                    })
                    ->leftJoin('institution_meal_students', function ($q) use($mealProgramId, $day) {
                        $q->on('institution_meal_students.institution_class_id', '=', 'institution_class_students.institution_class_id')
                        ->on('institution_meal_students.student_id', '=', 'institution_class_students.student_id')
                        ->on('institution_meal_students.institution_id', '=', 'institution_class_students.institution_id')
                        ->where('institution_meal_students.meal_programmes_id', $mealProgramId)
                        ->where('institution_meal_students.date', '=', $day);
                    })
                    ->leftJoin('meal_programmes', 'meal_programmes.id', '=', 'institution_meal_students.meal_programmes_id')
                    ->leftJoin('meal_received', 'meal_received.id', '=', 'institution_meal_students.meal_received_id')
                    ->leftJoin('meal_benefits', 'meal_benefits.id', '=', 'institution_meal_students.meal_benefit_id')
                    ->select(
                        'security_users.openemis_no',
                        DB::raw('CONCAT(security_users.first_name, " ", security_users.last_name) as full_name'),
                        'meal_benefits.name as meal_benefit_name',
                        'meal_received.name as meal_received_name',
                    )
                    ->groupby('institution_class_students.student_id')
                    ->get();
        return $query;
    }


    public function headings(): array
    {
        return [
            'OpenEMIS ID',
            'Name',
            'Meal Received',
            'Benefit Type'
        ];
    }
}
