<?php

namespace Apisearch\Listener;

use Apisearch\Model\ApisearchBuilder;
use Apisearch\Model\ApisearchConnection;
use Apisearch\Model\ApisearchHooks;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ProductUpdateListener
 */
class ProductUpdateListener implements EventSubscriberInterface
{
    /**
     * @param FilterResponseEvent $event
     * @return void
     */
    public function pushToApisearch(FilterResponseEvent $event)
    {
        $hooks = new ApisearchHooks(
            new ApisearchBuilder(),
            new ApisearchConnection()
        );
        $request = $event->getRequest();
        $controllerName = $request->attributes->get('_controller');
        $method = $request->getMethod();
        if (
            $controllerName == "PrestaShopBundle\\Controller\\Admin\\ProductController::formAction" &&
            $method !== "GET"
        ) {
            $productId = $request->get('id');
            if (!empty($productId)) {
                $hooks->putProductById($productId);
            }
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'pushToApisearch'
        ];
    }
}
