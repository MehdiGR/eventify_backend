<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\Group;

class GroupController extends CrudController
{
    protected $model = Group::class;

    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function getTable(): string
    {
        return 'groups'; // Table name
    }

    public function getModelClass(): string
    {
        return Group::class; // Model class name
    }
}
