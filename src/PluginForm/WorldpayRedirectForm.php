<?php
/**
 * Created by PhpStorm.
 * User: alexburrows
 * Date: 18/03/2018
 * Time: 19:39
 */

namespace Drupal\commerce_worldpay\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class WorldpayRedirectForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\ExpressCheckoutInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $extra = [
      'return_url' => $form['#return_url'],
      'cancel_url' => $form['#cancel_url'],
      'capture' => $form['#capture'],
    ];
    $paypal_response = $payment_gateway_plugin->setExpressCheckout($payment, $extra);

    $order = $payment->getOrder();
    $order->setData('worldpay_redirect', [
      'flow' => 'ec',
      'token' => $paypal_response['TOKEN'],
      'payerid' => FALSE,
      'capture' => $extra['capture'],
    ]);
    $order->save();
    $data = [
      'token' => $paypal_response['TOKEN'],
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    return $this->buildRedirectForm($form, $form_state, $payment_gateway_plugin->getRedirectUrl(), $data, 'get');
  }

}