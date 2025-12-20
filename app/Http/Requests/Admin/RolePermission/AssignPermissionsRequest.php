<?php

namespace App\Http\Requests\Admin\RolePermission;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,id',
        ];
    }
}


