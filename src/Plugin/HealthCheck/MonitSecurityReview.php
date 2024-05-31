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
        $this->securityReviewPluginManager = \Drupal::service('plugin.manager.security_review.security_check');
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
        $checks = $this->securityReviewPluginManager->getChecks();
        $this->securityReview->runChecks($checks);
        $this->securityReview->setLastRun(time());
        $definitions = $this->securityReviewPluginManager->getDefinitions();
        foreach ($definitions as $id => $definition) {
            $plugin = $this->securityReviewPluginManager->createInstance($id);
            $lastResult = $plugin->lastResult();
            $result_number = $lastResult['result'];
            $details = [];
            $helpDetails = [];
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
            $data[$id] = [
                'id' => $this->pluginId . '_' . $id,
                'title' => $plugin->getTitle(),
                'description' => $plugin->getDescription(),
                'status' => $resultStatus,
                'statusDecription' => 'a string that indicates the success label',
                'time' => $this->securityReview->getLastRun(),
                'details' => [
                    'resultStatusMessage' => $resultMessage,
                    'helpText' => $helpDetails,
                    'namespace' => $plugin->getNamespace(),
                    'details' => $details,
                    'findings' => array_merge($lastResult['findings'], $lastResult['hushed']),
                ],
            ];
        }

        return $data;
    }

}
