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

namespace MageOS\NetSuiteConnector\Core\Ui\DataProvider\Monitor\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;

class Actions extends Column
{
    private const URL_VIEW = 'netsuite/monitor/view';
    private const URL_RUN = 'netsuite/monitor/execute';
    private const URL_CANCEL = 'netsuite/monitor/cancel';

    private \Magento\Framework\UrlInterface $url;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $url
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $url,
        array $components = [],
        array $data = []
    ) {
        $this->url = $url;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $actions = [
                'view' => [
                    'href' => $this->url->getUrl(self::URL_VIEW, ['id' => $item['monitor_id']]),
                    'label' => __('View')
                ]
            ];
            $statusOriginal = new Status($item['status_original']);
            if ($statusOriginal->equals(Status::ERROR())) {
                $actions['execute'] = [
                    'href' => $this->url->getUrl(self::URL_RUN, ['id' => $item['monitor_id']]),
                    'label' => __('Back into Queue')
                ];
            }

            if ($statusOriginal->equals(Status::IN_QUEUE()) || $statusOriginal->equals(Status::RETRY())) {
                $actions['cancel'] = [
                    'href' => $this->url->getUrl(self::URL_CANCEL, ['id' => $item['monitor_id']]),
                    'label' => __('Cancel'),
                    'confirm' => [
                        'title' => __('Cancel process?'),
                        'message' => __('Are you sure you want to Cancel this Queue Item?')
                    ]
                ];
            }

            $item[$this->getData('name')] = $actions;
        }

        return $dataSource;
    }
}
