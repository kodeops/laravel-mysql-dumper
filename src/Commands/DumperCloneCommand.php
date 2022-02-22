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
        $dumper = new Dumper($this->option('force'));
        $this->comment("Starting cloning process of “{$dumper->getConnection('source')->getDatabase()}” from “{$dumper->getConnection('source')->getHost()}”...");
        $dumper->clone();
        $this->info("Database successfully cloned (from “{$dumper->getConnection('source')->getDatabase()}” to “{$dumper->getConnection('destination')->getDatabase()}”).");
    }
}
