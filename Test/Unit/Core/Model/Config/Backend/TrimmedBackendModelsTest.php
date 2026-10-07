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

namespace MageOS\NetSuiteConnector\Test\Unit\Core\Model\Config\Backend;

use MageOS\NetSuiteConnector\Core\Model\Config\Backend\TrimmedEncrypted;
use MageOS\NetSuiteConnector\Core\Model\Config\Backend\TrimmedValue;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TrimmedBackendModelsTest extends TestCase
{
    #[DataProvider('valueProvider')]
    public function testItSavesTheValueWithoutSurroundingWhitespace(?string $submitted, string $expected): void
    {
        $model = $this->model(TrimmedValue::class);
        $model->setValue($submitted);

        $model->beforeSave();

        $this->assertSame($expected, $model->getValue());
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function valueProvider(): array
    {
        return [
            'trailing space' => [
                'https://1234567.suitetalk.api.netsuite.com ',
                'https://1234567.suitetalk.api.netsuite.com',
            ],
            'leading tab and newline' => ["\t1234567_SB1\n", '1234567_SB1'],
            'clean value' => ['consumer-key', 'consumer-key'],
            'empty value' => [null, ''],
        ];
    }

    public function testItEncryptsTheSecretWithoutSurroundingWhitespace(): void
    {
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->expects($this->once())->method('encrypt')->with('token-secret')->willReturn('cipher');

        $model = $this->model(TrimmedEncrypted::class, ['encryptor' => $encryptor]);
        $model->setValue(" token-secret \n");

        $model->beforeSave();

        $this->assertSame('cipher', $model->getValue());
    }

    /**
     * The admin form sends the obscured value back for an unchanged secret: it must stay unsaved
     */
    public function testItLeavesAnUnchangedObscuredSecretAlone(): void
    {
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->expects($this->never())->method('encrypt');

        $model = $this->model(TrimmedEncrypted::class, ['encryptor' => $encryptor]);
        $model->setValue('****** ');

        $model->beforeSave();

        $this->assertFalse($model->isSaveAllowed());
    }

    /**
     * Build a backend model with a stub for every constructor dependency, so the save events go nowhere
     *
     * @param string $class
     * @param array<string, mixed> $arguments
     * @return mixed
     */
    private function model(string $class, array $arguments = [])
    {
        $context = $this->createStub(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createStub(ManagerInterface::class));
        $context->method('getLogger')->willReturn($this->createStub(LoggerInterface::class));
        $arguments += ['context' => $context];

        $constructorArguments = [];
        foreach ((new \ReflectionClass($class))->getConstructor()->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (array_key_exists($parameter->getName(), $arguments)) {
                $constructorArguments[] = $arguments[$parameter->getName()];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $constructorArguments[] = $parameter->getDefaultValue();
            } else {
                $constructorArguments[] = $this->createStub($type->getName());
            }
        }

        return new $class(...$constructorArguments);
    }
}
