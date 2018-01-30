<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides standard payment.
 *
 * @CommercePaymentGateway(
 *  id = "commerce_worldpay_standard",
 *  label = "Worldpay (Standard)",
 *  display_label = "Worldpay standard (offsite)",
 *  forms = {
 *    "worldpay-standard" = "Drupal\commerce_worldpay\PluginForm\Standard\WorldpayStandardForm",
 *  },
 *  payment_method_types = {"credit_card"},
 *  credit_card_types = {
 *     "amex", "maestro", "mastercard", "visa"
 *  },
 * )
 */
class WorldpayStandard extends OffsitePaymentGatewayBase {

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
      '#default_value' => '',
    ];
    $form['service_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Key'),
      '#required' => TRUE,
      '#default_value' => '',
    ];
    $form['client_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Key'),
      '#required' => TRUE,
      '#default_value' => '',
    ];
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
      $this->configuration['client_key'] = $values['client_key'];m
    }
  }


}