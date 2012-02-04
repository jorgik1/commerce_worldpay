<?php // kate: space-indent on; indent-width 2; mixedindent off; indent-mode normal;s
/**
 * @file
 * WorldPay custom page theme
 *
 * WorldPay generates custom pages by calling the Payment Response/Notification
 * server, which generates a HTML(ish) output with some WorldPay propriority
 * tags.
 *
 * We cannot point to any files on this server. They must be uploaded to
 * WorldPay in the installtion control panel and then reference here as:
 *    path="/i/$installtion_id/file.ext"
 * You can optionaly have WorldPay set the installtion_id instead of the one
 * store in this site by using the WorldPay tag:
 * path="/i/<wpdisplay item=instId>/file.ext">
 * For this reason we do not make use of Drupal's asset attachment features.
 * For more information on what WorldPay tags are available
 * @see: http://www.worldpay.com/support/kb/bg/paymentresponse/pr5402.html
 *
 * NOTE this template does not go through the usual theme route so don't
 * expect the same variables availble to html.tpl.php
 *
 * Variables:
 * - $installtion_id: The WorldPay installtion ID stored in the sites 
 *   Commerece payment settings page.
 * - $order_id: The current Commerec order's ID
 * - $order_no: The current Commerec order's number
 * - $content: The rendered content of the page
 *
 * @see template_preprocess_commerce_worldpay_bg_htmll()
 */
?>
<div id="page-wrapper">
  <div id="page">
    <?php print $content; ?>
  </div>
</div>
