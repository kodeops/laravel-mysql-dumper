<?php
namespace kodeops\LaravelMysqlDumper\Commands;

use Illuminate\Console\Command;
use kodeops\LaravelMysqlDumper\Dumper;

class DumperCloneCommand extends Command
{
    protected $signature = 'mysql-dumper:clone {--force}';
    protected $description = 'Clone database';

    public function handle()
    {
        $dumper = new Dumper($this->argument('force'));
        $dumper->import($dumper->export());
    }
}
