<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
                            {name : Module name (e.g. Companies)}
                            {--version=V1 : API version namespace}
                            {--model= : Domain model name (defaults to module singular)}
                            {--force : Overwrite existing files}';

    protected $description = 'Scaffold a module with clean architecture folders, repository contract/implementation, and provider registration.';

    public function handle(Filesystem $files): int
    {
        $module = Str::studly((string) $this->argument('name'));
        $version = Str::studly((string) $this->option('version'));
        $model = Str::studly((string) ($this->option('model') ?: Str::singular($module)));

        $moduleBasePath = app_path("Modules/{$version}/{$module}");
        $moduleNamespace = "App\\Modules\\{$version}\\{$module}";

        $directories = [
            'Application/UseCases',
            'Domain/Models',
            'Domain/Repositories',
            'Infrastructure/Providers',
            'Infrastructure/Persistence/Repositories',
            'Presentation/Http/Controller',
            'Presentation/Http/Requests',
            'Presentation/Http/Resources',
        ];

        foreach ($directories as $directory) {
            $path = "{$moduleBasePath}/{$directory}";
            if (! $files->isDirectory($path)) {
                $files->makeDirectory($path, 0755, true);
                $this->line("Created directory: {$path}");
            }
        }

        $interfaceName = "{$model}RepositoryInterface";
        $repositoryName = "Eloquent{$model}Repository";
        $providerName = "{$module}ModuleServiceProvider";

        $interfacePath = "{$moduleBasePath}/Domain/Repositories/{$interfaceName}.php";
        $repositoryPath = "{$moduleBasePath}/Infrastructure/Persistence/Repositories/{$repositoryName}.php";
        $providerPath = "{$moduleBasePath}/Infrastructure/Providers/{$providerName}.php";

        $this->writeFile($files, $interfacePath, $this->interfaceStub($moduleNamespace, $interfaceName));
        $this->writeFile($files, $repositoryPath, $this->repositoryStub($moduleNamespace, $repositoryName, $interfaceName));
        $this->writeFile($files, $providerPath, $this->providerStub($moduleNamespace, $providerName, $interfaceName, $repositoryName));

        $providerClass = "\\{$moduleNamespace}\\Infrastructure\\Providers\\{$providerName}::class";
        $this->registerProviderInConfig($files, $providerClass);

        $this->info("Module {$module} ({$version}) scaffolded successfully.");

        return self::SUCCESS;
    }

    protected function writeFile(Filesystem $files, string $path, string $content): void
    {
        if ($files->exists($path) && ! $this->option('force')) {
            $this->warn("Skipped existing file: {$path} (use --force to overwrite)");
            return;
        }

        $files->put($path, $content);
        $this->line("Generated file: {$path}");
    }

    protected function registerProviderInConfig(Filesystem $files, string $providerClass): void
    {
        $configPath = config_path('modules.php');

        if (! $files->exists($configPath)) {
            $content = "<?php\n\nreturn [\n    'providers' => [\n        {$providerClass},\n    ],\n];\n";
            $files->put($configPath, $content);
            $this->line('Created config/modules.php and registered provider.');

            return;
        }

        $config = require $configPath;
        $providers = (array) ($config['providers'] ?? []);

        $providerFqcn = trim($providerClass, '\\:class');
        if (in_array($providerFqcn, $providers, true)) {
            $this->line('Provider already registered in config/modules.php.');
            return;
        }

        $providers[] = $providerFqcn;
        sort($providers);

        $lines = array_map(fn (string $provider) => "        \\{$provider}::class,", $providers);
        $content = "<?php\n\nreturn [\n    'providers' => [\n".implode("\n", $lines)."\n    ],\n];\n";

        $files->put($configPath, $content);
        $this->line('Registered provider in config/modules.php.');
    }

    protected function interfaceStub(string $moduleNamespace, string $interfaceName): string
    {
        return <<<PHP
<?php

namespace {$moduleNamespace}\Domain\Repositories;

interface {$interfaceName}
{
}
PHP;
    }

    protected function repositoryStub(string $moduleNamespace, string $repositoryName, string $interfaceName): string
    {
        return <<<PHP
<?php

namespace {$moduleNamespace}\Infrastructure\Persistence\Repositories;

use {$moduleNamespace}\Domain\Repositories\{$interfaceName};

class {$repositoryName} implements {$interfaceName}
{
}
PHP;
    }

    protected function providerStub(string $moduleNamespace, string $providerName, string $interfaceName, string $repositoryName): string
    {
        return <<<PHP
<?php

namespace {$moduleNamespace}\Infrastructure\Providers;

use {$moduleNamespace}\Domain\Repositories\{$interfaceName};
use {$moduleNamespace}\Infrastructure\Persistence\Repositories\{$repositoryName};
use Illuminate\Support\ServiceProvider;

class {$providerName} extends ServiceProvider
{
    public function register(): void
    {
        \$this->app->bind({$interfaceName}::class, {$repositoryName}::class);
    }
}
PHP;
    }
}
