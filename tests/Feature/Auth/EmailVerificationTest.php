<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_can_be_verified()
    {
        $this->markTestSkipped('Breeze email-verification flow is not part of the current token-auth API baseline.');
    }

    public function test_email_is_not_verified_with_invalid_hash()
    {
        $this->markTestSkipped('Breeze email-verification flow is not part of the current token-auth API baseline.');
    }
}
