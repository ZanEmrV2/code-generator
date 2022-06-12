<?php

namespace App\Models;

use App\Models\ModelEventObserver;
use Eloquent as Model;


class AuditingBaseModel extends Model
{

    public static function boot()
    {
        parent::boot();

        $class = get_called_class();
        $class::observe(new ModelEventObserver());
    }
}
