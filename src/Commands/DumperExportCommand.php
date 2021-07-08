<?php
namespace kodeops\LaravelMysqlDumper\Commands;

use Illuminate\Console\Command;
use kodeops\LaravelMysqlDumper\Dumper;

class DumperExportCommand extends Command
{
    protected $signature = 'mysql-dumper:export {file?} {--upload} {--force}';
    protected $description = 'Export database to file';

    public function handle()
    {
        $dumper = new Dumper($this->option('force'));
        $this->comment("Starting export process of “{$dumper->getConnection('source')->getDatabase()}”...");
        $dumper->export($this->argument('file'), $this->option('upload'));
        $this->info("Database “{$dumper->getConnection('source')->getDatabase()}” successfully exported.");
        $this->comment($dumper->getDump());
    }
}
