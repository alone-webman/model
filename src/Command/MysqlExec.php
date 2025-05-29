<?php

namespace AloneWebMan\Model\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MysqlExec extends Command {
    protected static $defaultName        = 'alone:mysql';
    protected static $defaultDescription = 'show mysql config';

    public function execute(InputInterface $input, OutputInterface $output): int {
        echo "--------------------------------------------------------\r\n";
        $config = config('database');
        $headers = [
            'driver',
            'name',
            'host',
            'port',
            'database',
            'username',
            'password',
            'prefix',
            'charset',
            'collation',
            'default',
            //'unix_socket',
            //'strict',
            //'engine',
            //'schema',
            //'sslmode'
        ];
        $rows = [];
        foreach (($config['connections'] ?? []) as $name => $db_config) {
            $row = [];
            foreach ($headers as $key) {
                $row[] = match ($key) {
                    'name'    => $name,
                    'default' => $config['default'] == $name ? 'true' : 'false',
                    default   => $db_config[$key] ?? '',
                };
            }
            if ($config['default'] == $name) {
                array_unshift($rows, $row);
            } else {
                $rows[] = $row;
            }
        }
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        echo "--------------------------------------------------------\r\n";
        return self::SUCCESS;
    }
}