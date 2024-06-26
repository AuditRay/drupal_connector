<?php

namespace Drupal\monit_drupal_connector\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface HealthCheckInterface extends PluginInspectionInterface {

  /**
   * Get Monit access token from the module's configuration.
   */
  public function token();

  /**
   * Set the label of the health check.
   */
  public function label();

  /**
   * Set the description of the health check.
   */
  public function description();

  /**
   * Set the payload data of the health check.
   */
  public function data();
}
