<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;

class RoleController extends CrudController
{
    protected $table = 'role';

    protected $modelClass = Role::class;

    protected $restricted = ['delete'];

    protected function getTable()
    {
        return $this->table;
    }

    protected function getModelClass()
    {
        return $this->modelClass;
    }

    protected function getReadAllQuery(): Builder
    {
        // Order role by alphabetic name
        return $this->model()->orderBy('name', 'asc');
    }

    protected function afterCreateOne() {}

    protected function beforeDeleteOne() {}
}
