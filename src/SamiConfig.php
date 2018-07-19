<?php

namespace Riimu\SamiConfig;

use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Sami\Version\Version;
use Sami\Version\VersionCollection;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * SamiConfig.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class SamiConfig
{
    /** @var string */
    private $rootDirectory = '';

    public function buildConfig(string $rootDirectory = null): Sami
    {
        $this->rootDirectory = rtrim($rootDirectory ?? $this->detectRootDirectory(), '/');

        $iterator = $this->getSourceIterator();
        $themeSettings = $this->getThemeSettings();
        $title = $this->getTitle();
        $versions = $this->getVersionCollection();
        $remoteUrl = $this->getRemoteUrl();

        $buildDir = $this->rootDirectory . '/build/doc';
        $cacheDir = $this->rootDirectory . '/build/cache';

        $this->clearDirectories([$buildDir, $cacheDir]);

        return new Sami($iterator, $themeSettings + [
            'title' => $title,
            'versions' => $versions,
            'build_dir' => $buildDir,
            'cache_dir' => $cacheDir,
            'remote_repository' => new GitHubRemoteRepository($remoteUrl, $this->rootDirectory),
            'default_opened_level' => 2,
        ]);
    }

    private function detectRootDirectory(): string
    {
        foreach (debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {
            $file = $trace['file'] ?? '';

            if (basename($file) === 'sami_config.php') {
                return \dirname($file);
            }
        }

        throw new \RuntimeException('The configuration must be included via a sami_config.php file');
    }

    private function getSourceIterator(): \Traversable
    {
        return Finder::create()
            ->files()
            ->name('*.php')
            ->in($this->rootDirectory . '/src');
    }

    private function getThemeSettings(): array
    {
        $theme = getenv('SAMI_THEME');
        $settings = [];

        if ($theme) {
            $settings['theme'] = basename($theme);
            $settings['template_dirs'] = [\dirname($theme)];
        }

        return $settings;
    }

    private function getTitle(): string
    {
        $readme = file_get_contents($this->rootDirectory . '/README.md');

        if (!preg_match('/^#([^#\r\n]++)#?(\R|$)/', $readme, $match)) {
            throw new \RuntimeException('Could not parse a title from the README.md');
        }

        return sprintf('%s API', trim($match[1]));
    }

    private function getVersionCollection(): VersionCollection
    {
        $collection = new class($this->rootDirectory) extends GitVersionCollection {
            /** @var bool */
            private $revert = false;

            public function valid(): bool
            {
                if (parent::valid()) {
                    $this->revert = true;
                    return true;
                }

                if ($this->revert) {
                    $this->revert = false;
                    $this->switchVersion(new Version('-'));
                }

                return false;
            }
        };

        $collection->add($this->getLatestStableVersion());

        return $collection;
    }

    private function getLatestStableVersion(): string
    {
        $process = new Process('git tag', $this->rootDirectory);
        $process->mustRun();

        $tags = [];

        foreach (preg_split('/\R/', $process->getOutput()) as $tag) {
            if (preg_match('/^v?\d+\.\d+\.\d+$/', $tag)) {
                $tags[] = $tag;
            }
        }

        if (empty($tags)) {
            throw new \RuntimeException('No stable versions exist to create documentation');
        }

        usort($tags, function (string $a, string $b): int {
            return version_compare($a, $b);
        });

        return array_pop($tags);
    }

    private function getRemoteUrl(): string
    {
        $process = new Process('git remote get-url origin', $this->rootDirectory);
        $process->mustRun();

        $url = trim($process->getOutput());

        if (!preg_match('#^https://github.com/([^/.]++/[^/.]++)\.git#', $url, $match)) {
            throw new \RuntimeException("The remote url '$url' for origin is not a valid github url");
        }

        return $match[1];
    }

    /**
     * @param string[] $paths
     */
    private function clearDirectories(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                if (!is_dir($path)) {
                    throw new \RuntimeException("The file path '$path' is not a directory");
                }

                $process = new Process(['rm', '-rf', $path], $this->rootDirectory);
                $process->mustRun();
            }
        }
    }
}
