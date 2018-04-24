<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;

trait WorldpayPymentTrait {

  /**
   * Get the billing address for this order.
   *
   * @param OrderInterface $order
   *   The commerce order object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *
   * @return array
   */
  private function getBillingAddress(OrderInterface $order) {
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $billingAddress = $order->getBillingProfile()->get('address')->first();
    $country_list  = \Drupal::service('address.country_repository')->getList();

    return [
      'email' => $order->get('mail')->first()->value,
      'first_name' => $billingAddress->getGivenName(),
      'surname' => $billingAddress->getFamilyName(),
      'address1' => $billingAddress->getAddressLine1(),
      'address2' => $billingAddress->getAddressLine2(),
      'city' => $billingAddress->getLocality(),
      'postCode' => $billingAddress->getPostalCode(),
      'countryCode' => $billingAddress->getCountryCode(),
      'country' => $country_list[$billingAddress->getCountryCode()],
    ];
  }


  /**
   * Get the shipping address to pass to WorldPay.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The commerce shipment entity.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *
   * @return bool|\array
   *   Return false if no shipping profile. Otherwise return the shipping
   *   customer details.
   */
  protected function getShippingAddress(ShipmentInterface $shipment) {
    if (!$shippingProfile = $shipment->getShippingProfile()) {
      return FALSE;
    }
    $country_list  = \Drupal::service('address.country_repository')->getList();

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $shippingAddress = $shippingProfile->get('address')->first();
    return [
      'DeliveryFirstname' => $shippingAddress->getGivenName(),
      'DeliverySurname' => $shippingAddress->getFamilyName(),
      'DeliveryAddress1' => $shippingAddress->getAddressLine1(),
      'DeliveryAddress2' => $shippingAddress->getAddressLine2(),
      'DeliveryCity' => $shippingAddress->getLocality(),
      'DeliveryPostCode' => $shippingAddress->getPostalCode(),
      'DeliveryCountry' => $shippingAddress->getCountryCode(),
      'DeliveryCountryString' => $country_list[$shippingAddress->getCountryCode()],
    ];
  }
}