<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class mkbyf extends Command
{
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
            'resources',
            'routes',
            'tests',
            // 'vendor',
            'artisan',
            'composer.json',
            'composer.lock',
            'index.php',
        ];

        if($this->option('first')){
            $copyDirs[] = 'bootstrap';
            $copyDirs[] = 'public';
            $copyDirs[] = 'vendor';
            $copyDirs[] = 'DB';
            $copyDirs[] = 'lang';
            $copyDirs[] = 'database';
            $copyDirs[] = 'Documentation';
            $copyDirs[] = 'storage';
        }
        $destFolder = base_path();
        $to = (str($destFolder)->beforeLast(DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.(str($destFolder)->afterLast(DIRECTORY_SEPARATOR).'-buyer'));
        try {
            $this->info('Creating buyer file in '.$to);
            if(!File::isDirectory($to)){
                File::makeDirectory($to);
            }
        } catch (\Throwable $th) {
            $this->error('Failed to create directory: '.$to);
            return;
        }

        foreach($copyDirs as $dir){
            $this->info('Copying '.$dir.' to '.$to);
            try {
                if(File::isDirectory(base_path($dir))){
                    File::copyDirectory(base_path($dir), $to.'/'.$dir);
                }else{
                    File::copy(base_path($dir), $to.'/'.$dir);
                }
                $this->info('Copied '.$dir.' to '.$to);
            } catch (\Throwable $th) {
                $this->error('Failed to copy '.$dir.' to '.$to);
                $this->error($th->getMessage());
            }
        }
    }
}
