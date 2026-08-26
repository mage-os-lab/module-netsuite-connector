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

namespace MageOS\NetSuiteConnector\Core\Test\Integration\Contract;

use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker;
use NetSuite\NetSuiteService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Every integration test replaces the SOAP client with NetSuiteServiceFaker. Nothing else checks
 * that the faker still matches the client it stands in for, so an SDK signature change would leave
 * the whole suite green while production breaks. This test is that check.
 */
class NetSuiteServiceFakerTest extends TestCase
{
    /**
     * Faker methods that exist for the tests themselves and have no counterpart on the SDK client.
     */
    private const TEST_ONLY_METHODS = [
        '__construct',
        'setParameters',
        'getAddRequest',
        'getUpdateRequest',
        'getInitializeRequest',
    ];

    /**
     * Each faker method must name a real SDK operation
     *
     * A typo, or a method kept after the SDK dropped it, means the faker answers a call the real
     * client would reject.
     */
    public function testEveryFakerMethodExistsOnTheSdkClient(): void
    {
        $missing = [];

        foreach ($this->fakerOperations() as $method) {
            if (!method_exists(NetSuiteService::class, $method->getName())) {
                $missing[] = $method->getName();
            }
        }

        $this->assertSame(
            [],
            $missing,
            'NetSuiteServiceFaker implements methods that NetSuiteService does not have. '
            . 'Either the SDK dropped them or the faker has a typo: ' . implode(', ', $missing)
        );
    }

    /**
     * Each faker method must accept what the SDK method accepts
     *
     */
    #[DataProvider('fakerOperationProvider')]
    public function testFakerSignaturesMatchTheSdkClient(string $methodName): void
    {
        $faker = new ReflectionMethod(NetSuiteServiceFaker::class, $methodName);
        $sdk = new ReflectionMethod(NetSuiteService::class, $methodName);

        $this->assertSame(
            $sdk->getNumberOfRequiredParameters(),
            $faker->getNumberOfRequiredParameters(),
            sprintf('%s(): required parameter count drifted from the SDK', $methodName)
        );

        foreach ($sdk->getParameters() as $position => $sdkParam) {
            $fakerParams = $faker->getParameters();
            $this->assertArrayHasKey(
                $position,
                $fakerParams,
                sprintf('%s(): the SDK takes parameter %d and the faker does not', $methodName, $position + 1)
            );

            $this->assertSame(
                $this->typeName($sdkParam->getType()),
                $this->typeName($fakerParams[$position]->getType()),
                sprintf('%s(): parameter %d type drifted from the SDK', $methodName, $position + 1)
            );
        }
    }

    /**
     * Supply the operation names to the signature test
     *
     * @return array<string, array<int, string>>
     */
    public static function fakerOperationProvider(): array
    {
        $cases = [];

        foreach ((new ReflectionClass(NetSuiteServiceFaker::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            if (in_array($m->getName(), self::TEST_ONLY_METHODS, true)) {
                continue;
            }
            if (!method_exists(NetSuiteService::class, $m->getName())) {
                continue;
            }
            $cases[$m->getName()] = [$m->getName()];
        }

        return $cases;
    }

    /**
     * Get the faker methods that claim to stand in for an SDK operation
     *
     * @return ReflectionMethod[]
     */
    private function fakerOperations(): array
    {
        $methods = (new ReflectionClass(NetSuiteServiceFaker::class))->getMethods(ReflectionMethod::IS_PUBLIC);

        return array_filter($methods, static function (ReflectionMethod $m): bool {
            return !in_array($m->getName(), self::TEST_ONLY_METHODS, true);
        });
    }

    /**
     * Render a parameter type for comparison
     *
     * @param \ReflectionType|null $type
     * @return string
     */
    private function typeName(?\ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return ltrim($type->getName(), '\\');
        }

        return $type === null ? 'mixed' : (string)$type;
    }
}
