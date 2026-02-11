<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;

abstract class Controller
{
    protected function hashPassword(#[\SensitiveParameter] string $password): string
    {
        return Hash::make($password);
    }
}
