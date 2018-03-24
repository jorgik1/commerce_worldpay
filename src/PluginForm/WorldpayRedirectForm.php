<?php
/**
 * Created by PhpStorm.
 * User: alexburrows
 * Date: 18/03/2018
 * Time: 19:39
 */

namespace Drupal\commerce_worldpay\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class WorldpayRedirectForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

//    return $this->buildRedirectForm(
//      $form,
//      $form_state,
//      'https://payment.quickpay.net',
//      $data,
//      PaymentOffsiteForm::REDIRECT_POST
//    );
  }

}