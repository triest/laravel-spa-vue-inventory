<?php

namespace App\Http\Requests\Equipment;

use Illuminate\Foundation\Http\FormRequest;

class CreateEquipmentRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
                'code' => 'required|unique:equipment,code|max:255',
                'serial.*' => 'required|max:255',
                'code_type' => 'required|exists:equipment_types,code',
                'comment' => 'max:255',
        ];
    }
}
