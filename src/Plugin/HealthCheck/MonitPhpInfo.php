<?php

namespace Drupal\monit_drupal_connector\Plugin\HealthCheck;

use Drupal\monit_drupal_connector\Plugin\HealthCheckPluginBase;

/**
 * @HealthCheck(
 *   id = "monit_phpinfo",
 *   label = @Translation("Monit Php Info"),
 *   description = @Translation("Gathering Php info data.")
 * )
 */
class MonitPhpInfo extends HealthCheckPluginBase
{

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
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
    return 'Php Info';
  }

  /**
   * Set the Health Check descriptiion.
   *
   * @return string
   */
  public function description()
  {
    return 'This health check will return all php info.';
  }

  /**
   * Set the payload data.
   *
   * @return array $payload
   */
  public function data()
  {
    $phpInfo = $this->phpinfo_array();
    $data = [];
    foreach ($phpInfo as $section => $values) {

      foreach ($values as $key => $value) {
        //if $section === 'PHP Variables' & key doesn't contain '$_SERVER[' skip;
        if ($section === 'PHP Variables' && strpos($key, '$_SERVER[') === false) {
          continue;
        }
        $data[] = [
          'id' => $section . ' - ' . $key,
          'label' => $key,
          'description' => $key,
          'status' => '',
          'statusDescription' =>  '',
          'namespace' => "Php Info",
          'detailsTitle' => $section . ' - ' . $key,
          'detailsText' => [],
          'detailsFindings' => [
            [
              'type' => 'paragraph',
              'value' => $value,
            ]
          ],
          'detailsExtra' => []
        ];
      }
    }

    return $data;
  }

  private function phpinfo_array(){
    ob_start();
    phpinfo(-1);

    $pi = preg_replace(
      array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
        '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
        "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
        '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
        .'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
        '#<h1>PHP Credits</h1>#',
        '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
        "# +#", '#<tr>#', '#</tr>#'),
      array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
        '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
        "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
        '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
        '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
        '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
      ob_get_clean());

    $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
    unset($sections[0]);

    $pi = array();
    foreach($sections as $section){
      $n = substr($section, 0, strpos($section, '</h2>'));
      preg_match_all(
        '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
        $section, $askapache, PREG_SET_ORDER);
      foreach($askapache as $m)
        $pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
    }

    return $pi;
  }

}

