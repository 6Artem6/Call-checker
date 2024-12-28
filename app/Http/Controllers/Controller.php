<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    public function boot()
    {
        Inertia::share([
            'csrf_token' => function () {
                return csrf_token();
            },
        ]);
    }
}
