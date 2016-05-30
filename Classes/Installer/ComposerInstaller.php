<?php
namespace Cundd\CunddComposer\Installer;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Daniel Corn <info@cundd.net>, cundd
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;
use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;


/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class ComposerInstaller
{
    /**
     * Call composer on the command line to install the dependencies.
     *
     * @param boolean $dev Argument will be removed in future version
     * @return string                Returns the composer output
     */
    public function install($dev = false)
    {
        return $this->executeComposerCommand('install', $dev);
    }

    /**
     * Call composer on the command line to update the dependencies.
     *
     * @param boolean $dev Argument will be removed in future version
     * @return string                Returns the composer output
     */
    public function update($dev = false)
    {
        return $this->executeComposerCommand('update', $dev);
    }

    /**
     * Execute the given composer command
     *
     * @param string  $command The composer command to execute
     * @param boolean $dev     Argument will be removed in future version
     * @return string                Returns the composer output
     */
    protected function executeComposerCommand($command, $dev = false)
    {
        $pathToComposer = ComposerGeneralUtility::getComposerPath();

        if ($dev || func_num_args() > 1) {
            ComposerGeneralUtility::pd('Argument dev is deprecated and ignored');
        }

        ComposerGeneralUtility::makeSureTempPathExists();
        $fullCommand = ConfigurationUtility::getPHPExecutable() . ' '
//			. '-c ' . php_ini_loaded_file() . ' '
            . '"' . $pathToComposer . '" ' . $command . ' '
            . '--working-dir ' . '"' . ComposerGeneralUtility::getTempPath() . '" '
//			. '--no-interaction '
//			. '--no-ansi '
            . '--verbose '
//			. '--profile '
//			. '--optimize-autoloader '
//			. ($dev ? '--dev ' : '')
            . '2>&1';

        $fullCommand = 'COMPOSER_HOME=' . ComposerGeneralUtility::getTempPath() . ' ' . $fullCommand;

        $output = $this->executeShellCommand($fullCommand);

        ComposerGeneralUtility::pd($fullCommand);
        ComposerGeneralUtility::pd($output);
        return $output;
    }

    /**
     * Execute the shell command
     *
     * @param string $fullCommand Full composer command
     * @return string
     */
    protected function executeShellCommand($fullCommand)
    {
        $useShellExec = false;
        if ($useShellExec && !$this->getCustomEnvironmentConfiguration()) {
            return shell_exec($fullCommand);
        }

        $output = '';
        $descriptorSpec = array(
            0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            1 => array('pipe', 'w'),  // stdout is a pipe that the child will write to
            2 => array('pipe', sys_get_temp_dir() . '/error-output.txt', 'a') // stderr is a file to write to
        );

        $cwd = ComposerGeneralUtility::getTempPath();
        $env = array_merge($_ENV, $this->getCustomEnvironmentConfiguration());

        $process = proc_open($fullCommand, $descriptorSpec, $pipes, $cwd, $env);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to /tmp/error-output.txt

            // fwrite($pipes[0], '<?php print_r($_ENV); ? >');
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $returnValue = proc_close($process);

            ComposerGeneralUtility::pd('Return value:', $returnValue);
        }
        return $output;
    }

    /**
     * Return custom environment configuration
     *
     * @return array
     */
    private function getCustomEnvironmentConfiguration()
    {
        return array();
        //return array(
        //    'PATH' => getenv('PATH') . ':/usr/local/bin'
        //);
    }
}
