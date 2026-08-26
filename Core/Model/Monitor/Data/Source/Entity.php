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
 *
 */

namespace MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Source;

class Entity implements \Magento\Framework\Data\OptionSourceInterface
{
    private \Magento\Framework\ObjectManager\ConfigInterface $config;
    private array $entities;

    public function __construct(
        \Magento\Framework\ObjectManager\ConfigInterface $config
    ) {
        $this->config = $config;
    }

    private function getEntities()
    {
        if (isset($this->entities)) {
            return $this->entities;
        }
        $exportArguments = $this->config->getArguments(\MageOS\NetSuiteConnector\Core\Model\Process\ExportProcessor::class);
        $importArguments = $this->config->getArguments(\MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor::class);

        $exportEntities = isset($exportArguments['processors']['_vac_'])
            ? $exportArguments['processors']['_vac_'] : $exportArguments['processors'];

        $importEntities = isset($importArguments['processors']['_vac_'])
            ? $importArguments['processors']['_vac_'] : $importArguments['processors'];

        $this->entities = array_merge(
            array_keys($exportEntities),
            array_keys($importEntities)
        );

        return $this->entities;
    }

    public function toArray()
    {
        $entities = $this->getEntities();

        $data = [];
        foreach ($entities as $entity) {
            $data[$entity] = $this->getLabel($entity);
        }

        return $data;
    }

    public function toOptionArray()
    {
        $entities = $this->toArray();
        $options = [];

        foreach ($entities as $entity => $label) {
            $options[] = ['value' => $entity, 'label' => $label];
        }

        return $options;
    }

    /**
     * This is a temporary method, but its sticking around. Maybe on next refactor we add a di.xml where every
     * process that gets registered adds also a label which is used here
     *
     * @param string $entity
     * @return string
     */
    private function getLabel(string $entity): string
    {
        $parts = explode('_', $entity);
        $parts = array_map('ucfirst', $parts);

        return implode(' ', $parts);
    }
}
