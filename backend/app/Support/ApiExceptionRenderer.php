<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders every API error as {message, errors?, code}.
 */
class ApiExceptionRenderer
{
    public function __invoke(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $exception instanceof ValidationException => $this->respond(
                $exception->getMessage(), 'validation_failed', 422, $exception->errors()
            ),
            $exception instanceof AuthenticationException => $this->respond(__('Unauthenticated.'), 'unauthenticated', 401),
            $exception instanceof AuthorizationException,
            $exception instanceof AccessDeniedHttpException => $this->respond(__('This action is unauthorized.'), 'forbidden', 403),
            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => $this->respond(__('Not found.'), 'not_found', 404),
            $exception instanceof TokenMismatchException => $this->respond(__('CSRF token mismatch.'), 'csrf_mismatch', 419),
            $exception instanceof ThrottleRequestsException => $this->respond(
                __('Too many requests.'), 'too_many_requests', 429, headers: $exception->getHeaders()
            ),
            $exception instanceof HttpExceptionInterface => $this->respond(
                $exception->getMessage() ?: __('Request failed.'), 'http_error', $exception->getStatusCode(), headers: $exception->getHeaders()
            ),
            default => config('app.debug') ? null : $this->respond(__('Server error.'), 'server_error', 500),
        };
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     * @param  array<string, string>  $headers
     */
    private function respond(string $message, string $code, int $status, ?array $errors = null, array $headers = []): JsonResponse
    {
        $payload = ['message' => $message, 'code' => $code];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return new JsonResponse($payload, $status, $headers);
    }
}
