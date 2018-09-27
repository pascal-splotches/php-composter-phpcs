<?php

namespace PHPComposter\PHPComposter\PHPCS;

use Eloquent\Pathogen\FileSystem\FileSystemPath;
use Exception;
use PHPComposter\PHPComposter\BaseAction;
use RuntimeException;
use Symfony\Component\Process\Process;

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
    const EXIT_ERRORS_FOUND = 1;
    const EXIT_WITH_EXCEPTION = 2;

    const OS_WINDOWS = 'Windows';
    const OS_BSD = 'BSD';
    const OS_DARWIN = 'Darwin';
    const OS_SOLARIS = 'Solaris';
    const OS_LINUX = 'Linux';
    const OS_UNKNOWN = 'Unknown';

    /**
     * Verify whether files adhere to the standards defined in phpcs.xml
     *
     * @since 0.1.0
     */
    public function runPhpCs()
    {
        try {
            $this->checkPhpCsConfiguration();

            $process = new Process([$this->getPhpCsPath()]);
            $process->run();

            $this->write($process->getOutput());

            if (!$process->isSuccessful()) {
                $this->success('PHPCS detected no errors, allowing commit to proceed.');
            }

            $this->error('PHPCS detected errors, aborting commit!', self::EXIT_ERRORS_FOUND);
        } catch (Exception $e) {
            $this->error('An error occurred trying to run PHPCS: ' . PHP_EOL . $e->getMessage(), self::EXIT_WITH_EXCEPTION);
        }
    }

    /**
     * Build the path to the PHPCS binary
     *
     * @return string
     */
    protected function getPhpCsPath()
    {
        $root = FileSystemPath::fromString($this->root);

        $phpCsPath = $root->joinAtomSequence(
            [
                "vendor",
                "bin",
                $this->getPhpCsBinary(),
            ]
        );

        return $phpCsPath->string();
    }

    /**
     * Build the path to the PHPCS configuration
     *
     * @return string
     */
    protected function getPhpCsConfigurationPath()
    {
        $root = FileSystempath::fromString($this->root);

        $phpCsConfigurationPath = $root->joinAtomSequence(
            [
                "phpcs.xml",
            ]
        );

        return $phpCsConfigurationPath->string();
    }

    /**
     * Return the correct binary for the current OS
     *
     * @return string
     */
    protected function getPhpCsBinary()
    {
        switch (PHP_OS_FAMILY) {
            case self::OS_WINDOWS:
                return "phpcs.bat";
                break;
            default:
                return "phpcs";
                break;
        }
    }

    /**
     * Check whether PHPCS Configuration is available
     *
     * @throws RuntimeException
     */
    protected function checkPhpCsConfiguration()
    {
        if (!file_exists($this->getPhpCsConfigurationPath())) {
            throw new RuntimeException("PHPCS Configuration file missing");
        }
    }
}
