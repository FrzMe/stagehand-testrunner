<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2011-2012 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stagehand_TestRunner
 * @copyright  2011-2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.18.0
 */

namespace Stagehand\TestRunner\Process\Autotest;

use Stagehand\TestRunner\CLI\Terminal;
use Stagehand\TestRunner\Core\ApplicationContext;
use Stagehand\TestRunner\Core\Exception;
use Stagehand\TestRunner\Core\IComponentAwareFactory;
use Stagehand\TestRunner\Core\LegacyProxy;
use Stagehand\TestRunner\Core\TestTargets;
use Stagehand\TestRunner\Notification\Notification;
use Stagehand\TestRunner\Process\AlterationMonitoring;
use Stagehand\TestRunner\Process\FatalError;
use Stagehand\TestRunner\Process\StreamableProcess;
use Stagehand\TestRunner\Util\String;

/**
 * @package    Stagehand_TestRunner
 * @copyright  2011-2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.18.0
 */
abstract class Autotest
{
    /**
     * @var string
     */
    protected $runnerCommand;

    /**
     * @var array
     */
    protected $runnerOptions;

    /**
     * @var \Stagehand\TestRunner\CLI\Terminal
     * @since Property available since Release 3.0.0
     */
    protected $terminal;

    /**
     * @var \Stagehand\TestRunner\Core\TestTargets
     * @since Property available since Release 3.0.0
     */
    protected $testTargets;

    /**
     * @var array
     * @since Property available since Release 3.0.0
     */
    protected $monitoringDirectories;

    /**
     * @var \Stagehand\TestRunner\Core\IComponentAwareFactory
     * @since Property available since Release 3.0.0
     */
    protected $preparerFactory;

    /**
     * @var \Stagehand\TestRunner\Core\IComponentAwareFactory
     * @since Property available since Release 3.0.0
     */
    protected $runnerFactory;

    /**
     * @var \Stagehand\TestRunner\Core\IComponentAwareFactory
     * @since Method available since Release 3.0.0
     */
    protected $notifierFactory;

    /**
     * @var \Stagehand\TestRunner\Core\LegacyProxy
     * @since Property available since Release 3.0.0
     */
    protected $legacyProxy;

    /**
     * @var \Stagehand\TestRunner\Process\AlterationMonitoring
     */
    protected $alterationMonitoring;

    /**
     * Monitors for changes in one or more target directories and runs tests in
     * the test directory recursively when changes are detected. And also the test
     * directory is always added to the directories to be monitored.
     *
     * @throws \Stagehand\TestRunner\Core\Exception
     */
    public function monitorAlteration()
    {
        if (is_null($this->runnerCommand)) {
            $this->initializeRunnerCommandAndOptions();
        }

        $this->alterationMonitoring->monitor($this->getMonitoringDirectories(), array($this, 'runTests'));
    }

    /**
     * @throws \Stagehand\TestRunner\Core\Exception
     * @since Method available since Release 2.18.0
     */
    public function runTests()
    {
        if (is_null($this->runnerCommand)) {
            $this->initializeRunnerCommandAndOptions();
        }

        $streamOutput = '';
        $process = new StreamableProcess($this->runnerCommand . ' ' . implode(' ', $this->runnerOptions));
        $process->addOutputStreamListener(function ($buffer) {
            echo $buffer;
        });
        $process->addOutputStreamListener(function ($buffer) use (&$streamOutput) {
            $streamOutput .= $buffer;
        });
        $process->addErrorStreamListener(function ($buffer) {
            echo $buffer;
        });
        $exitStatus = $process->run();

        if ($exitStatus != 0 && $this->runnerFactory->create()->usesNotification()) {
            $fatalError = new FatalError($streamOutput);
            $this->notifierFactory->craete()->notifyResult(
                new Notification(Notification::RESULT_STOPPED, $fatalError->getFullMessage())
            );
        }
    }

    /**
     * @param \Stagehand\TestRunner\CLI\Terminal $terminal
     * @since Method available since Release 3.0.0
     */
    public function setTerminal(Terminal $terminal)
    {
        $this->terminal = $terminal;
    }

    /**
     * @param \Stagehand\TestRunner\Core\TestTargets $testTargets
     * @since Method available since Release 3.0.0
     */
    public function setTestTargets(TestTargets $testTargets)
    {
        $this->testTargets = $testTargets;
    }

    /**
     * @param array $monitoringDirectories
     * @since Method available since Release 3.0.0
     */
    public function setMonitoringDirectories(array $monitoringDirectories)
    {
        $this->monitoringDirectories = $monitoringDirectories;
    }

    /**
     * @param \Stagehand\TestRunner\Core\LegacyProxy $legacyProxy
     * @since Method available since Release 3.0.0
     */
    public function setLegacyProxy(LegacyProxy $legacyProxy)
    {
        $this->legacyProxy = $legacyProxy;
    }

    /**
     * @param \Stagehand\TestRunner\Process\AlterationMonitoring $alterationMonitoring
     * @since Method available since Release 3.0.0
     */
    public function setAlterationMonitoring(AlterationMonitoring $alterationMonitoring)
    {
        $this->alterationMonitoring = $alterationMonitoring;
    }

