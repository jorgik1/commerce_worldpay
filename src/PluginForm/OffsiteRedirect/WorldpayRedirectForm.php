<?php

namespace Drupal\commerce_worldpay\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway\WorldpayRedirectInterface;
use Drupal\Core\Form\FormStateInterface;

class WorldpayRedirectForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var WorldpayRedirectInterface $paymentGatewayPlugin */
    $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();

    $data = $paymentGatewayPlugin->buildFormData($payment);

    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = array('#type' => 'hidden', '#value' => $value);
      }
    }

    return $this->buildRedirectForm($form, $form_state, $paymentGatewayPlugin->getUrl(), $data, BasePaymentOffsiteForm::REDIRECT_POST);
  }

}