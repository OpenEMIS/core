<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AssessmentGradingOptions;
use App\Models\AssessmentGradingTypes;
use App\Models\AssessmentItem;
use App\Models\AssessmentPeriod;
use App\Models\Assessments;
use App\Models\ConfigItem;
use App\Models\InstitutionSubjectStaff;
use App\Models\InstitutionSubjectStudents;
use App\Models\InstitutionSubjects;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;

class SystemConfigurationRepository
{

    public function getAllConfigurationItems()
    {
        return ConfigItem::with('itemOptions')->get();
    }

    public function getConfigurationItemById($configId)
    {
        return ConfigItem::with('itemOptions')->where('id', $configId)->get();
    }

}