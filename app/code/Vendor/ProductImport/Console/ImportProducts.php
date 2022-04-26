<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Console;

use Magento\Framework\Exception\ValidatorException;
use Magento\Setup\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vendor\ProductImport\Model\ProductImporter;

class ImportProducts extends Command
{
    private ProductImporter $productImport;

    public function __construct(
        ProductImporter $productImport
    ) {
        parent::__construct();
        $this->productImport = $productImport;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName('bbb:import-products:run');
        $this->setDescription('Run Bbb products import');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws ValidatorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Start product import');

        $this->productImport->execute();

        $output->writeln('Finish product import');
    }
}
