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
 * Class ImportProcessor
 *
 * Ignore PHPMD
 * @SuppressWarnings(PHPMD)
 */
class ImportProcessor extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor
     */
    private $importProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp():void
    {
        /** @var \Magento\Framework\ObjectManagerInterface $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $this->importProcessor = $objectManager->get(
            \MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor::class
        );
    }

    /**
     * check that exeption is thrown on not existed processor initialization
     */
    public function testNotExistedProcessorCall()
    {
        $importModel =  $this->importProcessor->getEntityProcessor('not-existed-processor-code');
        $this->assertEquals(false, $importModel);
    }
}
