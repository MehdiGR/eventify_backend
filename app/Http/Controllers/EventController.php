<?php

namespace App\Http\Controllers;

use Event;

class EventController extends CrudController
{
    protected $table = 'events';

    protected $restricted = ['create', 'update', 'delete']; // Restrict certain actions

    protected $modelClass = Event::class;

    protected function getTable()
    {
        return $this->table;
    }

    protected function getModelClass()
    {
        return $this->modelClass;
    }
}
