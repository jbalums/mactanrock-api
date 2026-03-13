<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_can_be_requested()
    {
        $this->markTestSkipped('Breeze password-reset flow is not part of the current token-auth API baseline.');
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        $this->markTestSkipped('Breeze password-reset flow is not part of the current token-auth API baseline.');
    }
}
