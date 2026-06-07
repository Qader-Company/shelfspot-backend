<?php

namespace App\Console\Commands\Modules;

use App\Console\Commands\Modules\Concerns\WritesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModuleUseCaseCommand extends Command
{
    use WritesModuleFiles;

    protected $signature = 'make:module-usecase
                            {module : Module name e.g. Companies}
                            {name : UseCase name e.g. CreateCompanyUseCase}
                            {--api-version=V1}
                            {--force}';

    protected $description = 'Create a use case inside module application layer';

    public function handle(Filesystem $files): int
    {
        $data = $this->moduleData();

        $this->generateModuleFile(
            files: $files,
            directory: "{$data['basePath']}/Application/UseCases",
            filename: $data['name'],
            stubName: 'usecase',
            replacements: [
                'namespace' => "{$data['baseNamespace']}\\Application\\UseCases",
                'class' => $data['name'],
            ]
        );

        return self::SUCCESS;
    }
}
