<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StaffAttendanceRequest extends FormRequest
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
        return [
            'academic_period_id' => 'required',
            'institution_id' => 'required',
            'week_id' => 'required',
            'week_start_day' => 'required',
            'week_end_day' => 'required',
            'day_id' => 'required',
            'shift_id' => 'required',
            'day_date' => 'required',
        ];
        
    }


    //* @param Validator $validator
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            response()->json([
                'message' => "Unsuccessful.",
                'Enter Required Fileds' => $errors,
            ],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        )
        );
    }
}
