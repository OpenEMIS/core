<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AcademicPeriodListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $action_type = $this->action_type;
        $param = [];
        $param['action_type'] = 'required';

        if($action_type == 'WeeksForPeriod'){
            $param['academic_period_id'] = 'required';
        }

        if($action_type == 'DaysForPeriodWeek'){
            $param['academic_period_id'] = 'required';
            $param['week_id'] = 'required';
            $param['institution_id'] = 'required';
            $param['school_closed_required'] = 'required';
        }

        return $param;
        
    }


    //* @param Validator $validator
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            response()->json([
                'Enter Required Fileds' => $errors,
            ],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        )
        );
    }
}
