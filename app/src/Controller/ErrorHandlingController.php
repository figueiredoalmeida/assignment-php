<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ErrorHandlingController extends \Exception
{
    /**
     * ErrorController
     */
    public function notFoundHttpAction(): Response
    {
        return new JsonResponse(['message' => BaseApiController::ERROR_EXCEPTION_MESSAGE], Response::HTTP_NOT_FOUND);

    }
}