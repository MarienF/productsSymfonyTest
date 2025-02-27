<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApiAccessDeniedListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 100],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
		$request = $event->getRequest();
		
		// Intercepter TOUTE exception pour vérifier si le listener fonctionne
		file_put_contents('var/log/debug.log', 'Exception: ' . get_class($exception) . "\n", FILE_APPEND);
		file_put_contents('var/log/debug.log', 'Path: ' . $request->getPathInfo() . "\n", FILE_APPEND);
		
		// Si c'est une exception d'accès refusé pour une API
		if (str_starts_with($request->getPathInfo(), '/api/') && $exception instanceof AccessDeniedException) {
			file_put_contents('var/log/debug.log', 'Access denied intercepté!' . "\n", FILE_APPEND);
			
			$response = new JsonResponse([
				'success' => false,
				'error' => 'Accès refusé. Vous devez être administrateur pour effectuer cette action.'
			], 403);
			
			$event->setResponse($response);
		}
    }
}