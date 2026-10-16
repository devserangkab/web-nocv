<?php

namespace App\Actions\Management;

use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UpdateRole
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Role $role, array $data): Role
    {
        Authorize::managementManage();
        $data = Validator::make($data, CreateRole::rules($role->id), CreateRole::messages())->validate();

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions']);

        return $role;
    }
}
