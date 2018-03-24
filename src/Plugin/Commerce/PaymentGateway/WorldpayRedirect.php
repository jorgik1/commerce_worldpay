<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;

define('WORLDPAY_BG_SERVER_TEST', 'Test');
define('WORLDPAY_BG_SERVER_LIVE', 'Live');

define('C_WORLDPAY_BG_TXN_MODE_LIVE', 'live');
define('C_WORLDPAY_BG_TXN_MODE_TEST', 'live_test');
// define('WORLDPAY_TXN_MODE_SIMULATION', 'developer');

// Default URLs for WorldPay transaction.
define(
  'C_WORLDPAY_BG_DEF_SERVER_LIVE',
  'https://secure.wp3.rbsworldpay.com/wcc/purchase'
);
define(
  'C_WORLDPAY_BG_DEF_SERVER_TEST',
  'https://secure-test.worldpay.com/wcc/purchase'
);

// This is WorldPay custom variable name, used to hold the repsone URL.
define('C_WORLDPAY_BG_RESPONSE_URL_TOKEN', 'MC_callback');

/**
 * Provides the Worldpay Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "worldpay_redirect",
 *   label = @Translation("Worldpay (Redirect)"),
 *   display_label = @Translation("Worldpay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_worldpay\PluginForm\WorldpayRedirectForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 *  modes = {
 *    WORLDPAY_BG_SERVER_TEST = "Test", WORLDPAY_BG_SERVER_LIVE = "Live",
 *   },
 * )
 */
