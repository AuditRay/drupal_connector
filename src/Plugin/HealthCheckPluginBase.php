<?php

namespace Drupal\monit\Plugin;

use Drupal\Component\Plugin\PluginBase;

abstract class HealthCheckPluginBase extends PluginBase implements HealthCheckInterface {

  protected $token;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $config = \Drupal::config('monit.adminsettings');
    $this->token = $config->get('token');
  }

  /**
   * {@inheritdoc}
   */
  public function token() {
    return $this->token;
  }

   /**
   * {@inheritdoc}
   */
  public function label() {
    // Default implementation of label().
  }

   /**
   * {@inheritdoc}
   */
  public function description() {
    // Default implementation of description().
  }

   /**
   * {@inheritdoc}
   */
  public function data() {
    // Default implementation of data().
  }

}
