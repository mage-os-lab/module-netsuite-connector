<?php
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Model\Config;

class EtcXmlResolver
{
    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    private $readerConfig;
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    private $dirReaderFactory;
    /**
     * @var \Magento\Framework\Simplexml\ElementFactory
     */
    private $xmlElementFactory;

    private $xmlCache = [];

    public function __construct(
        \Magento\Framework\Module\Dir\Reader $readerConfig,
        \Magento\Framework\Filesystem\Directory\ReadFactory $dirReader,
        \Magento\Framework\Simplexml\ElementFactory $xmlElementFactory
    ) {

        $this->readerConfig = $readerConfig;
        $this->dirReaderFactory = $dirReader;
        $this->xmlElementFactory = $xmlElementFactory;
    }

    public function getXml(string $filename): \Magento\Framework\Simplexml\Element
    {
        if (isset($this->xmlCache[$filename])) {
            return $this->xmlCache[$filename];
        }

        $etcPath = $this->readerConfig->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
            'MageOS_NetSuiteConnector'
        );
        $directoryRead = $this->dirReaderFactory->create($etcPath . '/netsuite');
        $xmlData = $directoryRead->readFile($filename);
        $xml = $this->xmlElementFactory->create(['data' => $xmlData]);
        $this->xmlCache[$filename] = $xml;

        return $xml;
    }
}
