<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\RegistrationRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class RegistrationService extends Controller
{

    protected $registrationRepository;

    public function __construct(
    RegistrationRepository $registrationRepository) {
        $this->registrationRepository = $registrationRepository;
    }

    public function academicPeriodsList()
    {
        try {
            $data = $this->registrationRepository->academicPeriodsList()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name
                    ];
                }
            );
            
            return $data;
            
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
            $data = $this->registrationRepository->educationGradesList()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name
                    ];
                }
            );
            
            return $data;
            
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
            $data = $this->registrationRepository->institutionDropdown()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name
                    ];
                }
            );
            
            return $data;
            
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
            /*$data = $this->registrationRepository->administrativeAreasList()->map(
                function ($item, $key) {
                    return [
                        "id" => $item->id,
                        "name" => $item->name
                    ];
                }
            );*/


            $data = $this->registrationRepository->administrativeAreasList();
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Administrative Areas List Not Found');
        }
    }

}
