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
    public function health(Request $request)
    {
        $pluginsList = $request->request->get('plugins');
        if(empty($pluginsList)) {
            $pluginsList = 'all';
        }
        $pluginsListArray = explode(',', $pluginsList);
        $payload = [];
        // Get the available updates
        $payload['available_updates'] = [
            'label' => "Available updates",
            'description' => "Drupal's core and installed contributed modules available updates.",
            'data' => monit_drupal_connector_get_available_updates(),
            'error' => "",
        ];

        // Get other defined health checks plugins.
        $plugin_manager = \Drupal::service('plugin.manager.health_check');
        $definitions = $plugin_manager->getDefinitions();
        foreach ($definitions as $id => $definition) {
            if ($id === 'monit_phpinfo') continue;
            if ($pluginsListArray && !in_array($id, $pluginsListArray) && !in_array('all', $pluginsListArray)) {
                continue;
            }
            $plugin = $plugin_manager->createInstance($id);
            $data = [];
            $error = "";
            try {
                $data = $plugin->data();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
            $payload[$id] = [
                'label' => $plugin->label(),
                'description' => $plugin->description(),
                'data' => $data,
                'error' => $error,
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
    public function access(Request $request) {
        $config = \Drupal::config('monit_drupal_connector.adminsettings');
        $accessToken = $request ? $request->headers->get('Authorization') : $_SERVER['HTTP_AUTHORIZATION'];
        $tokenKey = $config->get('token');
        try {
            $token = \Drupal::service('key.repository')->getKey($tokenKey);
            if (!$token || !$accessToken || !$token->getKeyValue()) {
                return AccessResult::forbidden();
            }
            return AccessResult::allowedIf($accessToken === $token->getKeyValue());
        } catch (\Exception $e) {
            return AccessResult::forbidden();
        }
    }

}
