<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
$messageManagement = $objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
$messages = $messageManagement->receive(\MageOS\NetSuiteConnector\Core\Enum\Message\Queue::EXPORT(), 50);

foreach ($messages as $message) {
    $messageManagement->deleteById($message->getId());
}
