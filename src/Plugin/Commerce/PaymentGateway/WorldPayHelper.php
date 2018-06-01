<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\Url;

/**
 * Class WorldPayHelper
 * Helper class for collecting form data.
 *
 * @package Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway
 */
class WorldPayHelper {

  /**
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  private $order;

  /**
   * @var array
   */
  private $config;

  /**
   * @var array
   */
  private $data = [];

  /**
   * WorldPayHelper constructor.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param $configuration
   */
  public function __construct(OrderInterface $order, $configuration) {
    $this->order = $order;
    $this->config = $configuration;
  }

  /**
   * @param array $addressData
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function addShipmentAddress(array $addressData) {
    if (NULL === $addressData) {
      throw new MissingDataException('There is no address data provided!');
    }

    $this->data += [
      'name' => $addressData['DeliveryFirstname'] . ' ' . $addressData['DeliverySurname'],
      'address' => $addressData['DeliveryAddress1'],
      'postcode' => $addressData['DeliveryPostCode'],
      'country' => $addressData['DeliveryCountry'],
      'countryString' => $addressData['DeliveryCountryString'],
    ];
  }

  /**
   * @param array $addressData
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function addAddress(array $addressData) {
    if (NULL === $addressData) {
      throw new MissingDataException('There is no address data provided!');
    }

    $this->data += [
      'name' => $addressData['first_name'] . ' ' . $addressData['surname'],
      'address' => $addressData['address1'],
      'postcode' => $addressData['postCode'],
      'country' => $addressData['countryCode'],
      'countryString' => $addressData['country'],
      'email' => $addressData['email'],
    ];
  }

  /**
   * @return array
   */
  public function createData() {
    if ($this->config['payment_parameters']['test_mode']) {
      $this->data += [
        'testMode' => '100',
      ];
    }

    $this->data += [
      'instId' => $this->config['installation_id'],
      'amount' => $this->order->getTotalPrice()->getNumber(),
      'cartId' => $this->order->uuid(), //This is need to clarify.
      'currency' => $this->order->getTotalPrice()->getCurrencyCode(),
      'MC_orderId' => $this->order->id(),
      'M_http_host' => \Drupal::request()->getSchemeAndHttpHost(),
      'signatureFields' => implode(':', WorldpayRedirect::md5signatureFields()),
      'signature' => $this->buildMd5Hash([
        $this->config['installation_id'],
        $this->order->getTotalPrice()->getNumber(),
        $this->order->getTotalPrice()->getCurrencyCode(),
        $this->order->uuid(),
        $this->order->id(),
        $this->order->getData('worldpay_form')['return_url'],
      ]),
      // The path WorldPay should send its Payment Response to
      'MC_callback' => $this->order->getData('worldpay_form')['return_url'],
      // Used in WorldPay custom pages
      'C_siteTitle' => \Drupal::config('system.site')->get('name'),
    ];

    return $this->data;
  }

  /**
   * Helper function for hashing of signature fields.
   *
   * @param array $signature_fields_values
   *
   * @return string
   */
  public function buildMd5Hash(array $signature_fields_values) {
    return md5($this->config['payment_security']['md5_salt'] . ':' . implode(':', $signature_fields_values));
  }

}