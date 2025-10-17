<?php

namespace AloneWebMan\Model\Command;

use AloneWebMan\Model\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModelExec extends Command {
    protected static $defaultName        = 'alone:model';
    protected static $defaultDescription = 'create model file';

    public function execute(InputInterface $input, OutputInterface $output): int {
        echo "--------------------------------------------------------\r\n";
        Helper::model();
        echo "--------------------------------------------------------\r\n";
        return self::SUCCESS;
    }
}