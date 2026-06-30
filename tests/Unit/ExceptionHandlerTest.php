<?php

namespace Vandet\ApiResponse\Tests\Unit;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Exceptions\Handler;
use Vandet\ApiResponse\Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    private function makeValidationException(array $errors): ValidationException
    {
        $translator = new Translator(new ArrayLoader(), 'en');
        $validator  = new Validator($translator, [], []);
        foreach ($errors as $field => $messages) {
            foreach ((array) $messages as $message) {
                $validator->errors()->add($field, $message);
            }
        }
        return new ValidationException($validator);
    }

    public function test_maps_validation_exception_to_422_with_validation_failed(): void
    {
        $errors   = ['email' => ['Email is required.']];
        $response = Handler::handleValidation($this->makeValidationException($errors));

        $this->assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::VALIDATION_FAILED, $body['code']);
        $this->assertSame($errors, $body['errors']);
    }

    public function test_maps_authentication_exception_to_401_with_auth_token_missing(): void
    {
        $response = Handler::handleAuthentication(new AuthenticationException('Unauthenticated.'));

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::AUTH_TOKEN_MISSING, $body['code']);
    }

    public function test_maps_authorization_exception_to_403_with_auth_user_forbidden(): void
    {
        $response = Handler::handleAuthorization(new AuthorizationException('This action is unauthorized.'));

        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::AUTH_USER_FORBIDDEN, $body['code']);
    }

    public function test_maps_model_not_found_exception_to_404_with_resource_not_found(): void
    {
        $response = Handler::handleModelNotFound(new ModelNotFoundException());

        $this->assertSame(404, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::RESOURCE_NOT_FOUND, $body['code']);
    }

    public function test_maps_not_found_http_exception_to_404_with_resource_not_found(): void
    {
        $response = Handler::handleNotFound(new NotFoundHttpException());

        $this->assertSame(404, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::RESOURCE_NOT_FOUND, $body['code']);
    }

    public function test_maps_too_many_requests_exception_to_429_with_server_rate_limited(): void
    {
        $response = Handler::handleRateLimited(new TooManyRequestsHttpException());

        $this->assertSame(429, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::SERVER_RATE_LIMITED, $body['code']);
    }

    public function test_maps_throwable_to_500_with_server_unexpected_error(): void
    {
        $response = Handler::handleServerError(new RuntimeException('Something broke.'));

        $this->assertSame(500, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::SERVER_UNEXPECTED_ERROR, $body['code']);
    }

    public function test_maps_http_503_to_503_with_server_unavailable(): void
    {
        $response = Handler::handleHttpError(new HttpException(503, 'Down for maintenance.'));

        $this->assertSame(503, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::SERVER_UNAVAILABLE, $body['code']);
        $this->assertSame('Down for maintenance.', $body['message']);
    }

    public function test_maps_http_503_with_no_message_uses_translation_fallback(): void
    {
        $response = Handler::handleHttpError(new HttpException(503));

        $this->assertSame(503, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame(ErrorCodes::SERVER_UNAVAILABLE, $body['code']);
        $this->assertNotEmpty($body['message']);
    }

    public function test_maps_generic_http_exception_to_its_status_code(): void
    {
        $response = Handler::handleHttpError(new HttpException(502, 'Bad gateway.'));

        $this->assertSame(502, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::SERVER_UNEXPECTED_ERROR, $body['code']);
        $this->assertSame('Bad gateway.', $body['message']);
    }

    public function test_all_exception_responses_follow_the_standard_envelope(): void
    {
        $responses = [
            Handler::handleValidation($this->makeValidationException(['f' => ['e']])),
            Handler::handleAuthentication(new AuthenticationException()),
            Handler::handleAuthorization(new AuthorizationException()),
            Handler::handleModelNotFound(new ModelNotFoundException()),
            Handler::handleNotFound(new NotFoundHttpException()),
            Handler::handleRateLimited(new TooManyRequestsHttpException()),
            Handler::handleServerError(new RuntimeException()),
            Handler::handleHttpError(new HttpException(503)),
        ];

        foreach ($responses as $response) {
            $body = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('success', $body);
            $this->assertArrayHasKey('message', $body);
            $this->assertArrayHasKey('code', $body);
            $this->assertArrayHasKey('errors', $body);
            $this->assertFalse($body['success']);
            $this->assertArrayNotHasKey('data', $body);
        }
    }
}
