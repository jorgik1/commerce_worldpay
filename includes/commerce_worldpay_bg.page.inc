<?php // kate: space-indent on; indent-width 2; mixedindent off; indent-mode normal;
/**
 * @file
 *   Various page callback related functions
 */

/**
 * Page callback that listens for transaction information from WorldPay
 *
 * DRAFT
 * Here we create new transactions and load existing ones.
 *
 * - First validate the signature fields with md5
 * - Decide action
 * -- Create response pages for Worldpay to use
 * --- Success page or cancel page (resultY.html || resultC.html)
 */
function commerce_worldpay_bg_response_page($payment_method = NULL, $debug_wppr = array()) {
  $output = '';
  
  // If the payment method specifies full IPN logging, do it now.
  if ($payment_method && !empty($payment_method['settings']['payment_response_logging']) &&
    $payment_method['settings']['payment_response_logging'] == 'full_wppr'
 ) {
    $full_log = TRUE;
  }
  else {
    $full_log = FALSE;
  }
  
  if (empty($debug_wppr)) {
    $debug_mode = FALSE;
    // WorldPay Payment Response
    $wppr = $_POST;
    
    if (empty($wppr['transId']) && $wppr['transStatus'] != 'C') {
      if ($full_log)
        watchdog(
          'commerce_worldpay_bg', 'Request with no transId was sent from <em>@ip</em> with request method <b>@method</b>. Refered by: <b>@referer</b>.', 
          array(
            '@ip' => !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
            '@method' => !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            '@referer' => !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'UNKNOWN'
          ),
          WATCHDOG_NOTICE);
      return;
    }
    
    if (empty($wppr['MC_orderId'])) {
      drupal_add_http_header('Status', '404 Not Found');
      if ($full_log)
        watchdog(
          'commerce_worldpay_bg', 'Request with no MC_orderId was sent from <em>@ip</em> with request method <b>@method</b>. Refered by: <b>@referer</b>.', 
          array(
            '@ip' => !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
            '@method' => !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            '@referer' => !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'UNKNOWN'
          ),
          WATCHDOG_NOTICE);
      return;
    }
    
  }
  else {
    $wppr = $debug_wppr;
    $debug_mode = TRUE;
  }
  
  if ($full_log) {
    watchdog(
      'commerce_worldpay_bg', 'Attempting to process Payment Response @wp_txn_id. !wpr_log', 
      array(
        '@wp_txn_id' => $wppr['transId'],
        '!wpr_log' => '<pre>' . check_plain(print_r($wppr, TRUE)) . '</pre>'
      ),
      WATCHDOG_NOTICE);
  }
  
  // Exit if the Payment Response has already been processed.
//   if (!empty($wppr['transId']) && $prior_wppr = commerce_worldpay_bg_txn_load($wppr['transId'],'wp_txn_id')) {
//     if ($prior_wppr['auth_amount'] == $wppr['authAmount'] && $prior_wppr['auth_amount'] == $wppr['transaction_status']) {
//       watchdog('commerce_paypal', 'Attempted to process an IPN that has already been processed with transaction ID @txn_id.', array('@txn_id' => $wppr['txn_id']), WATCHDOG_NOTICE);
//       return FALSE;
//     }
//   }
  
  if (!$order = commerce_order_load($wppr['MC_orderId'])) {
    drupal_add_http_header('Status', '404 Not Found');
    return;
  }
  
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);
  
  // Check that the the transId and cartId are part of the same transaction
  // TODO
  if ($debug_mode && $debug_wppr['skip_auth']) {
    $order->data['payment_redirect_key'] = '123';
  }
  else {
    $authenticated = _commerce_worldpay_bg_payment_response_authenticate(
      $order_wrapper,
      $wppr,
      $payment_method,
      $prior_wppr);
    
    if (!$authenticated) {
      return;
    }
  }
  
  $theme_data = array(
    'order' => $order,
    'order_id' => $wppr['MC_orderId'],
    'order_no' => $wppr['cartId'],
    'wp_txn_id' => $wppr['transId'],
    'settings' => $payment_method['settings'],
  );
  $theme_data['installation_id'] = $payment_method ? $payment_method['settings']['installation_id'] : $wppr['instId'];
  // Remove parts of the settings a theme need not know about
  unset($theme_data['payment_security']);
  $theme_data['settings']['theme_debug'] = $debug_mode;
  
  switch ($wppr['transStatus']) {
    case 'Y':
      if ($debug_mode && $debug_wppr['skip_save']) {
        $order->data['payment_redirect_key'] = '123';
        // Write a message
      }
      else {
        // Create or re-establish a Commerce Transaction
        // This will add the 'transaction_id' to $wppr
        commerce_paypal_bg_transaction_process($order, $payment_method, $wppr, $prior_wppr);
        $tx_data = commerce_worldpay_bg_convert_wppr_to_record($wppr);
        $tx_data['transaction_id'] = $wppr['transaction_id'];
        $tx_data['order_id'] = $order->order_id;
        $tx_data['signature_fields'] = join(':', _commerce_worldpay_bg_md5_signature_fields());
        if ($prior_wppr) {
          $tx_data = array_replace($prior_wppr, $tx_data);
        }
        commerce_worldpay_bg_txn_save($tx_data);
      }
      
      if (!empty($payment_method) and !empty($payment_method['settings']['debug'])) {
        watchdog(
          'commerce_worldpay_bg', 'Creating success HTML for transaction @wp_txn_id.', 
          array(
            '@wp_txn_id' => $wppr['transId']
          ),
          WATCHDOG_NOTICE);
      }
      
      // Now generate the HTML that WorldPay will pull from to generate its
      // custom page.
      // This is printed directly as we want to skip the rest of Drupal's HTML
      // build. See: drupal_deliver_html_page()
      drupal_set_title(t('@site_name payment',
        array('@site_name' => variable_get('site_name', 'Drupal Commerce') . ' WorldPay payment')));
      print _commerce_worldpay_bg_build_page($theme_data, 'success');
      
      break;
    
    case 'C':
      
      if ($prior_wppr) {
        // Don't know if WorldPay ever changes transStatus after its been set
        // the first time but of it does we update our transaction info
        $tx_data = commerce_worldpay_bg_convert_wppr_to_record($wppr);
        $tx_data = array_replace($prior_wppr, $tx_data);
        commerce_worldpay_bg_txn_save($tx_data);
      }
      
      // Not totaly sure if this is the right thing to do here?
      commerce_payment_redirect_pane_previous_page($order);
      
      if (!empty($payment_method) and !empty($payment_method['settings']['debug'])) {
        watchdog(
          'commerce_worldpay_bg', 'Creating cancel HTML for transaction @wp_txn_id.', 
          array(
            '@wp_txn_id' => $wppr['transId']
          ),
          WATCHDOG_NOTICE);
      }
      
      drupal_set_title(t('@site_name payment', array(
        '@site_name' => variable_get('site_name', 'Drupal Commerce') . ' WorldPay payment canceled')));
      print _commerce_worldpay_bg_build_page($theme_data, 'cancel');
      break;
  }
  
}

