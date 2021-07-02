<?php
namespace kodeops\LaravelMysqlDumper;

use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use kodeops\LaravelMysqlDumper\Commands\DumperCommand;

class LaravelMysqlDumperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mysql-dumper')
            //->hasConfigFile()
            ->hasCommand(DumperCommand::class);
    }
}