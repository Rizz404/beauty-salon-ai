<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Salon;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesCurrentSalon
{
    protected function currentSalon(Request $request): Salon
    {
        $salon = $request->user()->currentSalon();

        if (! $salon) {
            throw new HttpException(403, 'No salon is linked to this account yet.');
        }

        return $salon;
    }
}
