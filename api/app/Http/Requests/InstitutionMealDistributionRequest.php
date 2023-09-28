<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class InstitutionMealDistributionRequest extends FormRequest
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
            'academic_period_id' => 'required_without:id',
            //'institution_id' => 'required_without:id',
            'meal_programmes_id' => 'required_without:id',
            'date_received' => 'required_without:id',
            'quantity_received' => 'required_without:id',
            'delivery_status_id' => 'required_without:id',
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
                    'Enter Required fields' => $errors,
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }
}
