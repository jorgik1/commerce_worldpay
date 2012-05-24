CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Known issues
 * Alternatives
 * Support
 * Sponsorship
 * Acknowledgements/Credits


INTRODUCTION
------------

TODO


INSTALLATION
------------

 1. Copy the 'commerce_worldpay_bg' folder into the modules directory
    usually: '/sites/all/modules/'.

 2. In your Drupal site, enable the module under Administration -> Modules
    The module will be in the group Commerce - Payment.

 3. TODO


KNOWN ISSUES
------------

 * This module will not work reliably at the moment if the order
   field commerce_customer_billing is missing or if that field (profile) is
   missing the field commerce_customer_address.

TODO
----
* Set appropriate Commerce Transaction status for SecureCode ('authentication').
* Set appropriate Commerce Transaction status for AVS ('AVS').
* Finish Bartik Worldpay theme (images etc).
* Make the module function fine without an addressfield or billing profile.

SUPPORT
-------

If you encounter any issues, please file a support request
at http://drupal.org/project/issues/commerce_worldpay_bg


SPONSORSHIP
-----------

This module was originaly developed for Zixiao (http://www.zixiao.co.uk).

ACKNOWLEDGEMENTS/CREDITS
------------------------

Much of the code here got a running start thanks to the Commerce PayPal and Sage
payment modules so thank you to the authors ikos and rszrama. Also thanks to the
Ubercart uc_worldpay author Hans Idink and psynaptic as that module also gave me
a running start on working with WorldPay's API.

AUTHORS
-------
Adam Lyall aka MagicMyth <magicmyth@magicmyth.com>
