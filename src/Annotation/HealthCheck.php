<?php

declare(strict_types=1);

namespace Drupal\monit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines health_check annotation object.
 *
 * @Annotation
 */
final class HealthCheck extends Plugin {

  /**
   * The plugin ID.
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
