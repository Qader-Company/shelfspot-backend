<?php

namespace App\Console\Commands\Modules;

use App\Console\Commands\Modules\Concerns\WritesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    use WritesModuleFiles;

    protected $signature = 'make:module
                        {module : Module name (e.g. Companies)}
                        {--api-version=V1 : API version namespace}
                        {--model= : Domain model name (defaults to module singular)}
                        {--m : Generate domain model}
                        {--c : Generate presentation controller}
                        {--force : Overwrite existing files}';

    protected $description = 'Scaffold a module with clean architecture folders, repository contract/implementation, and provider registration.';

    public function handle(Filesystem $files): int
    {
        $module = Str::studly((string) $this->argument('module'));
        $version = Str::studly((string) $this->option('api-version'));
        $model = Str::studly((string) ($this->option('model') ?: Str::singular($module)));

        $moduleBasePath = app_path("Modules/{$version}/{$module}");
        $moduleNamespace = "App\\Modules\\{$version}\\{$module}";

        $directories = [
            'Application/UseCases',
            'Domain/Models',
            'Domain/Repositories',
            'Infrastructure/Providers',
            'Infrastructure/Persistence/Repositories',
            'Presentation/Http/Controllers',
            'Presentation/Http/Requests',
            'Presentation/Http/Resources',
        ];

        foreach ($directories as $directory) {
            $this->ensureDirectory($files, "{$moduleBasePath}/{$directory}");
        }

        $interfaceName = "{$model}RepositoryInterface";
        $repositoryName = "Eloquent{$model}Repository";
        $providerName = "{$module}ModuleServiceProvider";

        $this->generateModuleFile(
            files: $files,
            directory: "{$moduleBasePath}/Domain/Repositories",
            filename: $interfaceName,
            stubName: 'repository-interface',
            replacements: [
                'namespace' => "{$moduleNamespace}\\Domain\\Repositories",
                'model_namespace' => "{$moduleNamespace}\\Domain\\Models",
                'model' => $model,
                'model_variable' => Str::camel($model),
                'interface' => $interfaceName,
            ]
        );

        $this->generateModuleFile(
            files: $files,
            directory: "{$moduleBasePath}/Infrastructure/Persistence/Repositories",
            filename: $repositoryName,
            stubName: 'repository',
            replacements: [
                'namespace' => "{$moduleNamespace}\\Infrastructure\\Persistence\\Repositories",
                'model_namespace' => "{$moduleNamespace}\\Domain\\Models",
                'interface_namespace' => "{$moduleNamespace}\\Domain\\Repositories",
                'model' => $model,
                'model_variable' => Str::camel($model),
                'interface' => $interfaceName,
                'class' => $repositoryName,
            ]
        );

        $this->generateModuleFile(
            files: $files,
            directory: "{$moduleBasePath}/Infrastructure/Providers",
            filename: $providerName,
            stubName: 'provider',
            replacements: [
                'namespace' => "{$moduleNamespace}\\Infrastructure\\Providers",
                'interface_namespace' => "{$moduleNamespace}\\Domain\\Repositories",
                'repository_namespace' => "{$moduleNamespace}\\Infrastructure\\Persistence\\Repositories",
                'interface' => $interfaceName,
                'repository' => $repositoryName,
                'class' => $providerName,
            ]
        );

        if ($this->option('m')) {
            $this->generateModuleFile(
                files: $files,
                directory: "{$moduleBasePath}/Domain/Models",
                filename: $model,
                stubName: 'model',
                replacements: [
                    'namespace' => "{$moduleNamespace}\\Domain\\Models",
                    'class' => $model,
                ]
            );
        }

        if ($this->option('c')) {
            $controllerName = "{$model}Controller";

            $this->generateModuleFile(
                files: $files,
                directory: "{$moduleBasePath}/Presentation/Http/Controllers",
                filename: $controllerName,
                stubName: 'controller',
                replacements: [
                    'namespace' => "{$moduleNamespace}\\Presentation\\Http\\Controllers",
                    'class' => $controllerName,
                ]
            );
        }

        $providerClass = "\\{$moduleNamespace}\\Infrastructure\\Providers\\{$providerName}::class";

        $this->registerProviderInConfig($files, $providerClass);

        $this->info("Module {$module} ({$version}) scaffolded successfully.");

        return self::SUCCESS;
    }

    protected function registerProviderInConfig(Filesystem $files, string $providerClass): void
    {
        $configPath = config_path('modules.php');

        if (! $files->exists($configPath)) {
            $content = <<<PHP
<?php

return [

    'providers' => [
        {$providerClass},
    ],
];

PHP;

            $files->put($configPath, $content);

            $this->line('Created config/modules.php and registered provider.');

            return;
        }

        $content = $files->get($configPath);

        preg_match("/'providers'\s*=>\s*\[(.*?)\]/s", $content, $matches);

        if (! isset($matches[1])) {
            $this->error('Could not find providers array.');

            return;
        }

        $providersBlock = $matches[1];

        $providerFqcn = trim($providerClass, '\\');
        $providerCheck = $providerFqcn . '::class';

        if (str_contains($providersBlock, $providerCheck)) {
            $this->line('Provider already registered in config/modules.php.');

            return;
        }

        $newProvidersBlock = rtrim($providersBlock) . "\n        {$providerClass},";

        $updatedContent = str_replace(
            $providersBlock,
            $newProvidersBlock,
            $content
        );

        $files->put($configPath, $updatedContent);

        $this->line('Registered provider in config/modules.php.');
    }
}
