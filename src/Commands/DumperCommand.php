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
                env('DBI_LOCAL_HOST'),
                env('DBI_LOCAL_PORT'),
                env('DBI_LOCAL_DATABASE'),
                env('DBI_LOCAL_USERNAME'),
                env('DBI_LOCAL_PASSWORD')
            );

            if (! $export) {
                return;
            }

            Artisan::call('migrate:reset', ['--force' => true]);
        }

        $this->export(
            env('DBI_REMOTE_HOST'),
            env('DBI_REMOTE_PORT'),
            env('DBI_REMOTE_DATABASE'),
            env('DBI_REMOTE_USERNAME'),
            env('DBI_REMOTE_PASSWORD')
        );

        $this->comment('Importing to ' . env('DBI_LOCAL_DATABASE') . '@' .  env('DB_HOST'));
        $command = "mysql -u ". env('DBI_LOCAL_USERNAME');
        $command .= " -h " . env('DBI_LOCAL_HOST');
        $command .= " -P " . env('DBI_LOCAL_PORT');
        $command .= " -p " . env('DBI_LOCAL_DATABASE');
        $command .= " -p" . env('DBI_LOCAL_PASSWORD') . " < {$this->export}";

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
            'DBI_REMOTE_HOST', 
            'DBI_REMOTE_PORT', 
            'DBI_REMOTE_DATABASE', 
            'DBI_REMOTE_USERNAME', 
            'DBI_REMOTE_PASSWORD'
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
        $models = File::allFiles(base_path($path));
        foreach ($models as $model) {
            $content = $model->getContents();
            $table = explode('protected $table = \'', $content);
            if (! isset($table[1])) {
                continue;
            }
            
            $table = explode('\'', $table[1])[0];
            if (Schema::connection('mysql-dbi')->hasTable($table)) {
                $this->error($table . ' table already exists!');
                return true;
            }
        }

        return false;
    }
}