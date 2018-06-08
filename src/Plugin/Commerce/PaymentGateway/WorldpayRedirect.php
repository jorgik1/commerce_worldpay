<?php

namespace Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Html;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the Worldpay Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "worldpay",
 *   label = @Translation("Worldpay (Redirect)"),
 *   display_label = @Translation("Worldpay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_worldpay\PluginForm\OffsiteRedirect\WorldpayRedirectForm",
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

  use WorldpayPymentTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The logger factory.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;


  /**
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  private $linkGenerator;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              PaymentTypeManager $payment_type_manager,
                              PaymentMethodTypeManager $payment_method_type_manager,
                              RequestStack $requestStack,
                              LoggerInterface $logger,
                              TimeInterface $time,
                              ModuleHandlerInterface $moduleHandler,
                              LinkGeneratorInterface $linkGenerator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->requestStack = $requestStack;
    $this->logger = $logger;
    $this->time = $time;
    $this->moduleHandler = $moduleHandler;
    $this->linkGenerator = $linkGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'installation_id' => '',
        'txn_mode' => C_WORLDPAY_BG_DEF_SERVER_TEST,
        'txn_type' => '',
        'debug' => 'log',
        'confirmed_setup' => FALSE,
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
   * Utility function holding Worldpay MAC sig codes.
   *
   * Defines what post fields should be used in the Worldpay MD5 signature.
   *
   * @todo Decide if this is worth making configurable.
   * @see http://www.worldpay.com/support/kb/bg/htmlredirect/rhtml5802.html
   *
   * @return array
   *   An array consisting of the name of fields that will be use.
   */
   public static function md5signatureFields() {
    return [
      'instId',
      'amount',
      'currency',
      'cartId',
      'MC_orderId',
      C_WORLDPAY_BG_RESPONSE_URL_TOKEN,
    ];
  }

  /**
   * {@inheritdoc}
   * @throws \InvalidArgumentException
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment_url = \Drupal::request()->getSchemeAndHttpHost() . '/payment/notify/MACHINE_NAME_OF_PAYMENT_GATEWAY';
    $help_url = URL::fromUri('http://www.worldpay.com/support/kb/bg/paymentresponse/pr5502.html');

    $form['help_text'] = [
      '#type'  => 'fieldset',
      '#title' => t('Installation instructions'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['help_text']['worldpay_settings'] = [
      '#markup' => $this->t(
        '<h4>Installation instructions</h4>
      <p>For this module to work properly you must configure a few specific options in your RBS WorldPay account under <em>Installation Administration</em> settings:</p>
      <ul>
        <li><strong>Payment Response URL</strong> must be set to: <em>@response_url</em></li>
        <li><strong>Payment Response enabled?</strong> must be <em>enabled</em></li>
        <li><strong>Enable the Shopper Response</strong> should be <em>enabled</em> to get the Commerce response page.</li>
        <li><strong>Shopper Redirect URL</strong> and set the value to be <em>@shopper_link</em>. @link.</li>
        <li><strong>SignatureFields must be set to</strong>: <em>@sig</em></li>
      </ul>',
        [
          '@response_url' => '<wpdisplay item=' . C_WORLDPAY_BG_RESPONSE_URL_TOKEN
            . '-ppe empty="' . $payment_url . '">',
          '@sig' => implode(':', static::md5signatureFields()),
          '@link' => $this->linkGenerator->generate($this->t('Worldpay help document'), $help_url),
          '@shopper_link' => '<wpdisplay item=MC_callback>'
        ]
      ),
    ];

    $form['help_text']['confirmed_setup'] = [
      '#type'          => 'checkbox',
      '#title'         => t(
        'I have completed the WorldPay installation setup (above).'
      ),
      '#default_value' => $this->configuration['confirmed_setup'],
      '#required'      => TRUE,
      '#tree'          => TRUE,
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

    $form['payment_parameters']['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable test mode'),
      '#default_value' => $this->configuration['payment_parameters']['test_mode'],
    ];

    $form['payment_parameters']['test_result'] = [
      '#type' => 'select',
      '#title' => t('Test mode result'),
      '#description' => t(
        'Specify the required transaction result when working in test mode.'
      ),
      '#default_value' => $this->configuration['payment_parameters']['test_result'],
      '#options' => [
        'AUTHORISED' => 'Authorised',
        'REFUSED' => 'Refused',
        'ERROR' => 'Error',
        'CAPTURED' => 'Captured',
      ],
      '#disabled' => (!$this->configuration['payment_parameters']['test_mode']) ? TRUE : FALSE,
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
      '#default_value' => $this->configuration['payment_security']['use_password'],
    ];

    $form['payment_security']['password'] = [
      '#type' => 'textfield',
      '#title' => t('Installation password'),
      '#description' => t(
        'This will only be used if you have checked "Use WorldPay installation password?".'
      ),
      '#size' => 20,
      '#maxlength' => 30,
      '#default_value' => $this->configuration['payment_security']['password'],
    ];

    $form['payment_security']['md5_salt'] = [
      '#type' => 'textfield',
      '#title' => t('Secret key'),
      '#description' => t('This is the key used to hash some of the content for verification between Worldpay and this site".'),
      '#size' => 20,
      '#maxlength' => 30,
      '#default_value' => $this->configuration['payment_security']['md5_salt'],
      '#required' => TRUE,
    ];

    $form['payment_urls'] = [
      '#type' => 'details',
      '#title' => t('Payment URLs'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['payment_urls']['test'] = [
      '#type' => 'textfield',
      '#title' => t('Test URL'),
      '#description' => t('The WorldPay test environment URL.'),
      '#default_value' => $this->configuration['payment_urls']['test'],
      '#required' => TRUE,
    ];

    $form['payment_urls']['live'] = [
      '#type' => 'textfield',
      '#title' => t('Live URL'),
      '#description' => t('The WorldPay live environment URL.'),
      '#default_value' => $this->configuration['payment_urls']['live'],
      '#required' => TRUE,
    ];

    $form['payment_urls']['use_ssl'] = [
      '#type' => 'checkbox',
      '#title' => t('Use SSL for payment notifications'),
      '#description' => t(
        'If checked, when WorldPay passes information, it will be done over SSL for greater security. Use in combination with callback password to prevent spoofing.'
      ),
      '#default_value' => $this->configuration['payment_urls']['use_ssl'],
    ];

    $form['payment_urls']['force_non_ssl_links'] = [
      '#type' => 'checkbox',
      '#title' => t('Force http (non-ssl) return links'),
      '#description' => t(
        'This is needed if "Use SSL" is checked and you want your buyers to return to the non-ssl site.'
      ),
      '#default_value' => $this->configuration['payment_urls']['force_non_ssl_links'],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if(!UrlHelper::isValid($form_state->getValue($form['#parents'])['payment_urls']['live'])) {
      $form_state->setErrorByName('live', $this->t(
        'The URL @url for @title is invalid. Enter a fully-qualified URL, such as https://secure.worldpay.com/example.',
        ['@url' => $form['#value'], '@title' => $form['#title']]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['installation_id'] = $values['installation_id'];
      $this->configuration['debug'] = $values['debug'];
      $this->configuration['confirmed_setup'] = $values['help_text']['confirmed_setup'];
      $this->configuration['site_id'] = $values['site_id'];
      $this->configuration['payment_parameters']['test_mode'] = $values['payment_parameters']['test_mode'];
      $this->configuration['payment_parameters']['test_result'] = $values['payment_parameters']['test_result'];
      $this->configuration['payment_security']['password'] = $values['payment_security']['password'];
      $this->configuration['payment_security']['md5_salt'] = $values['payment_security']['md5_salt'];
      $this->configuration['payment_urls']['live'] = $values['payment_urls']['live'];
      $this->configuration['payment_urls']['test'] = $values['payment_urls']['test'];
      $this->configuration['payment_urls']['use_ssl'] = $values['payment_urls']['use_ssl'];
      $this->configuration['payment_urls']['force_non_ssl_links'] = $values['payment_urls']['force_non_ssl_links'];
    }
  }


  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildFormData(PaymentInterface $payment) {
    /** @var OrderInterface $order */
    $order = $payment->getOrder();
    $worldPayFormApi = $this->getWorldPayApi($order);

    try {
      $worldPayFormApi->addAddress($this->getBillingAddress($order));
    }
    catch (MissingDataException $exception) {
      $this->logger->error(
        $exception->getMessage()
      );
      return FALSE;
    }
    if ($this->moduleHandler->moduleExists('commerce_shipping')) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
      $shipments = $order->get('shipments')->referencedEntities();

      if (!empty(($shipments)) && $shippingAddress = $this->getShippingAddress(reset($shipments))) {
        $worldPayFormApi->addShipmentAddress($shippingAddress);
      }
    }

    $data = $worldPayFormApi->createData();
    $order->setData('worldpay_form', [
      'request' => $data,
      'return_url' => $this->getNotifyUrl()->toString(),
    ]);
    $order->save();

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    $url = C_WORLDPAY_BG_DEF_SERVER_TEST;
    if ($this->getMode() == WORLDPAY_BG_SERVER_LIVE) {
      $url = C_WORLDPAY_BG_DEF_SERVER_LIVE;
    }
    return $url;
  }

  /**
   * @param $order
   *
   * @return \Drupal\commerce_worldpay\Plugin\Commerce\PaymentGateway\WorldPayHelper
   */
  protected function getWorldPayApi($order) {
    return new WorldPayHelper($order, $this->getConfiguration());
  }

  /**
   * Builds the URL to the "return" page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The "return" page url.
   */
  protected function buildReturnUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Builds the URL to the "cancel" page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The "cancel" page url.
   */
  protected function buildCancelUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Create a Commerce Payment from a WorldPay form request successful result.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return PaymentInterface $payment
   *    The commerce payment record.
   */
  public function createPayment(array $responseData, OrderInterface $order) {

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $paymentStorage */
    $paymentStorage = $this->entityTypeManager->getStorage('commerce_payment');

    /** @var PaymentInterface $payment */
    $payment = $paymentStorage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $responseData['MC_orderId'],
      'remote_state' => $responseData['transStatus'],
      'authorized' => $this->time->getRequestTime(),
    ]);

    $payment->save();

    return $payment;
  }

  /**
   * {@inheritdoc}
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function onNotify(Request $request) {
    $content = $request->getMethod() === 'POST' ? $request->getContent() : FALSE;
    if (!$content) {
      $this->logger->error('There is no response was received');
      throw new PaymentGatewayException();
    }

    if ($this->configuration['debug'] === 'log') {
      // Just development debug.
      $this->logger->debug('<pre>' . $content . '</pre>');
      $this->logger->debug(
        'Transaction ID %transID Order ID %orderID', [
          '%transID' => $request->request->get('transId'),
          '%orderID' => $request->request->get('MC_orderId')
        ]
      );
    }

    // Get and check the VendorTxCode.
    $txCode = $request->request->get('transId') !== NULL ? $request->request->get('transId') : FALSE;

    if (empty($txCode) || empty($request->request->get('MC_orderId'))) {
      $this->logger->error('No Transaction code have been returned.');
      throw new PaymentGatewayException();
    }

    $order = $this->entityTypeManager->getStorage('commerce_order')->load($request->request->get('MC_orderId'));
    $build = [];
    if ($order instanceof OrderInterface && $request->request->get('transStatus') === 'Y') {
      $payment = $this->createPayment($request->request->all(), $order);
      $payment->state = 'capture_completed';
      $payment->save();

      $logLevel = 'info';
      $logMessage = 'OK Payment callback received from WorldPay for order %order_id with status code %transID';
      $logContext = [
        '%order_id' => $order->id(),
        '%transID' => $request->request->get('transId'),
      ];
      $this->logger->log($logLevel, $logMessage, $logContext);

      $build += [
        '#theme' => 'commerce_worldpay_success',
        '#transaction_id' => $request->request->get('transId'),
        '#order_id' => $order->id(),
        '#return_url' => $this->buildReturnUrl($order),
        '#cache' => ['max-age' => 0],
      ];
    }

    if ($order instanceof OrderInterface && $request->request->get('transStatus') === 'C') {
      $logLevel = 'info';
      $logMessage
        = 'Cancel Payment callback received from WorldPay for order %order_id with status code %transID';
      $logContext = [
        '%order_id' => $order->id(),
        '%transID'  => $request->request->get('transId'),
      ];
      $this->logger->log($logLevel, $logMessage, $logContext);

      $build += [
        '#theme' => 'commerce_worldpay_cancel',
        '#transaction_id' => $request->request->get('transId'),
        '#order_id' => $order->id(),
        '#return_url' => $this->buildCancelUrl($order),
        '#cache' => ['max-age' => 0],
      ];

    }

    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setStatusCode(200);
    $response->setContent($output);
    $response->headers->set('Content-Type', 'text/html');

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('request_stack'),
      $container->get('logger.channel.commerce_worldpay'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('link_generator')
    );
  }

}
