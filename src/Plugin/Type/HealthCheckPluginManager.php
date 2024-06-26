<?php

namespace Drupal\monit_drupal_connector\Plugin\Type;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * HealthCheck plugin manager.
 */
final class HealthCheckPluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/HealthCheck', $namespaces, $module_handler, 'Drupal\monit_drupal_connector\Plugin\HealthCheckInterface', 'Drupal\monit_drupal_connector\Annotation\HealthCheck');
    $this->alterInfo('health_check_info');
    $this->setCacheBackend($cache_backend, 'health_check_plugins');
  }

}
