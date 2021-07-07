<?php
namespace kodeops\LaravelMysqlDumper\Commands;

use Illuminate\Console\Command;
use File;
use DB;
use Schema;
use Exception;
use App;
use Artisan;
use Ifsnop\Mysqldump as IMysqldump;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\Storage;
use kodeops\LaravelMysqlDumper\Exceptions\LaravelMysqlDumperException;

class DumperCommand extends Command
{
    protected $signature = 'mysql-dumper:run {model?}';
    protected $description = 'Import production database';

    public function handle()
    {
        if (! $this->checkSettings()) {
            return;
        }

        if ($this->argument('model') AND $this->checkIfDestionationExists($this->argument('model'))) {
            if (! $this->confirm('Local tables exists, do you want to drop them?')) {
                $this->error('Process stopped, tables already exists.');
                return;
            }

            // If local database exist, create a backup
            $export = $this->export(
                env('MYSQL_DUMPER_DESTINATION_HOST'),
                env('MYSQL_DUMPER_DESTINATION_PORT'),
                env('MYSQL_DUMPER_DESTINATION_DATABASE'),
                env('MYSQL_DUMPER_DESTINATION_USERNAME'),
                env('MYSQL_DUMPER_DESTINATION_PASSWORD')
            );

            if (! $export) {
                return;
            }

            Artisan::call('migrate:reset', ['--force' => true]);
        }

        $this->export(
            env('MYSQL_DUMPER_SOURCE_HOST'),
            env('MYSQL_DUMPER_SOURCE_PORT'),
            env('MYSQL_DUMPER_SOURCE_DATABASE'),
            env('MYSQL_DUMPER_SOURCE_USERNAME'),
            env('MYSQL_DUMPER_SOURCE_PASSWORD')
        );

        $this->comment('Importing to ' . env('MYSQL_DUMPER_DESTINATION_DATABASE') . '@' .  env('MYSQL_DUMPER_DESTINATION_HOST'));
        $command = "mysql -u ". env('MYSQL_DUMPER_DESTINATION_USERNAME');
        $command .= " -h " . env('MYSQL_DUMPER_DESTINATION_HOST');
        $command .= " -P " . env('MYSQL_DUMPER_DESTINATION_PORT');
        $command .= " -p " . env('MYSQL_DUMPER_DESTINATION_DATABASE');
        $command .= " -p" . env('MYSQL_DUMPER_DESTINATION_PASSWORD') . " < {$this->export}";

        $this->info($command);

        shell_exec($command);
        
        $this->info('Imported!');

        $this->uploadToClouStorage();

        File::delete($this->export);
    }

    private function checkSettings()
    {
        if (App::environment('production')) {
            $this->error('Command not available in production.');
            return false;
        }

        $path = storage_path('dbi');
        if (! File::exists($path)) {
            File::makeDirectory($path);
        }

        $valid_settings = true;
        $settings = [
            'MYSQL_DUMPER_SOURCE_HOST',
            'MYSQL_DUMPER_SOURCE_PORT',
            'MYSQL_DUMPER_SOURCE_DATABASE',
            'MYSQL_DUMPER_SOURCE_USERNAME',
            'MYSQL_DUMPER_SOURCE_PASSWORD'
        ];
        foreach ($settings as $setting) {
            if (is_null(env($setting))) {
                $this->error('Empty ' . $setting);
                $valid_settings = false;
            }
        }

        return $valid_settings;
    }

    private function uploadToClouStorage()
    {
        $this->comment('Uploading to cloud storage...');
        $cloud_destination = 'backup/mysql/' . date('Y') . '/'. date('m');
        Storage::cloud()->putFileAs(
            $cloud_destination, 
            new HttpFile($this->export), 
            $this->filename
        );
        $this->info("Uploaded to cloud storage ({$cloud_destination})!");
    }

    private function export($host, $port, $database, $user, $password)
    {
        $this->filename = date('Y-m-d_H-i-s') . "_{$database}@{$host}.sql";
        $this->export = storage_path("dbi/{$this->filename}");

        $this->comment("Exporting to {$database} to {$this->export}...");

        (new IMysqldump\Mysqldump(
            "mysql:host={$host};port={$port};dbname={$database}",
            $user, 
            $password
        ))->start($this->export);

        $this->info('Exported!');

        return $this->export;
    }

    private function checkIfDestionationExists($path)
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
                $this->error($table . ' table already exists!');
                return true;
            }
        }

        return false;
    }
}
