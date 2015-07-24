<?php
/**
 * A map of classname => filename for SPL autoloading.
 *
 * @package AuthorizeNet
 */

$libDir    = __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
$sharedDir = $libDir . 'shared' . DIRECTORY_SEPARATOR;

return array(
    'M2_AuthorizeNetAIM'            => $libDir    . 'AuthorizeNetAIM.php',
    'M2_AuthorizeNetAIM_Response'   => $libDir    . 'AuthorizeNetAIM.php',
    'M2_AuthorizeNetARB'            => $libDir    . 'AuthorizeNetARB.php',
    'M2_AuthorizeNetARB_Response'   => $libDir    . 'AuthorizeNetARB.php',
    'M2_AuthorizeNetAddress'        => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetBankAccount'    => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetCIM'            => $libDir    . 'AuthorizeNetCIM.php',
    'M2_AuthorizeNetCIM_Response'   => $libDir    . 'AuthorizeNetCIM.php',
    'M2_AuthorizeNetCP'             => $libDir    . 'AuthorizeNetCP.php',
    'M2_AuthorizeNetCP_Response'    => $libDir    . 'AuthorizeNetCP.php',
    'M2_AuthorizeNetCreditCard'     => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetCustomer'       => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetDPM'            => $libDir    . 'AuthorizeNetDPM.php',
    'M2_AuthorizeNetException'      => $sharedDir . 'AuthorizeNetException.php',
    'M2_AuthorizeNetLineItem'       => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetPayment'        => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetPaymentProfile' => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetRequest'        => $sharedDir . 'AuthorizeNetRequest.php',
    'M2_AuthorizeNetResponse'       => $sharedDir . 'AuthorizeNetResponse.php',
    'M2_AuthorizeNetSIM'            => $libDir    . 'AuthorizeNetSIM.php',
    'M2_AuthorizeNetSIM_Form'       => $libDir    . 'AuthorizeNetSIM.php',
    'M2_AuthorizeNetSOAP'           => $libDir    . 'AuthorizeNetSOAP.php',
    'M2_AuthorizeNetTD'             => $libDir    . 'AuthorizeNetTD.php',
    'M2_AuthorizeNetTD_Response'    => $libDir    . 'AuthorizeNetTD.php',
    'M2_AuthorizeNetTransaction'    => $sharedDir . 'AuthorizeNetTypes.php',
    'M2_AuthorizeNetXMLResponse'    => $sharedDir . 'AuthorizeNetXMLResponse.php',
    'M2_AuthorizeNet_Subscription'  => $sharedDir . 'AuthorizeNetTypes.php',
);
