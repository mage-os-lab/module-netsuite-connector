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

namespace MageOS\NetSuiteConnector\Core\Test\Unit\Model\Config\Source;

use MageOS\NetSuiteConnector\Core\Model\Config\Source\AuthType;
use MageOS\NetSuiteConnector\Core\Model\Config\Source\Visibility;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\TestCase;

class AuthTypeTest extends TestCase
{
    /**
     * @var AuthType
     */
    private AuthType $source;

    /**
     * Build the option source under test.
     */
    protected function setUp(): void
    {
        $this->source = new AuthType();
    }

    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->source);
        $this->assertInstanceOf(Visibility::class, $this->source);
    }

    /**
     * Token based authentication is the only supported option and keeps its stored value.
     */
    public function testItOffersTokenAuthenticationOnly(): void
    {
        $options = $this->source->toArray();

        $this->assertSame(['token'], array_keys($options));
        $this->assertSame('Token', (string)$options['token']);
    }

    /**
     * The option array must pair the token label with the token value.
     */
    public function testItConvertsTheTokenOptionIntoALabelAndValuePair(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(1, $options);
        $this->assertSame('token', $options[0]['value']);
        $this->assertSame('Token', (string)$options[0]['label']);
    }

    /**
     * The catalog visibility options of the parent class must not leak into the authentication options.
     */
    public function testItDoesNotInheritTheVisibilityOptions(): void
    {
        $this->assertNotSame((new Visibility())->toArray(), $this->source->toArray());
    }
}
