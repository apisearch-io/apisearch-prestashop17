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
        try {
            $request = $event->getRequest();
            $controllerName = $request->attributes->get('_controller');
            $method = $request->getMethod();
            if (
                \Configuration::get('AS_REAL_TIME_INDEXATION') == 1 &&
                $controllerName == "PrestaShopBundle\\Controller\\Admin\\ProductController::formAction" &&
                $method !== "GET"
            ) {
                $productId = $request->get('id');

                $hooks = new ApisearchHooks(
                    new ApisearchBuilder(),
                    new ApisearchConnection()
                );

                if (!empty($productId)) {
                    $hooks->putProductById($productId);
                }
            }
        } catch (\Throwable $throwable) {
            // An error here should not affect the whole process
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
