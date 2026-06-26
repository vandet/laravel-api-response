<?php

namespace Vandet\ApiResponse\Tests\Unit;

use ReflectionClass;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Tests\TestCase;

class ErrorCodesTest extends TestCase
{
    public function test_defines_all_authentication_error_codes(): void
    {
        $this->assertSame('AUTH_TOKEN_EXPIRED', ErrorCodes::AUTH_TOKEN_EXPIRED);
        $this->assertSame('AUTH_TOKEN_INVALID', ErrorCodes::AUTH_TOKEN_INVALID);
        $this->assertSame('AUTH_TOKEN_MISSING', ErrorCodes::AUTH_TOKEN_MISSING);
        $this->assertSame('AUTH_USER_UNAUTHORIZED', ErrorCodes::AUTH_USER_UNAUTHORIZED);
        $this->assertSame('AUTH_USER_FORBIDDEN', ErrorCodes::AUTH_USER_FORBIDDEN);
        $this->assertSame('AUTH_USER_SUSPENDED', ErrorCodes::AUTH_USER_SUSPENDED);
        $this->assertSame('AUTH_USER_UNVERIFIED', ErrorCodes::AUTH_USER_UNVERIFIED);
        $this->assertSame('AUTH_SESSION_EXPIRED', ErrorCodes::AUTH_SESSION_EXPIRED);
        $this->assertSame('AUTH_MFA_REQUIRED', ErrorCodes::AUTH_MFA_REQUIRED);
    }

    public function test_defines_validation_error_code(): void
    {
        $this->assertSame('VALIDATION_FAILED', ErrorCodes::VALIDATION_FAILED);
    }

    public function test_defines_all_user_error_codes(): void
    {
        $this->assertSame('USER_NOT_FOUND', ErrorCodes::USER_NOT_FOUND);
        $this->assertSame('USER_EMAIL_DUPLICATE', ErrorCodes::USER_EMAIL_DUPLICATE);
        $this->assertSame('USER_EMAIL_INVALID', ErrorCodes::USER_EMAIL_INVALID);
        $this->assertSame('USER_PASSWORD_WEAK', ErrorCodes::USER_PASSWORD_WEAK);
        $this->assertSame('USER_ROLE_NOT_FOUND', ErrorCodes::USER_ROLE_NOT_FOUND);
    }

    public function test_defines_all_resource_error_codes(): void
    {
        $this->assertSame('RESOURCE_NOT_FOUND', ErrorCodes::RESOURCE_NOT_FOUND);
        $this->assertSame('RESOURCE_ALREADY_EXISTS', ErrorCodes::RESOURCE_ALREADY_EXISTS);
        $this->assertSame('RESOURCE_CONFLICT', ErrorCodes::RESOURCE_CONFLICT);
        $this->assertSame('RESOURCE_LOCKED', ErrorCodes::RESOURCE_LOCKED);
    }

    public function test_defines_all_bulk_operation_error_codes(): void
    {
        $this->assertSame('BULK_PARTIAL_FAILURE', ErrorCodes::BULK_PARTIAL_FAILURE);
        $this->assertSame('BULK_ALL_FAILED', ErrorCodes::BULK_ALL_FAILED);
        $this->assertSame('BULK_LIMIT_EXCEEDED', ErrorCodes::BULK_LIMIT_EXCEEDED);
    }

    public function test_defines_all_server_error_codes(): void
    {
        $this->assertSame('SERVER_UNEXPECTED_ERROR', ErrorCodes::SERVER_UNEXPECTED_ERROR);
        $this->assertSame('SERVER_UNAVAILABLE', ErrorCodes::SERVER_UNAVAILABLE);
        $this->assertSame('SERVER_RATE_LIMITED', ErrorCodes::SERVER_RATE_LIMITED);
        $this->assertSame('SERVER_TIMEOUT', ErrorCodes::SERVER_TIMEOUT);
        $this->assertSame('SERVER_MAINTENANCE', ErrorCodes::SERVER_MAINTENANCE);
    }

    public function test_all_codes_are_in_screaming_snake_case(): void
    {
        $reflection = new ReflectionClass(ErrorCodes::class);

        foreach ($reflection->getConstants() as $name => $value) {
            $this->assertMatchesRegularExpression('/^[A-Z][A-Z0-9_]+$/', $value);
            $this->assertSame($name, $value);
        }
    }
}
