<?php
/**
 * @file
 * Contains Drupal\monit\Form\MonitConfigForm.
 */

namespace Drupal\monit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
Use \Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class MonitConfigForm.
 */
class MonitConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'monit.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'monit_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('monit.adminsettings');

    $form['token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Token'),
      '#description' => $this->t('Your Monit account token.'),
      '#default_value' => $config->get('token'),
      '#weight' => '1',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '2',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory()->getEditable('monit.adminsettings');
    $config->set('token', $form_state->getValue('token'))->save();

    \Drupal::messenger()->addMessage('Monit configurations saved.');
  }

}
