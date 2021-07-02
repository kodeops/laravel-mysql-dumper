<?php
namespace kodeops\LaravelMysqlDumper\Commands;

use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use kodeops\LaravelMysqlDumper\Commands\Dumper;

class LaravelMysqlDumperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mysql-dumper')
            ->hasConfigFile()
            ->hasCommand(Dumper::class);
    }
}