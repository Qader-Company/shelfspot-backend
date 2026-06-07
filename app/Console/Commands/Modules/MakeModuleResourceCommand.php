<?php

namespace App\Console\Commands\Modules;

use App\Console\Commands\Modules\Concerns\WritesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModuleResourceCommand extends Command
{
    use WritesModuleFiles;

    protected $signature = 'make:module-resource
                            {module : Module name e.g. Companies}
                            {name : Resource name e.g. CompanyResource}
                            {--api-version=V1}
                            {--force}';

    protected $description = 'Create a JSON resource inside module presentation layer';

    public function handle(Filesystem $files): int
    {
        $data = $this->moduleData();

        $this->generateModuleFile(
            files: $files,
            directory: "{$data['basePath']}/Presentation/Http/Resources",
            filename: $data['name'],
            stubName: 'resource',
            replacements: [
                'namespace' => "{$data['baseNamespace']}\\Presentation\\Http\\Resources",
                'class' => $data['name'],
            ]
        );

        return self::SUCCESS;
    }
}
