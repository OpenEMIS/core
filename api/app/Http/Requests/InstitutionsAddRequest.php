<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class InstitutionsAddRequest extends FormRequest
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
            'name' => 'required_without:id',
            'code' => 'required_without:id',
            'address' => 'required_without:id',
            'date_opened' => 'required_without:id',
            'year_opened' => 'required_without:id',
            'shift_type' => 'required_without:id',
            'area_id' => 'required_without:id',
            'area_administrative_id' => 'required_without:id',
            'institution_locality_id' => 'required_without:id',
            'institution_type_id' => 'required_without:id',
            'institution_ownership_id' => 'required_without:id',
            'institution_status_id' => 'required_without:id',
            'institution_sector_id' => 'required_without:id',
            'institution_provider_id' => 'required_without:id',
            'institution_gender_id' => 'required_without:id',
            'logo_content' => 'file'
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
