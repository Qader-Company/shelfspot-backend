<?php

namespace App\Console\Commands\Modules;

use App\Console\Commands\Modules\Concerns\WritesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModuleControllerCommand extends Command
{
    use WritesModuleFiles;

    protected $signature = 'make:module-controller
                            {module : Module name e.g. Companies}
                            {name : Controller name e.g. CompanyController}
                            {--api-version=V1}
                            {--force}';

    protected $description = 'Create a controller inside module presentation layer.';

    public function handle(Filesystem $files): int
    {
        $data = $this->moduleData();

        $this->generateModuleFile(
            files: $files,
            directory: "{$data['basePath']}/Presentation/Http/Controllers",
            filename: $data['name'],
            stubName: 'controller',
            replacements: [
                'namespace' => "{$data['baseNamespace']}\\Presentation\\Http\\Controllers",
                'class' => $data['name'],
            ]
        );

        return self::SUCCESS;
    }
}
