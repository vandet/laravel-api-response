<?php

use Vandet\ApiResponse\Constants\ErrorCodes;

it('defines all authentication error codes', function () {
    expect(ErrorCodes::AUTH_TOKEN_EXPIRED)->toBe('AUTH_TOKEN_EXPIRED');
    expect(ErrorCodes::AUTH_TOKEN_INVALID)->toBe('AUTH_TOKEN_INVALID');
    expect(ErrorCodes::AUTH_TOKEN_MISSING)->toBe('AUTH_TOKEN_MISSING');
    expect(ErrorCodes::AUTH_USER_UNAUTHORIZED)->toBe('AUTH_USER_UNAUTHORIZED');
    expect(ErrorCodes::AUTH_USER_FORBIDDEN)->toBe('AUTH_USER_FORBIDDEN');
    expect(ErrorCodes::AUTH_USER_SUSPENDED)->toBe('AUTH_USER_SUSPENDED');
    expect(ErrorCodes::AUTH_USER_UNVERIFIED)->toBe('AUTH_USER_UNVERIFIED');
    expect(ErrorCodes::AUTH_SESSION_EXPIRED)->toBe('AUTH_SESSION_EXPIRED');
    expect(ErrorCodes::AUTH_MFA_REQUIRED)->toBe('AUTH_MFA_REQUIRED');
});

it('defines validation error code', function () {
    expect(ErrorCodes::VALIDATION_FAILED)->toBe('VALIDATION_FAILED');
});

it('defines all user error codes', function () {
    expect(ErrorCodes::USER_NOT_FOUND)->toBe('USER_NOT_FOUND');
    expect(ErrorCodes::USER_EMAIL_DUPLICATE)->toBe('USER_EMAIL_DUPLICATE');
    expect(ErrorCodes::USER_EMAIL_INVALID)->toBe('USER_EMAIL_INVALID');
    expect(ErrorCodes::USER_PASSWORD_WEAK)->toBe('USER_PASSWORD_WEAK');
    expect(ErrorCodes::USER_ROLE_NOT_FOUND)->toBe('USER_ROLE_NOT_FOUND');
});

it('defines all resource error codes', function () {
    expect(ErrorCodes::RESOURCE_NOT_FOUND)->toBe('RESOURCE_NOT_FOUND');
    expect(ErrorCodes::RESOURCE_ALREADY_EXISTS)->toBe('RESOURCE_ALREADY_EXISTS');
    expect(ErrorCodes::RESOURCE_CONFLICT)->toBe('RESOURCE_CONFLICT');
    expect(ErrorCodes::RESOURCE_LOCKED)->toBe('RESOURCE_LOCKED');
});

it('defines all bulk operation error codes', function () {
    expect(ErrorCodes::BULK_PARTIAL_FAILURE)->toBe('BULK_PARTIAL_FAILURE');
    expect(ErrorCodes::BULK_ALL_FAILED)->toBe('BULK_ALL_FAILED');
    expect(ErrorCodes::BULK_LIMIT_EXCEEDED)->toBe('BULK_LIMIT_EXCEEDED');
});

it('defines all server error codes', function () {
    expect(ErrorCodes::SERVER_UNEXPECTED_ERROR)->toBe('SERVER_UNEXPECTED_ERROR');
    expect(ErrorCodes::SERVER_UNAVAILABLE)->toBe('SERVER_UNAVAILABLE');
    expect(ErrorCodes::SERVER_RATE_LIMITED)->toBe('SERVER_RATE_LIMITED');
    expect(ErrorCodes::SERVER_TIMEOUT)->toBe('SERVER_TIMEOUT');
    expect(ErrorCodes::SERVER_MAINTENANCE)->toBe('SERVER_MAINTENANCE');
});

it('defines all codes in SCREAMING_SNAKE_CASE format', function () {
    $reflection = new ReflectionClass(ErrorCodes::class);
    foreach ($reflection->getConstants() as $name => $value) {
        expect($value)->toMatch('/^[A-Z][A-Z0-9_]+$/')
            ->and($name)->toBe($value);
    }
});
