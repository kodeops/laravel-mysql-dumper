<?php
namespace kodeops\LaravelMysqlDumper\Commands;

use Illuminate\Console\Command;
use kodeops\LaravelMysqlDumper\Dumper;

class DumperImportCommand extends Command
{
    protected $signature = 'mysql-dumper:import {file} {check?} {--force}';
    protected $description = 'Import dump file database';

    public function handle()
    {
        (new Dumper($this->option('force')))
            ->import($this->argument('file'), $this->option('check'));
    }
}
