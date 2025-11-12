<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Redirect;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class CustomVerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
        {
            return Redirect()->route('profile.edit');
        }
}
