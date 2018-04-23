<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Interface WorldpayRedirectInterface.
 */
interface WorldpayRedirectInterface  extends OffsitePaymentGatewayInterface {

  /**
   * Get the WorldPay form integration url.
   */
  public function getUrl();

  /**
   * Builds the transaction data.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The commerce payment object.
   *
   * @return bool|array
   *   Transaction data.
   */
  public function buildFormData(PaymentInterface $payment);
}