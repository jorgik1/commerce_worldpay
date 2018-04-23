<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Worldpay direct payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "worldpay_direct",
 *   label = @Translation("Worldpay (Direct)"),
 *   display_label = @Translation("Worldpay"),
 *    forms = {
 *     "onsite-payment" = "Drupal\commerce_worldpay\PluginForm\Onsite\WorldpayDirectForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 * )
 */
class WorldpayDirect extends OnsitePaymentGatewayBase implements WorldpayDirectInterface {
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

  /**
   * Creates a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param bool $capture
   *   Whether the created payment should be captured (VS authorized only).
   *   Allowed to be FALSE only if the plugin supports authorizations.
   *
   * @throws \InvalidArgumentException
   *   If $capture is FALSE but the plugin does not support authorizations.
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    // TODO: Implement createPayment() method.
  }

  /**
   * Creates a payment method with the given payment details.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $payment_details
   *   The gateway-specific payment details.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // TODO: Implement createPaymentMethod() method.
  }

  /**
   * Deletes the given payment method.
   *
   * Both the entity and the remote record are deleted.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // TODO: Implement deletePaymentMethod() method.
  }
}