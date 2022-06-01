<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        /** @var JsonResponse $response */
        $response = $event->getResponse();
        if ($response instanceof JsonResponse) {
            $data = json_decode($response->getContent(), true);
            if (!in_array($response->getStatusCode(), [200, 201])) {
                $data['code'] = $response->getStatusCode();
            }
            $response->setData($data);
        }
        $response->setStatusCode(200);
    }
}
