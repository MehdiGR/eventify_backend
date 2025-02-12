<?php

namespace App\Http\Controllers;

use Event;

class EventController extends CrudController
{
    protected $table = 'events';

    protected $modelClass = Event::class;

    protected $restricted = ['create', 'read', 'update', 'delete'];

    protected function getTable()
    {
        return $this->table;
    }

    protected function getModelClass()
    {
        return $this->modelClass;
    }
}
