<?php // kate: space-indent on; indent-width 2; mixedindent off; indent-mode normal;
/**
 * @file
 *   Various page callback related functions
 */

/**
 * 
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
}

function template_preprocess_commerce_worldpay_bg_success(&$variables) {
  $order = $variables['order'];
  $variables['title'] = t('Payment has been completed');
  $variables['site_name'] = variable_get('site_name', 'Drupal');
  
  $variables['return_url'] = url('checkout/' . $order->order_id . '/payment/return/' . $order->data['payment_redirect_key'], array('absolute' => TRUE));
}

function template_preprocess_commerce_worldpay_bg_cancel(&$variables) {
  $order = $variables['order'];
  $variables['title'] = t('The transaction has been canceled');
  $variables['site_name'] = variable_get('site_name', 'Drupal');
  
  // Return to the previous page when payment is canceled
  $variables['return_url'] = url('checkout/' . $order->order_id . '/payment/back/' . $order->data['payment_redirect_key'], array('absolute' => TRUE));
}