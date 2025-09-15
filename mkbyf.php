<?php

namespace App\Console\Commands;

use App\Traits\ImageUpload;
use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Jackiedo\DotenvEditor\DotenvEditor;
use Process;

class mkbyf extends Command
{
    use ImageUpload;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mkbyf {--first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Buyer File, (--first) if first time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $copyDirs = [
            'app',
            'config',
            // 'public',
            'DB',
            'resources',
            'routes',
            // 'tests',
            // 'vendor',
            'artisan',
            'composer.json',
            'composer.lock',
            'index.php',
        ];

        if ($this->option('first')) {
            $copyDirs[] = 'bootstrap';
            $copyDirs[] = 'assets';
            $copyDirs[] = 'vendor';
            // $copyDirs[] = 'DB';
            $copyDirs[] = 'lang';
            $copyDirs[] = 'database';
            $copyDirs[] = 'Documentation';
            $copyDirs[] = 'storage';
            $copyDirs[] = '.env';
            $copyDirs[] = '.htaccess';
            $copyDirs[] = 'modules';
        }
        $destFolder = base_path();
        $PROJECT_NAME = str($destFolder)->beforeLast(DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . (str($destFolder)->afterLast(DIRECTORY_SEPARATOR));

        $to = ($PROJECT_NAME . '-buyer');


        if ($this->option('first')) {
            // clear the directory before
            File::cleanDirectory($to);
        }

        try {
            $this->info('Creating buyer file in ' . $to);
            if (!File::isDirectory($to)) {
                File::makeDirectory($to);
            }
        } catch (\Throwable $th) {
            $this->error('Failed to create directory: ' . $to);

            return;
        }

        foreach ($copyDirs as $dir) {
            $this->info('Copying ' . $dir . ' to ' . $to);
            try {
                if (File::isDirectory(base_path($dir))) {
                    File::copyDirectory(base_path($dir), $to . '/' . $dir);
                } else {
                    File::copy(base_path($dir), $to . '/' . $dir);
                }
                $this->info('Copied ' . $dir . ' to ' . $to);
            } catch (\Throwable $th) {
                $this->error('Failed to copy ' . $dir . ' to ' . $to);
                $this->error($th->getMessage());
            }
        }

        if ($this->option('first')) {
            Process::path($to)->run('composer update --no-dev');
        }

        $imageAssetFolder = ($to . '/assets/global/images');

        $buyerSql = $to . '/DB/gamkon-buyer.sql';

        $sqlContent = File::get($buyerSql);

        foreach (File::allFiles($imageAssetFolder) as $key => $file) {

            if (!str($sqlContent)->contains($file->getFilename())) {
                File::delete($file->getRealPath());
                $this->info('Deleted ' . $file->getFilename() . ' from ' . $imageAssetFolder);
            }
        }
        $this->info('Cleanup of image assets completed.');

        // clear storage files
        $storagePath = $to . '/storage';
        $storageFiles = File::allFiles($storagePath, true);

        foreach ($storageFiles as $file) {
            if (
                str($file->getFilename())->contains(['index.php', '.gitignore', 'README.md'], true)

            ) {
                $this->info('Skipping ' . $file->getFilename() . ' in ' . $storagePath);

                continue;
            }
            File::delete($file->getRealPath());
            $this->info('Deleted ' . $file->getFilename() . ' from ' . $storagePath);
        }

        $this->info('Storage cleanup completed.');



        // dot env editor
        app(DotenvEditor::class)->load($to . '/.env')->deleteKeys(['LICENSE_KEY','APP_DEMO'])->save();

        // delete admin login credentials

        $adminLogin = File::get($to . '/resources/views/backend/auth/login.blade.php');
        $adminLogin = str($adminLogin)->replaceMatches('/value=".*?"/', 'value=""');
        File::put($to . '/resources/views/backend/auth/login.blade.php', $adminLogin);
        $this->info('Cleared admin login credentials from login view.');

        // delete main sql and make buyer sql to 

        $projectName = basename($PROJECT_NAME);

        if (File::exists($to . "/DB/$projectName.sql") && File::exists($to . "/DB/$projectName-buyer.sql")) {
            File::delete($to . '/DB/' . $projectName . '.sql');
            $this->info('Deleted main SQL file: ' . $projectName . '.sql');

            rename($to . "/DB/$projectName-buyer.sql", $to . "/DB/$projectName.sql");
        }


        // delete system commands
        $systemCommands = [
            'mkbyf',
            'CopyAssets',
        ];

        foreach ($systemCommands as $command) {
            $commandFile = $to . '/app/Console/Commands/' . $command . '.php';
            if (File::exists($commandFile)) {
                File::delete($commandFile);
                $this->info('Deleted ' . $commandFile);
            } else {
                $this->info('Command file ' . $commandFile . ' does not exist.');
            }
        }

        // clear cache
        Process::path($to)->run('php artisan optimize:clear');
        $this->info('Buyer file creation completed successfully.');
    }
}
