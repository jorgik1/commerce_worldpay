<?php
/**
 * @file
 * Theme and pre/process functions.
 *
 * This includes the base template pre/process functions used by the WorldPay
 * payment response templates.
 */

/**
 * Template preprocess for the overal page template.
 */
function template_preprocess_commerce_worldpay_bg_html(&$variables) {
  // Pinched from template_preprocess_html()
  // RDFa allows annotation of XHTML pages with RDF data, while GRDDL provides
  // mechanisms for extraction of this RDF content via XSLT transformation
  // using an associated GRDDL profile.
  $variables['rdf_namespaces']    = drupal_get_rdf_namespaces();
  $variables['grddl_profile']     = 'http://www.w3.org/1999/xhtml/vocab';
  $variables['language']          = $GLOBALS['language'];
  $variables['language']->dir     = $GLOBALS['language']->direction ? 'rtl' : 'ltr';

  if (drupal_get_title()) {
    $variables['head_title'] = strip_tags(drupal_get_title());
  }
  else {
    $variables['head_title'] = check_plain(variable_get('site_name', 'Drupal Commerce')) . ' WorldPay payment';
  }

  $variables['title'] = variable_get('site_name', 'Drupal Commerce') . ' WorldPay payment';
}

function template_preprocess_commerce_worldpay_bg_page(&$variables) {
  $variables['title'] = variable_get('site_name', 'Drupal Commerce') . ' WorldPay payment';
}

function template_preprocess_commerce_worldpay_bg_success(&$variables) {
  $order = $variables['order'];
  $variables['title'] = t('Payment has been completed');
  $variables['site_name'] = variable_get('site_name', 'Drupal');

  $url_options = array();
  if ($variables['settings']['payment_urls']['force_non_ssl_links']) $url_options['https'] = FALSE;

  $variables['return_url'] = url(
    'checkout/' . $order->order_id . '/payment/return/' . $order->data['payment_redirect_key'],
    array('absolute' => TRUE)+$url_options);
}

function template_preprocess_commerce_worldpay_bg_cancel(&$variables) {
  $order = $variables['order'];
  $variables['title'] = t('The transaction has been canceled');
  $variables['site_name'] = variable_get('site_name', 'Drupal');

  $url_options = array();
  if ($variables['settings']['payment_urls']['force_non_ssl_links']) $url_options['https'] = FALSE;
  // Return to the previous page when payment is canceled
  $variables['return_url'] = url(
    'checkout/' . $order->order_id . '/payment/back/' . $order->data['payment_redirect_key'],
    array('absolute' => TRUE)+$url_options);
}
