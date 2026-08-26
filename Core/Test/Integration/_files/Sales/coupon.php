<?php

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$registry = $objectManager->get('Magento\Framework\Registry');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$coupon['name'] = 'custom_discount';
$coupon['desc'] = 'Custom discount cupon.';
$coupon['start'] = date('Y-m-d');
$coupon['end'] = '';
$coupon['max_redemptions'] = 1;
$coupon['discount_type'] ='cart_fixed';
$coupon['discount_amount'] = 50;
$coupon['flag_is_free_shipping'] = 'yes';
$coupon['redemptions'] = 1;
$coupon['code'] ='NL01-1234'; //this code will normally be autogenetated but i am hard coding for testing purposes

$shoppingCartPriceRule = $objectManager->create('Magento\SalesRule\Model\Rule');
$shoppingCartPriceRule->setName($coupon['name'])
    ->setDescription($coupon['desc'])
    ->setFromDate($coupon['start'])
    ->setToDate($coupon['end'])
    ->setUsesPerCustomer($coupon['max_redemptions'])
    ->setCustomerGroupIds(['0','1','2','3',])
    ->setIsActive(1)
    ->setSimpleAction($coupon['discount_type'])
    ->setDiscountAmount($coupon['discount_amount'])
    ->setDiscountQty(1)
    ->setApplyToShipping($coupon['flag_is_free_shipping'])
    ->setTimesUsed($coupon['redemptions'])
    ->setWebsiteIds(['1'])
    ->setCouponType(2)
    ->setCouponCode($coupon['code'])
    ->setUsesPerCoupon(null);
try {
    $shoppingCartPriceRule->save();
} catch (\Magento\Framework\Exception\AlreadyExistsException $ex) {

}
