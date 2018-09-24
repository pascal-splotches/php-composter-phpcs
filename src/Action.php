<?php

namespace PHPComposter\PHPComposter\PHPCS;

use Eloquent\Pathogen\Exception\InvalidPathStateException;
use Eloquent\Pathogen\FileSystem\PlatformFileSystemPath;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\DeepExitException;
use PHP_CodeSniffer\Runner;
use PHPComposter\PHPComposter\BaseAction;
use ReflectionObject;

/**
 * Class CodeSniffer
 *
 * @since 0.1.0
 *
 * @package PHPComposter\PHPComposter\PHPCS
 *
 * @author Pascal Scheepers <pascal@splotch.es>
 */
class Action extends BaseAction
{
    const EXIT_NO_ERRORS = 0;
    const EXIT_ERRORS_FOUND = 1;
    const EXIT_EXCEPTION = 2;

    /**
     * Verify whether staged files adhere to the standards defined in phpcs.xml
     *
     * @since 0.1.0
     */
    public function sniffStagedFiles()
    {
        try {
            $numberOfErrors = $this->runPhpCodeSniffer();

            if ($numberOfErrors === 0) {
                $this->success('PHP Code Sniffer found no errors! Good Job!', self::EXIT_NO_ERRORS);
            } else {
                $this->error('PHP Code Sniffer found errors! Aborting Commit.', self::EXIT_ERRORS_FOUND);
            }
        } catch (DeepExitException $e) {
            $this->error('An error occurred whilst running PHP Code Sniffer: ' . $e->getMessage(),
                self::EXIT_EXCEPTION);
        }
    }

    /**
     * Run The PHP CodeSniffer for only the staged files that are in configured locations.
     *
     * @since 0.1.0
     *
     * @return integer
     * @throws DeepExitException
     */
    protected function runPhpCodeSniffer()
    {
        $runner = new Runner();
        $reflector = new ReflectionObject($runner);

        $runner->config = new Config();

        $runner->init();

        $runner->config->interactive = false;
        $runner->config->cache = false;

        $runner->config->files = $this->getMatchingFiles($runner->config->files, $this->getStagedFiles());

        $runMethod = $reflector->getMethod('run');

        $runMethod->setAccessible(true);
        $numberOfErrors = (int)$runMethod->invoke($runner);

        return $numberOfErrors;
    }

    /**
     * Only return the files that match the pre-configured paths.
     *
     * @since 0.1.0
     *
     * @param string[] $configuredPaths
     * @param string[] $stagedFiles
     *
     * @return string[]
     */
    protected function getMatchingFiles(array $configuredPaths = [], array $stagedFiles = [])
    {
        $matchingFiles = [];

        foreach ($configuredPaths as $configuredPath) {
            try {
                $configuredPath = PlatformFileSystemPath::fromString($configuredPath)->toAbsolute();
            } catch (InvalidPathStateException $e) {
                $this->error('Unable to parse configured path: ' . $configuredPath, false);
                continue;
            }

            foreach ($stagedFiles as $stagedFile) {
                try {
                    $stagedFile = PlatformFileSystemPath::fromString($stagedFile)->toAbsolute();
                } catch (InvalidPathStateException $e) {
                    $this->error('Unable to parse staged file: ' . $stagedFile, false);
                    continue;
                }

                if ($configuredPath->isAncestorOf($stagedFile)) {
                    array_push($matchingFiles, $stagedFile->string());
                }
            }
        }

        return $matchingFiles;
    }
}
