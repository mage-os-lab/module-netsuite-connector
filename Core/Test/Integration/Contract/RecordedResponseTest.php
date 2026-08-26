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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

/**
 * The recorded NetSuite responses are the only description of the SOAP payloads this package
 * handles. Nothing validated them, so a recording could name the wrong SDK record type and every
 * test would still pass. That is how an InventoryItem payload sat inside an AssemblyItem until
 * PHP 8.2 started reporting the undeclared properties.
 */
class RecordedResponseTest extends TestCase
{
    /**
     * Locals the faker has in scope when it includes a recording.
     */
    public const FAKER_PARAMETERS = [
        "has_files" => 1,
        "files" => [["file_name" => "recording.jpg", "file_content" => ""]],
        "search_success" => 1,
        "get_success" => 1,
        "add_success" => 1,
        "netsuite_internal_id" => "1111",
    ];

    public const FAKER_SCOPE = [
        'netsuiteId' => '1111',
        'success' => true,
        'isPerson' => false,
        'record' => null,
        'productQty' => 1,
        'suffix' => '',
    ];

    /**
     * Every recording must build objects out of declared SDK properties
     *
     * A property the SDK class does not declare means the recording names the wrong record type.
     * PHP 9 turns those assignments into an Error, so this also guards the upgrade.
     *
     */
    #[DataProvider('recordedResponseProvider')]
    public function testRecordingUsesDeclaredPropertiesOnly(string $file): void
    {
        $response = $this->loadRecording($file);

        $this->assertIsObject($response, sprintf('%s did not return an object', $file));

        $undeclared = [];
        $this->collectUndeclaredProperties($response, '', $undeclared);

        $this->assertSame(
            [],
            $undeclared,
            sprintf(
                "%s assigns properties that the SDK class does not declare:\n  %s",
                $file,
                implode("\n  ", $undeclared)
            )
        );
    }

    /**
     * Every recording must return a NetSuite SDK object
     *
     */
    #[DataProvider('recordedResponseProvider')]
    public function testRecordingReturnsAnSdkResponse(string $file): void
    {
        $response = $this->loadRecording($file);

        $this->assertStringStartsWith(
            'NetSuite\\Classes\\',
            get_class($response),
            sprintf('%s returned %s, which is not a NetSuite SDK class', $file, get_class($response))
        );
    }

    /**
     * Find every recorded response shipped with the package
     *
     * @return array<string, array<int, string>>
     */
    public static function recordedResponseProvider(): array
    {
        $moduleRoot = dirname(__DIR__, 4);
        $cases = [];

        $directories = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($directories as $path) {
            if (!$path->isFile() || !str_contains($path->getPathname(), '_files_ns_response')) {
                continue;
            }
            $relative = substr($path->getPathname(), strlen($moduleRoot) + 1);
            $cases[$relative] = [$path->getPathname()];
        }

        ksort($cases);

        return $cases;
    }

    /**
     * Run a recording the way the faker runs it
     *
     * Most recordings are PHP that builds and returns a response. One is a serialised object dump.
     * Some read $this->parameters, so the include is bound to a configured faker.
     *
     * @param string $file
     * @return mixed
     */
    private function loadRecording(string $file)
    {
        if (!str_starts_with((string)file_get_contents($file, false, null, 0, 5), "<?php")) {
            return unserialize(trim((string)file_get_contents($file)));
        }

        $faker = new NetSuiteServiceFaker(dirname($file));
        $faker->setParameters(self::FAKER_PARAMETERS);

        $run = function (string $path) {
            extract(RecordedResponseTest::FAKER_SCOPE, EXTR_SKIP);

            return include $path;
        };

        return \Closure::bind($run, $faker, NetSuiteServiceFaker::class)($file);
    }

    /**
     * Walk an SDK object graph and record every property its class does not declare
     *
     * @param mixed $value
     * @param string $path
     * @param array $undeclared
     * @return void
     */
    private function collectUndeclaredProperties($value, string $path, array &$undeclared): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->collectUndeclaredProperties($item, sprintf('%s[%s]', $path, $key), $undeclared);
            }
            return;
        }

        if (!is_object($value) || !str_starts_with(get_class($value), 'NetSuite\\Classes\\')) {
            return;
        }

        $declared = [];
        foreach ((new ReflectionObject($value))->getProperties() as $property) {
            $declared[$property->getName()] = true;
        }

        foreach (get_object_vars($value) as $name => $item) {
            $childPath = $path === '' ? $name : $path . '->' . $name;

            if (!isset($declared[$name])) {
                $undeclared[] = sprintf('%s (on %s)', $childPath, get_class($value));
                continue;
            }

            $this->collectUndeclaredProperties($item, $childPath, $undeclared);
        }
    }
}
