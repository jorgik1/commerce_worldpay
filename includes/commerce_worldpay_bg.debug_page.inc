<?php
/**
 * @file
 *   Debuging pages
 */

require_once drupal_get_path('module', 'commerce_worldpay_bg') . '/includes/commerce_worldpay_bg.page.inc';
 
/**
 * A debuging callback
 */
function commerce_worldpay_bg_debug_response_page($payment_method = NULL) {
  if (!$payment_method) {
    if (!isset($_GET['skip_pm']))
      $payment_method = commerce_payment_method_instance_load('commerce_worldpay_bg|commerce_payment_commerce_worldpay_bg');
  }
  $order_id = isset($_GET['cartId']) ? (int) $_GET['cartId'] : 1;
  $trans_id = isset($_GET['transId']) ? (int) $_GET['transId'] : 1;
  $debug_wppr = array
    (
      'region' => '',
      'authAmountString' => 'US&#36;35.45',
      '_SP_charEnc' => 'UTF-8',
      'desc' => '',
      'M_http_host' => 'magicmyth.webfactional.com',
      'tel' => '0876272',
      'MC_callback' => 'http://magicmyth.webfactional.com/commerce_worldpay/bg/response/commerce_worldpay_bg%7Ccommerce_payment_commerce_worldpay_bg',
      'address1' => '',
      'countryMatch' => 'N',
      'address2' => '',
      'cartId' => $order_id,
      'address3' => '',
      'lang' => 'en',
      'rawAuthCode' => 'A',
      'callbackPW' => 'pass',
      'transStatus' => 'Y',
      'amountString' => 'US&#36;35.45',
      'authCost' => '35.45',
      'currency' => 'USD',
      'installation' => '269343',
      'amount' => '35.45',
      'wafMerchMessage' => 'waf.warning',
      'countryString' => 'United Kingdom',
      'displayAddress' => '64 Langham Way
Ivybridge
Devon',
      'transTime' => '1328622427346',
      'name' => 'AUTHORISED',
      'testMode' => '100',
      'MC_orderId' => $order_id,
      'routeKey' => 'VISA-SSL',
      'ipAddress' => '90.217.57.19',
      'fax' => '',
      'rawAuthMessage' => 'cardbe.msg.authorised',
      'instId' => '269343',
      'AVS' => '0004',
      'compName' => 'Zixiao Chang',
      'authAmount' => '35.45',
      'postcode' => 'PL219BY',
      'cardType' => 'Visa',
      'cost' => '35.45',
      'authCurrency' => 'USD',
      'country' => 'GB',
      'charenc' => 'UTF-8',
      'email' => 'adam@studiospaceone.com',
      'address' => '64 Langham Way
Ivybridge
Devon',
      'transId' => $trans_id,
      'msgType' => 'authResult',
      'town' => '',
      'authMode' => 'A',
);
  
  $debug_wppr['skip_save'] = TRUE;
  $debug_wppr['skip_auth'] = TRUE;
  
  commerce_worldpay_bg_response_page($payment_method, $debug_wppr);
}
