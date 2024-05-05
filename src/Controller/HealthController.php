<?php

namespace Drupal\monit\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class HealthController
{

  public function health()
  {
    $data = monit_push_modules_updates(false);
    return new JsonResponse($data);
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $config = \Drupal::config('monit.adminsettings');
    $accessToken = \Drupal::request()->request->get('token');
    $token = $config->get('token');
    return AccessResult::allowedIf($accessToken === $token);
  }

}
