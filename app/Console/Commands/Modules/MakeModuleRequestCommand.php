<?php

namespace App\Console\Commands\Modules;

use App\Console\Commands\Modules\Concerns\WritesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModuleRequestCommand extends Command
{
    use WritesModuleFiles;

    protected $signature = 'make:module-request
                            {module : Module name e.g. Companies}
                            {name : Request name e.g. StoreCompanyRequest}
                            {--api-version=V1}
                            {--force}';

    protected $description = 'Create a form request inside module presentation layer';

    public function handle(Filesystem $files): int
    {
        $data = $this->moduleData();

        $this->generateModuleFile(
            files: $files,
            directory: "{$data['basePath']}/Presentation/Http/Requests",
            filename: $data['name'],
            stubName: 'request',
            replacements: [
                'namespace' => "{$data['baseNamespace']}\\Presentation\\Http\\Requests",
                'class' => $data['name'],
            ]
        );

        return self::SUCCESS;
    }
}
