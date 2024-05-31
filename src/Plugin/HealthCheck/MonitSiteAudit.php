<?php

namespace Drupal\monit\Plugin\HealthCheck;

use Drupal\monit\Plugin\HealthCheckPluginBase;
use Drupal\site_audit\Renderer\Html;
use Drupal\site_audit\Plugin\SiteAuditCheckBase;

/**
 * @HealthCheck(
 *   id = "monit_site_audit",
 *   label = @Translation("Monit Site Audit"),
 *   description = @Translation("Gathering Site Audit data.")
 * )
 */
class MonitSiteAudit extends HealthCheckPluginBase {

    /**
     * The current plugin ID.
     *
     * @var string $pluginId
     */
    protected $pluginId;

    /**
     * The Site Audit Check manager.
     *
     * @var \Drupal\site_audit\Plugin\SiteAuditCheckManager
     */
    protected $auditCheckManager;

    /**
     * The Site Audit Report manager.
     *
     * @var \Drupal\site_audit\Plugin\SiteAuditChecklistManager
     */
    protected $auditChecklistManager;

    /**
     * Constructs a MonitSiteAudit object.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition) {
        $this->pluginId = $plugin_id;
        $this->auditCheckManager = \Drupal::service('plugin.manager.site_audit_check');
        $this->auditChecklistManager = \Drupal::service('plugin.manager.site_audit_checklist');
    }

    /**
     * Set the Health Check label.
     *
     * @return string
     */
    public function label()
    {
        return 'Site Audit Health Check Data';
    }

    /**
     * Set the Health Check descriptiion.
     *
     * @return string
     */
    public function description()
    {
        return 'This health check will return all the findings of the Site Audit module.';
    }

    /**
     * Set the payload data.
     *
     * @return array $payload
     */
    public function data()
    {
        $$data = [];
        $options = ['skip' => 'none', 'format' => 'json', 'detail' => FALSE, 'bootstrap' => FALSE];
        $checklistDefinitions = $this->auditChecklistManager->getDefinitions();
        foreach ($checklistDefinitions as $id => $checklist) {
          $checklists[$id] = $this->auditChecklistManager->createInstance($checklist['id'], $options);
        }
        foreach ($checklists as $id => $checklist) {
            $data[$id] = [
                'id' => $this->pluginId . '_' . $id,
                'label' => $checklist->getLabel()->render(),
                'description' => $checklist->getDescription(),
                'status' => $checklist->getPercent(),
                'statusDecription' => 'Success percentage of the checklist',
                'details' => [],
            ];
            foreach ($checklist->getCheckObjects() as $check) {
                // The details that we get from AuditChecks objects are not
                // consistent, so we had to check for the type of the result.
                $details = $check->getResult();
                if (is_array($details) && isset($details['#theme'])) {
                    $details = \Drupal::service('renderer')->render($details);
                }
                elseif (!is_string($details)) {
                    $details = $check->getResult()->render();
                }
                $data[$id]['details'][$check->getId()] = [
                    'id' => $this->pluginId . '_' . $check->getId(),
                    'label' => $check->getLabel()->render(),
                    'description' => $check->getDescription()->render(),
                    'details' => $details,
                    'action' => $check->renderAction(),
                    'status' => $this->getScoreLabel($check->getScore()),
                ];
            }
        }

        return $data;
    }

    /**
     * Get the CSS class associated with a score.
     *
     * @return string
     *   Name of the Twitter bootstrap class.
     */
    public function getScoreLabel($score = NULL) {
        switch ($score) {
        case SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS:
            return 'success';

        case SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN:
            return 'warning';

        case SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO:
            return 'info';

        default:
            return 'danger';

        }
    }

}
