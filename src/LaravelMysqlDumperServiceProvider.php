<?php
namespace kodeops\LaravelMysqlDumper;

use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use kodeops\LaravelMysqlDumper\Commands\DumperExportCommand;
use kodeops\LaravelMysqlDumper\Commands\DumperImportCommand;
use kodeops\LaravelMysqlDumper\Commands\DumperCloneCommand;

class LaravelMysqlDumperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mysql-dumper')
            //->hasConfigFile()
            ->hasCommand(DumperExportCommand::class)
            ->hasCommand(DumperImportCommand::class)
            ->hasCommand(DumperCloneCommand::class);
    }
}
