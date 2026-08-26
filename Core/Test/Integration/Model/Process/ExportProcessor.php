<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 *
 *
 */
// @codingStandardsIgnoreStart
namespace MageOS\NetSuiteConnector\Core\Test\Integration\Model\Process;

use Magento\TestFramework\Helper\Bootstrap;
/**
 * Class ExportProcessor
 *
 * Ignore PHPMD
 * @SuppressWarnings(PHPMD)
 */
class ExportProcessor extends \PHPUnit\Framework\TestCase
{
    private $exportProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp():void
    {
        /** @var \Magento\Framework\ObjectManagerInterface $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $this->exportProcessor = $objectManager->get(
            \MageOS\NetSuiteConnector\Core\Model\Process\ExportProcessor::class
        );
    }

    /**
     * check that exeption is thrown on not existed processor initialization
     */
    public function testNotExistedProcessorCall()
    {
        $exportModel = $this->exportProcessor->getEntityProcessor('not-existed-processor-code');
        $this->assertEquals(false, $exportModel);
    }
}
