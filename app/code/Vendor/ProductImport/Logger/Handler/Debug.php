<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use DateTime;

class Debug extends Base
{
    /**
     * Can't define property type because:
     * "Type must not be defined (as in base class '\Magento\Framework\Logger\Handler\Base')"
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        $this->filesystem = $filesystem;
        $this->fileName = $this->getFilename();

        StreamHandler::__construct(
            $filePath ? $filePath . $this->fileName : BP . DIRECTORY_SEPARATOR . $this->fileName,
            $this->loggerType
        );

        $this->setFormatter(new LineFormatter(null, null, true));
    }

    /**
     * @return string
     */
    private function getFilename(): string
    {
        return '/var/log/bbbProductImport/' . (new DateTime())->format('Y-m-d') . '_debug.log';
    }
}
