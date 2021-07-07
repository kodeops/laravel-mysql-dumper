<?php
namespace kodeops\LaravelMysqlDumper;

use Ifsnop\Mysqldump;
use kodeops\LaravelMysqlDumper\Connection;
use kodeops\LaravelMysqlDumper\Exceptions\LaravelMysqlDumperException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File as HttpFile;
use File;
use Schema;

class Dumper
{
    protected $connections;
    protected $runInProduction;

    public function __construct($runInProduction = false)
    {
        $this->runInProduction = $runInProduction;

        if (app()->isProduction() AND ! $this->runInProduction) {
            throw new LaravelMysqlDumperException("Production environment disabled by default");
        }

        $this->connections['source'] = new Connection(
            env('MYSQL_DUMPER_DESTINATION_HOST'),
            env('MYSQL_DUMPER_DESTINATION_PORT'),
            env('MYSQL_DUMPER_DESTINATION_DATABASE'),
            env('MYSQL_DUMPER_DESTINATION_USERNAME'),
            env('MYSQL_DUMPER_DESTINATION_PASSWORD'),
        );
        $this->connections['destination'] = new Connection(
            env('MYSQL_DUMPER_DESTINATION_HOST'),
            env('MYSQL_DUMPER_DESTINATION_PORT'),
            env('MYSQL_DUMPER_DESTINATION_DATABASE'),
            env('MYSQL_DUMPER_DESTINATION_USERNAME'),
            env('MYSQL_DUMPER_DESTINATION_PASSWORD'),
        );
    }

    public function export($filename = null, $upload = false)
    {
        $filename = $filename ?? date('Y-m-d_H-i-s') . "_{$database}@{$host}.sql";
        $path = storage_path("laravel-mysql-dumper/{$filename}");

        $connection = "mysql:";
        $connection .= "host={$this->connections['source']->getHost()};";
        $connection .= "port={$this->connections['source']->getPort()};";
        $connection .= "dbname={$this->connections['source']->getDatabase()}";

        (new Mysqldump(
            $connection,
            $this->connections['source']->getUser(),
            $this->connections['source']->getPassword()
        ))->start($path);

        return $path;
    }

    public function import($dump)
    {
        $command = "mysql -u ". $this->connections['destination']->getUser();
        $command .= " -h " . $this->connections['destination']->getHost();
        $command .= " -P " . $this->connections['destination']->getPort();
        $command .= " -p " . $this->connections['destination']->getDatabase();
        $command .= " -p" . $this->connections['destination']->getPassword() . " < {$dump}";

        shell_exec($command);
    }

    public function upload($dump, $destination)
    {
        Storage::cloud()->putFileAs(
            $destination,
            new HttpFile($dump),
            pathinfo($dump)['basename']
        );
    }

    public function checkIfDestionationExists($path)
    {
        if (! config('database.connections.mysql-dumper-destination')) {
            throw new LaravelMysqlDumperException("mysql-dumper database connection not found");
        }

        $models = File::allFiles(base_path($path));
        foreach ($models as $model) {
            $content = $model->getContents();
            $table = explode('protected $table = \'', $content);
            if (! isset($table[1])) {
                continue;
            }

            $table = explode('\'', $table[1])[0];
            if (Schema::connection('mysql-dumper')->hasTable($table)) {
                throw new LaravelMysqlDumperException("{$table} table already exists");
                return true;
            }
        }

        return false;
    }
}
