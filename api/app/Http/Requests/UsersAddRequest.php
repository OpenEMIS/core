<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UsersAddRequest extends FormRequest
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
            'first_name' => 'required_without:id',
            'last_name' => 'required_without:id',
            'gender_id' => 'required_without:id',
            'date_of_birth' => 'required_without:id',
            //POCOR-9697: refuse the request outright if super_admin is sent.
            //Defense-in-depth: even with the field stripped from $fillable
            //and from setUserData, returning 422 makes the privilege-escalation
            //attempt visible in logs instead of silently dropped.
            'super_admin' => 'prohibited',
        ];
    }

    /**
     * POCOR-9697: tell the caller exactly why their request was rejected.
     */
    public function messages()
    {
        return [
            'super_admin.prohibited' => 'The super_admin field may not be set through this endpoint.',
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
