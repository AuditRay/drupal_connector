<?php

namespace Drupal\monit\Plugin\HealthCheck;

use Drupal\monit\Plugin\HealthCheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
Use Drupal\Core\Link;

/**
 * @HealthCheck(
 *   id = "monit_security_review",
 *   label = @Translation("Monit Security Review"),
 *   description = @Translation("Gathering Security Review data.")
 * )
 */
class MonitSecurityReview extends HealthCheckPluginBase {

  /**
   * The Security Review manager service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected $securityReview;

  /**
   * The Security Review manager service.
   *
   * @var \Drupal\security_review\SecurityReviewManager
   */
  protected $securityReviewPluginManager;

  /**
   * Constructs a SecurityReview object.
   *
   * @param \Drupal\security_review\SecurityReview $security_review_manager
   *   The Security Review manager service.
   */
  public function __construct() {
    $this->securityReview = \Drupal::service('security_review');
    $this->securityReviewPluginManager = \Drupal::service('plugin.manager.security_review.security_check');
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('security_review')
    );
  }

  /**
   * Set the Health Check label.
   *
   * @return string
   */
  public function label()
  {
    return 'Security Review Health Check Data';
  }

  /**
   * Set the Health Check descriptiion.
   *
   * @return string
   */
  public function description()
  {
    return 'This health check will return all the findings of the security review module.';
  }

  /**
   * Set the payload data.
   *
   * @return array $payload
   */
  public function data()
  {
    $results = [];
    $checks = $this->securityReviewPluginManager->getChecks();
    $this->securityReview->runChecks($checks);
    $this->securityReview->setLastRun(time());
    $definitions = $this->securityReviewPluginManager->getDefinitions();
    foreach ($definitions as $id => $definition) {
      $plugin = $this->securityReviewPluginManager->createInstance($id);
      $lastResult = $plugin->lastResult();
      $resultDetails = $plugin->getDetails($lastResult['findings'], $lastResult['hushed']);
      foreach ($resultDetails as $resultDetail) {
        foreach ($resultDetail['#paragraphs'] as $paragraph) {
          if ($paragraph instanceof Link) {
            $details[] = $paragraph->toString();
          }
          else {
            $details[] = $paragraph->render();
          }
        }
      }
      $results[] = $details;

      $help_text = $plugin->getHelp();
      foreach ($help_text['#paragraphs'] as $paragraph) {
        $helpDetails[] = $paragraph->render();
      }
      $help = [
        'title' => $help_text['#title']->render(),
        'decription' => $helpDetails,
      ];
      $payload[] = [
        'title' => $plugin->getTitle(),
        'help_text' => $help,
        'description' => $plugin->getDescription(),
        'namespace' => $plugin->getNamespace(),
        'status' => $plugin->getStatusMessage(),
        'result' => $results,
        'time' => $this->securityReview->getLastRun(),
      ];
    }

    return $payload;
  }

}
