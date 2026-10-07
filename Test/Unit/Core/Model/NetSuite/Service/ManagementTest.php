<?php declare(strict_types=1);
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
 */

namespace MageOS\NetSuiteConnector\Test\Unit\Core\Model\NetSuite\Service;

use MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException;
use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ClassMap;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management;
use MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry;
use NetSuite\Classes\GetServerTimeResponse;
use NetSuite\Classes\GetServerTimeResult;
use NetSuite\Classes\Status;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

class ManagementTest extends TestCase
{
    /**
     * All call sites use the default $retry argument, so 5 is the number of attempts the
     * connector is meant to make, not just a coincidental default value
     */
    public function testTheDefaultRetryCountIsFive(): void
    {
        $parameter = new ReflectionParameter([Management::class, 'retryNetSuiteQuery'], 'retry');

        $this->assertSame(5, $parameter->getDefaultValue());
    }

    public function testASoapFaultIsRetriedAndTheCallThatFollowsCanStillSucceed(): void
    {
        $management = $this->management();
        $response = $this->validResponse();

        $attempts = 0;
        $result = $management->retryNetSuiteQuery(function () use (&$attempts, $response) {
            $attempts++;
            if ($attempts === 1) {
                throw new \SoapFault('Server', 'Concurrent request limit exceeded');
            }
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertSame(2, $attempts);
    }

    public function testTheLoopMakesExactlyTheRequestedNumberOfAttempts(): void
    {
        $management = $this->management();

        $attempts = 0;
        try {
            $management->retryNetSuiteQuery(function () use (&$attempts) {
                $attempts++;
                throw new \SoapFault('Server', 'timeout');
            }, 3);
            $this->fail('Expected a NetSuiteRuntimeException after every attempt failed.');
        } catch (NetSuiteRuntimeException $e) {
            $this->assertSame(3, $attempts);
        }
    }

    public function testTheSoapClientGetsTheConnectorClassMap(): void
    {
        $options = $this->management()->getNetSuiteServiceOptions('default');

        $this->assertSame((new ClassMap())->get(), $options['classmap']);
    }

    private function management(): Management
    {
        $connectorConfig = $this->createStub(ConnectorConfig::class);
        $connectorConfig->method('getSoapRequestTimeout')->willReturn(60);

        return new Management(
            new ModuleRegistry(),
            $connectorConfig,
            $this->createStub(ConstructorFactory::class)
        );
    }

    private function validResponse(): GetServerTimeResponse
    {
        $status = new Status();
        $status->isSuccess = true;

        $result = new GetServerTimeResult();
        $result->status = $status;
        $result->serverTime = '2026-09-15T00:00:00+0000';

        $response = new GetServerTimeResponse();
        $response->getServerTimeResult = $result;

        return $response;
    }
}
