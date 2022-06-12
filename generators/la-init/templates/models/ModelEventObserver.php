<?php

namespace App\Models;

class ModelEventObserver
{
    private $userName;

    public function __construct()
    {
        $user = auth()->user();

        $this->userName = isset($user) ? $user['first_name'] . ' ' . $user['last_name'] : 'no-user';
    }

    public function creating($model)
    {

        $model->created_by =  $this->userName;
    }

    public function created($model)
    {
        //Track if enable
    }

    public function updating($model)
    {
        $model->updated_by = $this->userName;
    }

    public function updated($model)
    {
        //Track if enable
    }

    public function deleting($model)
    {
        //
    }

    public function deleted($model)
    {
        //Track if enable
    }
}
