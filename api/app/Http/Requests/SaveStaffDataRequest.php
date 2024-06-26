<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SaveStaffDataRequest extends FormRequest
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
            //'institution_id' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'gender_id' => 'required',
            'date_of_birth' => 'required',
            //'academic_period_id' => 'required',
            //'staff_type_id' => 'required',
            //'start_date' => 'required',
            //'end_date' => 'required',
            //'staff_position_grade_id' => ['required_unless:is_same_school,1'],
            //'institution_position_id' => ['required_unless:is_same_school,1']
        ];
    }



    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            response()->json(
                [
                    'message' => "Unsuccessful.",
                    'Enter Required fields' => $errors,
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }
}
