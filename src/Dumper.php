<?php
namespace kodeops\LaravelMysqlDumper;

use Ifsnop\Mysqldump\Mysqldump;
use kodeops\LaravelMysqlDumper\Connection;
use kodeops\LaravelMysqlDumper\Exceptions\LaravelMysqlDumperException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File as HttpFile;
use File;
use Schema;
use Exception;
use Illuminate\Support\Facades\DB;

class Dumper
{
    const ENVIRONMENT_PREFIX = "MYSQL_DUMPER";

    protected $runInProduction;
    protected $dump;
    protected $connections;

    public function __construct($runInProduction = false)
    {
        $this->runInProduction = $runInProduction;
        $this->dump = false;
        $this->connections = [];

        if (app()->isProduction() AND ! $this->runInProduction) {
            throw new LaravelMysqlDumperException("Production environment disabled by default");
        }
    }

    private function setupConnection($location)
    {
        if (isset($this->connections[$location])) {
            return $this->connections[$location];
        }

        $env_keys = ['host', 'port', 'database', 'username', 'password'];
        foreach ($env_keys as $key) {
            $env_key = $this->getEnvKey($location, strtoupper($key));
            if (is_null(env($env_key))) {
                throw new LaravelMysqlDumperException("Empty entry “{$key}” in environemnt file for “{$location}” connection ({$env_key})");
            }
        }

        try {
            $connection = new Connection(
                env($this->getEnvKey($location, 'HOST')),
                env($this->getEnvKey($location, 'PORT')),
                env($this->getEnvKey($location, 'DATABASE')),
                env($this->getEnvKey($location, 'USERNAME')),
                env($this->getEnvKey($location, 'PASSWORD')),
            );
        } catch (Exception $e) {
            throw new LaravelMysqlDumperException("Could not set “{$location}” connection: " . $e->getMessage());
        }

        $this->setConnectionDriver($location);

        $this->connections[$location] = $connection;

        return $connection;
    }

    private function getEnvKey($location, $key)
    {
        return self::ENVIRONMENT_PREFIX. "_" . strtoupper($location) . "_" . strtoupper($key);
    }

    private function setConnectionDriver($location)
    {
        $connection = [
            'driver' => 'mysql',
            'host' => env($this->getEnvKey($location, 'HOST')),
            'port' => env($this->getEnvKey($location, 'PORT')),
            'database' => env($this->getEnvKey($location, 'DATABASE')),
            'username' => env($this->getEnvKey($location, 'USERNAME')),
            'password' => env($this->getEnvKey($location, 'PASSWORD')),
            'unix_socket' => env($this->getEnvKey($location, 'SOCKET')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];

        config(["database.connections.mysql-dumper-{$location}" => $connection]);
    }

    public function export($filename = null, $upload = false)
    {
        $connection = $this->setupConnection('source');

        $filename = $filename ?? date('Y-m-d_H-i-s') . "_{$connection->getDatabase()}@{$connection->getHost()}.sql";
        $this->dump = storage_path("laravel-mysql-dumper/{$filename}");

        $dsn = "mysql:";
        $dsn .= "host=" . $connection->getHost() . ";";
        $dsn .= "port=" . $connection->getPort() . ";";
        $dsn .= "dbname=" . $connection->getDatabase();

        (new Mysqldump(
            $dsn,
            $connection->getUser(),
            $connection->getPassword()
        ))->start($this->dump);

        return $this;
    }

    public function import()
    {
        if (! $this->dump) {
            throw new LaravelMysqlDumperException("No dump was generated for import");
        }

        $connection = $this->setupConnection('destination');

        if (! $this->databaseIsEmpty()) {
            throw new LaravelMysqlDumperException("Destination database “{$connection->getDatabase()}” is not empty");
        }

        $command = "mysql -u ". $connection->getUser();
        $command .= " -h " . $connection->getHost();
        $command .= " -P " . $connection->getPort();
        $command .= " -p " . $connection->getDatabase();
        $command .= " -p" . $connection->getPassword() . " < {$this->dump}";
        $command .= " 2>&1";

        shell_exec($command);

        return $this;
    }

    public function upload($destination)
    {
        Storage::cloud()->putFileAs(
            $destination,
            new HttpFile($this->dump),
            pathinfo($this->dump)['basename']
        );
    }

    public function databaseIsEmpty()
    {
        $connection = $this->setupConnection('destination');

        return DB::connection('mysql-dumper-destination')
                 ->table('INFORMATION_SCHEMA.TABLES')
                 ->select(DB::raw('count(*) as tables_count'))
                 ->where('TABLE_SCHEMA', $connection->getDatabase())
                 ->first()
                 ->tables_count == 0;
    }

    public function clone()
    {
        // Check that required settings are available
        $this->setupConnection('source');
        $this->setupConnection('destination');

        $this->export();
        $this->import();
    }

    public function getConnection($location)
    {
        return $this->setupConnection($location);
    }
}
