<?php

namespace Drupal\sitesettings_api\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class SiteSettingsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = [
      $collection->get('system.site_information_settings'),
    ];
    foreach ($routes as $route) {
      if ($route) {
        // Extend core site config form.
        $route->setDefault('_form', 'Drupal\sitesettings_api\Form\SiteSettingsFormExtended');
      }
    }
  }

}
