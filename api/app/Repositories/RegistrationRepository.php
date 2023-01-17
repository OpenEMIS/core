<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\AcademicPeriod;
use App\Models\EducationGrades;
use App\Models\Institutions;
use App\Models\AreaAdministratives;

class RegistrationRepository extends Controller
{


    public function academicPeriodsList()
    {
        try {
            $academicPeriods = AcademicPeriod::select('id', 'name')->orderBy('id','DESC')->get();
            
            return $academicPeriods;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Academic Period List Not Found');
        }
    }

    public function educationGradesList()
    {
        try {
            $educationGrades = EducationGrades::select('id', 'name')->get();
            
            return $educationGrades;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
        }
    }


    public function institutionDropdown()
    {
        try {
            $educationGrades = Institutions::select('id', 'name')->get();
            
            return $educationGrades;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function administrativeAreasList()
    {
        try {
            $areaAdministratives = AreaAdministratives::select('id', 'name', 'parent_id')->with('areaAdministrativesChild:id,name,parent_id')->get();
            
            return $areaAdministratives;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administratives List Not Found');
        }
    }
}

