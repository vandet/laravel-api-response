<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Exceptions\Handler;

it('maps ValidationException to 422 with VALIDATION_FAILED', function () {
    $errors   = ['email' => ['Email is required.']];
    $e        = ValidationException::withMessages($errors);
    $response = Handler::handleValidation($e);

    expect($response->getStatusCode())->toBe(422);

    $body = json_decode($response->getContent(), true);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::VALIDATION_FAILED);
    expect($body['errors'])->toBe($errors);
});

it('maps AuthenticationException to 401 with AUTH_TOKEN_MISSING', function () {
    $e        = new AuthenticationException('Unauthenticated.');
    $response = Handler::handleAuthentication($e);

    expect($response->getStatusCode())->toBe(401);

    $body = json_decode($response->getContent(), true);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::AUTH_TOKEN_MISSING);
});

it('maps AuthorizationException to 403 with AUTH_USER_FORBIDDEN', function () {
    $e        = new AuthorizationException('This action is unauthorized.');
    $response = Handler::handleAuthorization($e);

    expect($response->getStatusCode())->toBe(403);

    $body = json_decode($response->getContent(), true);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::AUTH_USER_FORBIDDEN);
});

it('maps ModelNotFoundException to 404 with RESOURCE_NOT_FOUND', function () {
    $e        = new ModelNotFoundException();
    $response = Handler::handleModelNotFound($e);

    expect($response->getStatusCode())->toBe(404);

    $body = json_decode($response->getContent(), true);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::RESOURCE_NOT_FOUND);
});

it('maps NotFoundHttpException to 404 with RESOURCE_NOT_FOUND', function () {
    $e        = new NotFoundHttpException();
    $response = Handler::handleNotFound($e);

    expect($response->getStatusCode())->toBe(404);

    $body = json_decode($response->getContent(), true);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::RESOURCE_NOT_FOUND);
});

it('maps TooManyRequestsHttpException to 429 with SERVER_RATE_LIMITED', function () {
    $e        = new TooManyRequestsHttpException();
    $response = Handler::handleRateLimited($e);

    expect($response->getStatusCode())->toBe(429);

    $body = json_decode($response->getContent(), true);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::SERVER_RATE_LIMITED);
});

it('maps Throwable to 500 with SERVER_UNEXPECTED_ERROR', function () {
    $e        = new RuntimeException('Something broke.');
    $response = Handler::handleServerError($e);

    expect($response->getStatusCode())->toBe(500);

    $body = json_decode($response->getContent(), true);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::SERVER_UNEXPECTED_ERROR);
});

it('all exception responses follow the standard envelope', function () {
    $responses = [
        Handler::handleValidation(ValidationException::withMessages(['f' => ['e']])),
        Handler::handleAuthentication(new AuthenticationException()),
        Handler::handleAuthorization(new AuthorizationException()),
        Handler::handleModelNotFound(new ModelNotFoundException()),
        Handler::handleNotFound(new NotFoundHttpException()),
        Handler::handleRateLimited(new TooManyRequestsHttpException()),
        Handler::handleServerError(new RuntimeException()),
    ];

    foreach ($responses as $response) {
        $body = json_decode($response->getContent(), true);
        expect($body)->toHaveKeys(['success', 'message', 'code', 'errors']);
        expect($body['success'])->toBeFalse();
        expect($body)->not->toHaveKey('data');
    }
});