/**
 * Generates the theme output
 */
function _commerce_worldpay_bg_build_page($theme_data, $type = 'success') {
  drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
  return theme(
          'commerce_worldpay_bg_html',
          array(
            'page' => theme('commerce_worldpay_bg_page', array(
                'content' => theme('commerce_worldpay_bg_' . $type, $theme_data)
             )+$theme_data),
          )+$theme_data
         );
}

function _commerce_worldpay_bg_payment_response_authenticate(
  $order_wrapper, &$wppr, &$payment_method = NULL, &$wp_transaction = NULL
) {
  $failed_authenticaion = FALSE;
  $message = '';
  $settings = $payment_method['settings'];
  
  //If the merchant set a password compare them callbackPW
  if ($settings['payment_security']['use_password'] && !empty($settings['payment_security']['password'])) {
    if ($settings['payment_security']['password'] != $wppr['callbackPW']) {
      $failed_authenticaion = TRUE;
      $message = 'WorldPay passwords do not match. Make sure you have the same password set in the Commerce WorldPay settings page as set in your WorldPay installtion.';
    }
  }
  
  /*
  // Check the md5
  $response_url = commerce_worldpay_bg_response_url($settings['payment_method']);
  $salt = $settings['payment_security']['md5_salt'];
  
  if ($wp_transaction) {
    $local_md5 = $wp_transaction['md5'];
  }
  else {
    $amount = round(commerce_currency_amount_to_decimal(
      $order_wrapper->commerce_order_total->amount->value(),
      $order_wrapper->commerce_order_total->currency_code->value()
    ));
    $local_md5 = commerce_worldpay_bg_build_md5(
      _commerce_worldpay_build_sig_array(
        $order_wrapper,
        $settings['installation_id'],
        $response_url),
      $salt);
  }
  
  // Current fields are: instId, amount, currency, cartId, MC_orderId, WORLDPAY_RESPONSE_URL_TOKEN
  $wp_sig_fields = array(
    $wppr['instId'],
    $wppr['amount'],
    $wppr['currency'],
    $wppr['cartId'],
    $wppr['MC_orderId'],
    $wppr[WORLDPAY_RESPONSE_URL_TOKEN],
  );
  
  if ($local_md5 != commerce_worldpay_bg_build_md5($wp_sig_fields, $salt)) {
    $failed_authenticaion = TRUE;
    $message = 'The md5 strings did not match.';
  }
  */
  
  // TODO - Is it worth checking the User agent is the one WorldPay uses which is:
  // User-Agent: WJHRO/1.0 (WorldPay Java HTTP Request Object)
  
  if ($failed_authenticaion) {
    drupal_add_http_header('Status', '403 Forbidden');
    watchdog('commerce_worldpay_bg', 'Access denied! ' . $message . ' Clients details: <em>@ip</em> with request method <b>@method</b>. Refered by: <b>@referer</b>.', 
      array(
        '@ip' => !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
        '@method' => !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
        '@referer' => !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'UNKNOWN'
      ), WATCHDOG_WARNING);
    return FALSE;
  }
  
  return TRUE;
}

