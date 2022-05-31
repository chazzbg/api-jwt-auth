<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {

        $exception = $event->getThrowable();
        $response = new JsonResponse([
            'code' => $event->getResponse()? $event->getResponse()->getStatusCode() : $exception->getCode(),
            'message' => $exception->getMessage(),
            'trace'=> $exception->getTrace()
        ]);
        $response->setStatusCode(200);
        $event->setResponse($response);
        $event->allowCustomResponseCode();

        if ($event->getRequest()->headers->has('Content-Type')
            && $event->getRequest()->headers->get('Content-Type') === 'application/json'
        ) {

        }
    }
}
