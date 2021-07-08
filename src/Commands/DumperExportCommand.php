<?php
namespace kodeops\LaravelMysqlDumper\Commands;

use Illuminate\Console\Command;
use kodeops\LaravelMysqlDumper\Dumper;

class DumperExportCommand extends Command
{
    protected $signature = 'mysql-dumper:export {file?} {upload?} {--force}';
    protected $description = 'Export database to file';

    public function handle()
    {
        (new Dumper($this->option('force')))
            ->export($this->argument('file'), $this->option('upload'));
    }
}
