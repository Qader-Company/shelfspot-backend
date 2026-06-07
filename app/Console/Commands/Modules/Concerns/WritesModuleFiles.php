<?php

namespace App\Console\Commands\Modules\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

trait WritesModuleFiles
{
    protected function moduleData(): array
    {
        $module = Str::studly((string) $this->argument('module'));
        $name = Str::studly((string) $this->argument('name'));
        $version = Str::studly((string) ($this->option('api-version') ?: 'V1'));

        return [
            'module' => $module,
            'name' => $name,
            'version' => $version,
            'basePath' => app_path("Modules/{$version}/{$module}"),
            'baseNamespace' => "App\\Modules\\{$version}\\{$module}",
        ];
    }

    protected function generateModuleFile(
        Filesystem $files,
        string $directory,
        string $filename,
        string $stubName,
        array $replacements = []
    ): bool {
        $this->ensureDirectory($files, $directory);

        $stub = $this->getStub($stubName);

        $content = $this->replaceStub($stub, $replacements);

        return $this->writeFile(
            files: $files,
            path: "{$directory}/{$filename}.php",
            content: $content
        );
    }

    protected function getStub(string $stubName): string
    {
        $path = base_path("stubs/modules/{$stubName}.stub");

        if (! file_exists($path)) {
            throw new \RuntimeException("Stub file not found: {$path}");
        }

        return file_get_contents($path);
    }

    protected function replaceStub(string $stub, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $stub = str_replace(
                ['{{ '.$key.' }}', '{{'.$key.'}}'],
                $value,
                $stub
            );
        }

        return $stub;
    }

    protected function ensureDirectory(Filesystem $files, string $directory): void
    {
        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }
    }

    protected function writeFile(Filesystem $files, string $path, string $content): bool
    {
        if ($files->exists($path) && ! $this->option('force')) {
            $this->warn("Skipped existing file: {$path}");

            return false;
        }

        $files->put($path, $content);

        $this->info("Generated: {$path}");

        return true;
    }
}