/**
 * Process the WPPR once it's been validated.
 */
function commerce_paypal_bg_transaction_process($order, $payment_method, &$wppr, &$prior_wppr = NULL) {
  // Exit when we don't get a payment status we recognize.
  if (!in_array($wppr['transStatus'], array('Y', 'C'))) {
    commerce_payment_redirect_pane_previous_page($order);
    return FALSE;
  }

  // If this is a prior authorization capture WorldPay Payment Response for which we've already
  // created a transaction...
  if ($prior_wppr) {
    // Load the prior IPN's transaction and update that with the capture values.
    $transaction = commerce_payment_transaction_load($prior_wppr['transaction_id']);
  }
  else {
    // Create a new payment transaction for the order.
    $transaction = commerce_payment_transaction_new('commerce_worldpay_bg', $order->order_id);
    $transaction->instance_id = $payment_method['instance_id'];
  }

  $transaction->remote_id = $wppr['transId'];
  $transaction->amount = commerce_currency_decimal_to_amount($wppr['authAmount'], $wppr['authCurrency']);
  $transaction->currency_code = $wppr['authCurrency'];
  $transaction->payload[REQUEST_TIME] = $wppr;

  // Set the transaction's statuses based on the IPN's payment_status.
  $transaction->remote_status = $wppr['transStatus'];

  // TODO - Figure out how best to  set status based on SecureCode authentication.
  if (isset($wppr['authentication'])) {
    switch ($wppr['authentication']) {
      case 'ARespH.card.authentication.0':
        $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
        $transaction->message = t("Cardholder authenticated by SecureCode.");
        break;

      case 'ARespH.card.authentication.1':
        $transaction->message = t('Cardholder/Issuing Bank not enrolled for authentication.');
        break;

      case 'ARespH.card.authentication.6':
        $transaction->message = t('Cardholder authentication not available');
        break;

      case 'ARespH.card.authentication.7':
        $transaction->message = t('Cardholder did not complete authentication.');
        break;
    }
  }
  
  // They don't give us very detailed transaction information do they?
  switch ($wppr['transStatus']) {
    case 'Y':
      $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
      $transaction->message = t('WorldPay excepted the user\'s transaction.');
      break;
   
   // I don't think we should ever see this status at this point but incase
   // we do...
   case 'C':
      // TODO - Is this a suitable status?
      $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
      $transaction->message = t('The user canceled the payment.');
      break;
  }

  // Save the transaction information.
  commerce_payment_transaction_save($transaction);
  $wppr['transaction_id'] = $transaction->transaction_id;

  commerce_payment_redirect_pane_next_page($order);
  watchdog('commerce_worldpay_bg', 'Payment Response processed for Order @order_number with ID @txn_id.', array('@txn_id' => $wppr['transId'], '@order_number' => $order->order_number), WATCHDOG_INFO);
}