    /**
     * @param \Stagehand\TestRunner\Core\IComponentAwareFactory $preparerFactory
     * @since Method available since Release 3.0.0
     */
    public function setPreparerFactory(IComponentAwareFactory $preparerFactory)
    {
        $this->preparerFactory = $preparerFactory;
    }

    /**
     * @param \Stagehand\TestRunner\Core\IComponentAwareFactory $runnerFactory
     * @since Method available since Release 3.0.0
     */
    public function setRunnerFactory(IComponentAwareFactory $runnerFactory)
    {
        $this->runnerFactory = $runnerFactory;
    }

    /**
     * @param \Stagehand\TestRunner\Core\IComponentAwareFactory $notifierFactory
     * @since Method available since Release 3.0.0
     */
    public function setNotifierFactory(IComponentAwareFactory $notifierFactory)
    {
        $this->notifierFactory = $notifierFactory;
    }

    /**
     * @return array
     * @throws \Stagehand\TestRunner\Core\Exception
     */
    protected function getMonitoringDirectories()
    {
        $monitoringDirectories = array();
        foreach (
            array_merge(
                $this->monitoringDirectories,
                $this->testTargets->getResources()
            ) as $directory) {
            if (!$this->legacyProxy->is_dir($directory)) {
                throw new Exception('A specified path [ ' . $directory . ' ] is not found or not a directory.');
            }

            $directory = $this->legacyProxy->realpath($directory);
            if ($directory === false) {
                throw new Exception('Cannnot get the absolute path of a specified directory [ ' . $directory . ' ]. Make sure all elements of the absolute path have valid permissions.');
            }

            if (!in_array($directory, $monitoringDirectories)) {
                $monitoringDirectories[] = $directory;
            }
        }

        return $monitoringDirectories;
    }

    /**
     * @return array
     * @throws \Stagehand\TestRunner\Core\Exception
     */
    protected function buildRunnerCommand()
    {
        if (array_key_exists('_', $_SERVER)) {
            $command = $_SERVER['_'];
        } elseif (array_key_exists('PHP_COMMAND', $_SERVER)) {
            $command = $_SERVER['PHP_COMMAND'];
        } else {
            $command = $_SERVER['argv'][0];
        }

        if (preg_match('!^/cygdrive/([a-z])/(.+)!', $command, $matches)) {
            $command = $matches[1] . ':\\' . str_replace('/', '\\', $matches[2]);
        }

        return escapeshellarg($command);
    }

    /**
     * @return array
     */
    protected function buildRunnerOptions()
    {
        $options = array();

        if (!preg_match('!^testrunner$!', trim($this->runnerCommand, '\'"'))) {
            $configFile = $this->getPHPConfigDir();
            if ($configFile !== false) {
                $options[] = '-c';
                $options[] = escapeshellarg($configFile);
            }

            $options[] = escapeshellarg($_SERVER['argv'][0]);
        }

        if ($this->terminal->colors()) {
            $options[] = '--ansi';
        }

        $options[] = escapeshellarg(strtolower(ApplicationContext::getInstance()->getPlugin()->getPluginID()));

        if (!is_null(ApplicationContext::getInstance()->getEnvironment()->getPreloadScript())) {
            $options[] = '-p ' . escapeshellarg(ApplicationContext::getInstance()->getEnvironment()->getPreloadScript());
        }

        $options[] = '-R';

        if ($this->runnerFactory->create()->usesNotification()) {
            $options[] = '-n';
        }

        if ($this->runnerFactory->create()->stopsOnFailure()) {
            $options[] = '--stop-on-failure';
        }

        if (!$this->testTargets->isDefaultFilePattern()) {
            $options[] = '--test-file-pattern=' . escapeshellarg($this->testTargets->getFilePattern());
        }

        if ($this->runnerFactory->create()->printsDetailedProgressReport()) {
            $options[] = '--detailed-progress';
        }

        $options = array_merge($options, $this->doBuildRunnerOptions());

        $this->testTargets->walkOnResources(function ($resource, $index, TestTargets $testTargets) use (&$options) {
            $options[] = escapeshellarg($resource);
        });

        return $options;
    }

    /**
     * @return string
     * @since Method available since Release 2.18.1
     */
    protected function getPHPConfigDir()
    {
        return $this->legacyProxy->get_cfg_var('cfg_file_path');
    }

    /**
     * @since Method available since Release 2.18.1
     */
    protected function initializeRunnerCommandAndOptions()
    {
        $this->runnerCommand = $this->buildRunnerCommand();
        $this->runnerOptions = $this->buildRunnerOptions();
    }

    /**
     * @param string $runnerCommand
     * @return integer
     * @since Method available since Release 2.20.0
     */
    protected function executeRunnerCommand($runnerCommand)
    {
        return $this->legacyProxy->passthru($runnerCommand);
    }

    /**
     * @return array
     * @since Method available since Release 3.0.0
     */
    abstract protected function doBuildRunnerOptions();
}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
