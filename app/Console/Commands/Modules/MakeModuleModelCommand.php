<?php

namespace App\Console\Commands\Modules;

use App\Console\Commands\Modules\Concerns\WritesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModuleModelCommand extends Command
{
    use WritesModuleFiles;

    protected $signature = 'make:module-model
                            {module : Module name e.g. Companies}
                            {name : Model name e.g. Company}
                            {--api-version=V1}
                            {--force}';

    protected $description = 'Create a model inside module domain layer';

    public function handle(Filesystem $files): int
    {
        $data = $this->moduleData();

        $this->generateModuleFile(
            files: $files,
            directory: "{$data['basePath']}/Domain/Models",
            filename: $data['name'],
            stubName: 'model',
            replacements: [
                'namespace' => "{$data['baseNamespace']}\\Domain\\Models",
                'class' => $data['name'],
            ]
        );

        return self::SUCCESS;
    }
}
