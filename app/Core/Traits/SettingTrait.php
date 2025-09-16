<?php

namespace App\Core\Traits;

use Illuminate\Support\Facades\Auth;


trait SettingTrait
{
    protected function setAppLocale(string $lang)
    {
        \App::setLocale($lang);
    }
}
