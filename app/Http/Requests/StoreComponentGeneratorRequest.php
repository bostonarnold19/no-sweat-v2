<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComponentGeneratorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'is_update_model_class' => ['sometimes'],
            'is_update_migration_file' => ['sometimes'],
            'is_create_includes' => ['sometimes'],
            'components' => ['sometimes'],
            'module' => ['required', 'string'],
            'model' => ['required', 'string'],
        ];
    }

    /**
    * Modify input data
    *
    * @return array
    */
    public function getSanitized(): array
    {
        $sanitized = $this->validated();

        if (!empty($sanitized['components'])) {
            $sanitized['components'] = collect(json_decode($sanitized['components']));
        }

        if(!empty($sanitized['is_update_model_class'])) {
            $sanitized['is_update_model_class'] = true;
        } else {
            $sanitized['is_update_model_class'] = false;
        }

        if(!empty($sanitized['is_update_migration_file'])) {
            $sanitized['is_update_migration_file'] = true;
        } else {
            $sanitized['is_update_migration_file'] = false;
        }

        if(!empty($sanitized['is_create_includes'])) {
            $sanitized['is_create_includes'] = true;
        } else {
            $sanitized['is_create_includes'] = false;
        }

        $sanitized['files_to_update'] = [
            'is_update_migration_file' => $sanitized['is_update_migration_file'],
            'is_create_includes' => $sanitized['is_create_includes'],
            'is_update_model_class' => $sanitized['is_update_model_class'],
        ];

        unset($sanitized['is_update_migration_file']);
        unset($sanitized['is_create_includes']);
        unset($sanitized['is_update_model_class']);
        //Add your code for manipulation with request data here

        return $sanitized;
    }
}