class WorldpayRedirect extends OffsitePaymentGatewayBase implements WorldpayRedirectInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'service_key' => '',
        'client_key' => '',
        'installation_id' => '',
        'txn_mode' => C_WORLDPAY_BG_DEF_SERVER_TEST,
        'txn_type' => '',
        'debug' => 'log',
        'payment_response_logging' => 'full_wppr',
        'site_id' => '',
        'payment_parameters' => [
          'test_mode' => '',
          'test_result' => 'AUTHORISED',
        ],
        'payment_security' => [
          'use_password' => FALSE,
          'password' => '',
          'md5_salt' => '',
        ],
        'payment_urls' => [
          'live' => C_WORLDPAY_BG_DEF_SERVER_LIVE,
          'test' => C_WORLDPAY_BG_DEF_SERVER_TEST,
          'use_ssl' => FALSE,
          'force_non_ssl_links' => FALSE,
        ],
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


    $form['installation_id'] = [
      '#type' => 'textfield',
      '#title' => t('Installation ID'),
      '#size' => 16,
      '#default_value' => $this->configuration['installation_id'],
      '#required' => TRUE,
    ];

    $form['debug'] = [
      '#type' => 'select',
      '#title' => t('Debug mode'),
      '#multiple' => FALSE,
      '#options' => [
        'log' => t('Log'),
        'screen' => t('Screen'),
        'both' => t('Both'),
        'none' => t('None'),
      ],
      '#default_value' => $this->configuration['debug'],
    ];

    $form['payment_response_logging'] = [
      '#type' => 'radios',
      '#title' => t('Payment Response/Notificaton logging'),
      '#options' => [
        'notification' => t(
          'Log notifications during WorldPay Payment Notifications validation and processing.'
        ),
        'full_wppr' => t(
          'Log notifications with the full WorldPay Payment Notifications during validation and processing (used for debugging).'
        ),
      ],
      '#default_value' => $this->configuration['payment_response_logging'],
    ];

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => t('Site ID'),
      '#description' => t(
        'A custom identifier that will be passed to WorldPay. This is useful for using one WorldPay account for multiple web sites.'
      ),
      '#size' => 10,
      '#default_value' => $this->configuration['site_id'],
      '#required' => FALSE,
    ];


    $form['payment_parameters'] = [
      '#type' => 'fieldset',
      '#title' => t('Payment parameters'),
      '#description' => t(
        'These options control what parameters are sent to RBS WorldPay when the customer submits the order.'
      ),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['payment_parameters']['pm_select_localy'] = [
      '#type' => 'checkbox',
      '#title' => t('Select payment method locally'),
      '#default_value' => $this->configuration['pm_select_localy'],
      '#description' => t(
        'When checked the payment methods will be chosen on this website instead of on WorldPay\'s server.'
      ),
    ];

    $form['payment_parameters']['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable test mode'),
      '#default_value' => $this->configuration['test_mode'],
    ];

    $form['payment_parameters']['test_result'] = [
      '#type' => 'select',
      '#title' => t('Test mode result'),
      '#description' => t(
        'Specify the required transaction result when working in test mode.'
      ),
      '#default_value' => $this->configuration['test_result'],
      '#options' => [
        'AUTHORISED' => 'Authorised',
        'REFUSED' => 'Refused',
        'ERROR' => 'Error',
        'CAPTURED' => 'Captured',
      ],
      '#disabled' => (!$this->configuration['test_mode']) ? TRUE : FALSE,
    ];


    $form['payment_security'] = [
      '#type' => 'fieldset',
      '#title' => t('Security'),
      '#description' => t(
        'These options are for insuring a secure transaction to RBS WorldPay when the customer submits the order.'
      ),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['payment_security']['use_password'] = [
      '#type' => 'checkbox',
      '#title' => t('Use WorldPay installation password?'),
      '#description' => t(
        'It is recomended that you set a password in your Worldpay Merchant Interface > Installation. Once done check this and enter the password.'
      ),
      '#default_value' => $this->configuration['use_password'],
    ];

    $form['payment_security']['password'] = [
      '#type' => 'textfield',
      '#title' => t('Installation password'),
      '#description' => t(
        'This will only be used if you have checked "Use WorldPay installation password?".'
      ),
      '#size' => 16,
      '#maxlength' => 16,
      '#default_value' => $this->configuration['password'],
    ];

    $form['payment_security']['md5_salt'] = [
      '#type' => 'textfield',
      '#title' => t('Secret key'),
      '#description' => t('This is the key used to hash some of the content for verification between Worldpay and this site".'),
      '#size' => 16,
      '#maxlength' => 16,
      '#default_value' => $this->configuration['md5_salt'],
      '#required' => TRUE,
    ];

    $form['payment_urls'] = [
      '#type' => 'fieldset',
      '#title' => t('Payment URLs'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['payment_urls']['test'] = [
      '#type' => 'textfield',
      '#title' => t('Test URL'),
      '#description' => t('The WorldPay test environment URL.'),
      '#default_value' => $this->configuration['test'],
      '#required' => TRUE,
    ];

    $form['payment_urls']['live'] = [
      '#type' => 'textfield',
      '#title' => t('Live URL'),
      '#description' => t('The WorldPay live environment URL.'),
      '#default_value' => $this->configuration['live'],
      '#required' => TRUE,
    ];

    $form['payment_urls']['use_ssl'] = [
      '#type' => 'checkbox',
      '#title' => t('Use SSL for payment notifications'),
      '#description' => t(
        'If checked, when WorldPay passes information, it will be done over SSL for greater security. Use in combination with callback password to prevent spoofing.'
      ),
      '#default_value' => $this->configuration['use_ssl'],
    ];

    $form['payment_urls']['force_non_ssl_links'] = [
      '#type' => 'checkbox',
      '#title' => t('Force http (non-ssl) return links'),
      '#description' => t(
        'This is needed if "Use SSL" is checked and you want your buyers to return to the non-ssl site.'
      ),
      '#default_value' => $this->configuration['force_non_ssl_links'],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if(!UrlHelper::isValid($form_state->getValue('live'))) {
      $form_state->setErrorByName('live', $this->t(
        'The URL @url for @title is invalid. Enter a fully-qualified URL, such as https://secure.worldpay.com/example.',
        ['@url' => $form['#value'], '@title' => $form['#title']]
        )
      );
    }
    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['service_key'] = $values['service_key'];
      $this->configuration['client_key'] = $values['client_key'];

      $this->configuration['installation_id'] = $values['installation_id'];
      $this->configuration['debug'] = $values['debug'];
      $this->configuration['site_id'] = $values['site_id'];
      $this->configuration['test_mode'] = $values['test_mode'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['md5_salt'] = $values['md5_salt'];
      $this->configuration['live'] = $values['live'];
      $this->configuration['test'] = $values['test'];
      $this->configuration['use_ssl'] = $values['use_ssl'];
      $this->configuration['force_non_ssl_links'] = $values['force_non_ssl_links'];

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
   * Builds the transaction data.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The commerce payment object.
   *
   * @return array
   *   Transaction data.
   */
  public function buildTransaction(PaymentInterface $payment) {
    return [];
  }
}