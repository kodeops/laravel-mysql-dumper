<?php
namespace kodeops\LaravelMysqlDumper\Commands;

use Illuminate\Console\Command;
use kodeops\LaravelMysqlDumper\Dumper;

class DumperImportCommand extends Command
{
    protected $signature = 'mysql-dumper:import {file} {--force}';
    protected $description = 'Import dump file database';

    public function handle()
    {
        $dumper = new Dumper($this->option('force'));
        $this->comment("Starting importing process to “{$dumper->getConnection('destination')->getDatabase()}”...");
        $dumper->import($this->argument('file'));
        $this->info("Dump successfully imported to “{$dumper->getConnection('destination')->getDatabase()}”.");
    }
}
