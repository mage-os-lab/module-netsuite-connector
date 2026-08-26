<?php
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManager */
$messageManager = $objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
$messages = $messageManager->receive(\MageOS\NetSuiteConnector\Core\Enum\Message\Queue::EXPORT(), 50);

foreach ($messages as $message) {
    $messageManager->deleteById($message->getId());
}
