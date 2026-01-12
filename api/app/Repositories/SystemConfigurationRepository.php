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

    public function getAllConfigurationItems($params)
    {
        $items = ConfigItem::with('itemOptions');

        if(isset($params['order'])){
            $orderBy = $params['order_by']??"ASC";
            $col = $params['order'];
            $items = $items->orderBy($col, $orderBy);
        }

        if (isset($params['limit'])) {
            $items = $items->paginate($params['limit']);
        } else {
            $items = $items->get();
        }

        return $items;
    }

    public function getConfigurationItemById($configId)
    {
        return ConfigItem::with('itemOptions')->where('id', $configId)->get();
    }

}