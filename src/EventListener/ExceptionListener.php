<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {

        if ($event->getRequest()->headers->has('Content-Type')
            && $event->getRequest()->headers->get('Content-Type') === 'application/json'
        ) {
            $exception = $event->getThrowable();

            $response = new JsonResponse([
                'code' => $exception instanceof NotFoundHttpException ? $exception->getStatusCode() : $exception->getCode(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ]);
            $response->setStatusCode(200);
            $event->setResponse($response);
            $event->allowCustomResponseCode();

        }
    }
}
