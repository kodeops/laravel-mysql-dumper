```
 _     _  _____  ______  _______  _____   _____  _______
 |____/  |     | |     \ |______ |     | |_____] |______
 |    \_ |_____| |_____/ |______ |_____| |       ______|
 
```
 

# kodeops/laravel-mysql-dumper
Dump and restore the contents of a MySQL database.

## Install

### Add composer dependency

`composer require kodeops/laravel-mysql-dumper`

### Add database connection settings to the environment file

The `SOURCE` prefix indicates the database settings where the dump will be generated from:

```
MYSQL_DUMPER_DESTINATION_HOST=
MYSQL_DUMPER_DESTINATION_PORT=
MYSQL_DUMPER_DESTINATION_DATABASE=
MYSQL_DUMPER_DESTINATION_USERNAME=
MYSQL_DUMPER_DESTINATION_PASSWORD=
```

The `DESTINATION` prefix indicates the database settings where the dump will be imported:

```
MYSQL_DUMPER_SOURCE_HOST=
MYSQL_DUMPER_SOURCE_PORT=
MYSQL_DUMPER_SOURCE_DATABASE=
MYSQL_DUMPER_SOURCE_USERNAME=
MYSQL_DUMPER_SOURCE_PASSWORD=
```

Example:

```
MYSQL_DUMPER_DESTINATION_HOST=127.0.0.1
MYSQL_DUMPER_DESTINATION_PORT=3306
MYSQL_DUMPER_DESTINATION_DATABASE=destination-database
MYSQL_DUMPER_DESTINATION_USERNAME=root
MYSQL_DUMPER_DESTINATION_PASSWORD=secret

MYSQL_DUMPER_SOURCE_HOST=127.0.0.1
MYSQL_DUMPER_SOURCE_PORT=3306
MYSQL_DUMPER_SOURCE_DATABASE=source-database
MYSQL_DUMPER_SOURCE_USERNAME=root
MYSQL_DUMPER_SOURCE_PASSWORD=secret
```

### Add database connection (required only for importing the dump)

Add the `mysql-dumper` connection to `database.php` config file:

```
'connections' => [
    
    ...
    
    'mysql-dumper-destination' => [
        'driver' => 'mysql',
        'host' => env('MYSQL_DUMPER_DESTINATION_HOST', '127.0.0.1'),
        'port' => env('MYSQL_DUMPER_DESTINATION_PORT', '3306'),
        'database' => env('MYSQL_DUMPER_DESTINATION_DATABASE', 'forge'),
        'username' => env('MYSQL_DUMPER_DESTINATION_USERNAME', 'homestead'),
        'password' => env('MYSQL_DUMPER_DESTINATION_PASSWORD', 'secret'),
        'unix_socket' => env('DB_DUMP_DESTINATION_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
],
```

## Using the command

`php artisan mysql-dumper:run {model?}`
