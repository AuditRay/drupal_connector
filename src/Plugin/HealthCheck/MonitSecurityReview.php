<?php

namespace Drupal\monit\Plugin\HealthCheck;

use Drupal\monit\Plugin\HealthCheckPluginBase;
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
    try {
      $this->securityReviewPluginManager = \Drupal::service('plugin.manager.security_review.security_check');
    } catch (\Exception $e) {
      $this->securityReviewPluginManager = NULL;
    }
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
    //Drupal 8 Compatibility
    if (!$this->securityReviewPluginManager) {
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
              $helpDetails[]= $paragraph->render();
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
    $checks = $this->securityReviewPluginManager->getChecks();
    $this->securityReview->runChecks($checks);
    $this->securityReview->setLastRun(time());
    $definitions = $this->securityReviewPluginManager->getDefinitions();
    $data = [];
    $helpDetails = [];
    foreach ($definitions as $id => $definition) {
      $plugin = $this->securityReviewPluginManager->createInstance($id);
      $lastResult = $plugin->lastResult();
      $result_number = $lastResult['result'];
      $details = [];
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
      $resultMessage = $plugin->getStatusMessage($result_number);
      $resultDetails = $plugin->getDetails($lastResult['findings'], $lastResult['hushed']);
      foreach ($resultDetails as $resultDetail) {
        foreach ($resultDetail['#paragraphs'] as $paragraph) {
          if ($paragraph instanceof Link) {
            $details[] = [
              "type" => "link",
              "value" => $paragraph->toString()
            ];
          }
          else {
            $details[] = [
              "type" => "paragraph",
              "value" => $paragraph->render()
            ];
          }
        }
        if (isset($resultDetail['#items'])) {
          $items = [];
          foreach ($resultDetail['#items'] as $item) {
            $items[] = $item;
          }
          $details[] = [
            "type" => "list",
            "items" => $items
          ];
        }
      }

      $help_text = $plugin->getHelp();
      foreach ($help_text['#paragraphs'] as $paragraph) {
        $helpDetails[] = $paragraph->render();
      }
      $data[] = [
        'id' => $this->pluginId . '_' . $id,
        'label' => $plugin->getTitle(),
        'description' => $plugin->getDescription(),
        'status' => $resultStatus,
        'statusDescription' => 'a string that indicates the success label',
        'namespace' => $plugin->getNamespace(),
        'detailsTitle' => $resultMessage,
        'detailsText' => $helpDetails,
        'detailsFindings' => $details,
        'detailsExtra' => [
          'findings' => array_merge($lastResult['findings'], $lastResult['hushed']),
        ]
      ];
    }

    return $data;
  }

}
