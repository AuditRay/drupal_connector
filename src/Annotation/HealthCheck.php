<?php

declare(strict_types=1);

namespace Drupal\monit_drupal_connector\Annotation;

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
  public readonly string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public readonly string $label;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public readonly string $description;

}
