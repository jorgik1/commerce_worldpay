<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Worldpay Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "worldpay_redirect",
 *   label = @Translation("Worldpay (Redirect)"),
 *   display_label = @Translation("Worldpay"),
 *    forms = {
 *     "worldpay-redirect" = "Drupal\commerce_worldpay\PluginForm\WorldpayRedirectForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 * )
 */
class WorldpayRedirect extends OffsitePaymentGatewayBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'service_key' => '',
        'client_key' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['merchant_id'],
    ];
    $form['service_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Key'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['service_key'],
    ];
    $form['client_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Key'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['client_key'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['service_key'] = $values['service_key'];
      $this->configuration['client_key'] = $values['client_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['service_key'] = $values['service_key'];
      $this->configuration['client_key'] = $values['client_key'];
    }
  }
}