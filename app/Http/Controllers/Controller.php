<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

abstract class Controller
{
    public function index()
    {
        $classname = class_basename(static::class);

        $base = str_replace('Controller', '', $classname);

        return view('pages.'.Str::plural(Str::kebab($base)).'.index');
    }
}
