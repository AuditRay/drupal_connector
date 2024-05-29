<?php

namespace Drupal\monit\Plugin\HealthCheck;

use Drupal\monit\Plugin\HealthCheckPluginBase;

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
        $checklistsResult = [];
        $options = ['skip' => 'none', 'format' => 'json', 'detail' => FALSE, 'bootstrap' => FALSE];
        $checklistDefinitions = $this->auditChecklistManager->getDefinitions();
        foreach ($checklistDefinitions as $id => $checklist) {
          $checklists[$id] = $this->auditChecklistManager->createInstance($checklist['id'], $options);
        }
        foreach ($checklists as $id => $checklist) {
            $checklistsResult[$id] = [
                'id' => $this->pluginId . '_' . $id,
                'percent' => $checklist->getPercent(),
                'label' => $checklist->getLabel()->render(),
                'checks' => [],
            ];
            foreach ($checklist->getCheckObjects() as $check) {
                // The results that we get from AuditChecks objects are not
                // consistent, so we had to check for the type of the result.
                $result = $check->getResult();
                if (is_array($result) && isset($result['#theme'])) {
                    $result = \Drupal::service('renderer')->render($result);
                }
                elseif (!is_string($result)) {
                    $result = $check->getResult()->render();
                }
                $checklistsResult[$id]['checks'][$check->getId()] = [
                    'id' => $this->pluginId . '_' . $check->getId(),
                    'label' => $check->getLabel()->render(),
                    'description' => $check->getDescription()->render(),
                    'result' => $result,
                    'action' => $check->renderAction(),
                    'score' => $check->getScore(),
                ];
            }
        }

        return $checklistsResult;
    }

}
