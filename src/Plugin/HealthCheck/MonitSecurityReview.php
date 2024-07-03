<?php

namespace Drupal\monit_drupal_connector\Plugin\HealthCheck;

use Drupal\monit_drupal_connector\Plugin\HealthCheckPluginBase;
use Drupal\Core\Link;
use Drupal\security_review\CheckResult;

/**
 * @HealthCheck(
 *   id = "monit_security_review",
 *   label = @Translation("Monit Security Review"),
 *   description = @Translation("Gathering Security Review data.")
 * )
 */
class MonitSecurityReview extends HealthCheckPluginBase {

  /**
   * The current plugin ID.
   *
   * @var string $pluginId
   */
  protected $pluginId;
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
   * Constructs a MonitSecurityReview object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->securityReview = \Drupal::service('security_review');
    $this->pluginId = $plugin_id;
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
      $checklist = \Drupal::service('security_review.checklist');
      $checks = $checklist->getEnabledChecks();
      foreach ($checks as $check) {
          // Run the check.
          $results = array_merge($results, $checklist->runChecks([$check]));
      }


      $payload = [];
      if (!empty($results)) {
          $this->securityReview->setLastRun(time());
          $checklist->storeResults($results);
          foreach ($results as $result) {
              $help = $result->check()->help();
              $helpDetails = [];
              $result_number = $result->result();
              $resultStatus = '';
              switch ($result_number) {
                  case CheckResult::SUCCESS:
                      $resultStatus = 'success';
                      break;

                  case CheckResult::FAIL:
                      $resultStatus = 'fail';
                      break;

                  case CheckResult::WARN:
                      $resultStatus = 'warning';
                      break;

                  case CheckResult::INFO:
                      $resultStatus = 'info';
                      break;
              }

              foreach ($help['#paragraphs'] as $paragraph) {
                  if ($paragraph instanceof Link) {
                      $helpDetails[] = $paragraph->toString();
                  }
                  else {
                    $helpDetails[]= $paragraph;
                  }
              }
              $payload[] = [
                  'id' => $this->pluginId . '_' . $result->check()->id(),
                  'label' => $result->check()->getTitle(),
                  'description' => $result->resultMessage()->render(),
                  'status' => $resultStatus,
                  'statusDescription' => 'a string that indicates the success label',
                  'namespace' => $result->check()->getNamespace(),
                  'detailsTitle' => $result->resultMessage()->render(),
                  'detailsText' => $helpDetails,
                  'detailsFindings' => [],
                  'detailsExtra' => [
                      'findings' =>  $result->findings(),
                  ]
              ];

          }
      }
      return $payload;
  }

}
