<?php

namespace Drupal\monit_drupal_connector\Controller;

use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class HealthController
{
  /**
   * Provide the all health checks data
   *
   * @return JsonResponse $payload
   */
  public function health()
  {
    $payload = [];
    // Get the available updates
    $payload['available_updates'] = [
      'label' => "Available updates",
      'description' => "Drupal's core and installed contributed modules available updates.",
      'data' => monit_drupal_connector_get_available_updates(),
    ];

    // Get other defined health checks plugins.
    $plugin_manager = \Drupal::service('plugin.manager.health_check');
    $definitions = $plugin_manager->getDefinitions();
    foreach ($definitions as $id => $definition) {
      $plugin = $plugin_manager->createInstance($id);
      $payload[$id] = [
        'label' => $plugin->label(),
        'description' => $plugin->description(),
        'data' => $plugin->data(),
      ];
    }

    // Send the payload as JSON.
    return new JsonResponse($payload);
  }

  /**
   * Checks access for a specific request.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request = NULL) {
    $config = \Drupal::config('monit_drupal_connector.adminsettings');
    $accessToken = $request ? $request->headers->get('Authorization') : $_SERVER['HTTP_AUTHORIZATION'];
    $token = $config->get('token');
    if(!$token || !$accessToken) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIf($accessToken === $token);
  }

}
