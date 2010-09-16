<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2007-2010 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007-2010 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.5.0
 */

/**
 * A testrunner script to run tests automatically.
 *
 * @package    Stagehand_TestRunner
 * @copyright  2007-2010 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.5.0
 */
class Stagehand_TestRunner extends Stagehand_CLIController
{
    protected $exceptionClass = 'Stagehand_TestRunner_Exception';
    protected $shortOptions = 'hVRcp:aw:gm:v';
    protected $longOptions =
        array(
            'growl-password=',
            'log-junit=',
            'log-junit-realtime',
            'classes=',
            'stop-on-failure',
            'phpunit-config='
        );
    protected $config;

    /**
     * @param string $framework
     */
    public function __construct($framework)
    {
        $this->config = new Stagehand_TestRunner_Config();
        $this->config->framework = $framework;
    }

    /**
     * @param string $option
     * @param string $value
     * @return boolean
     */
    protected function configureByOption($option, $value)
    {
        switch ($option) {
        case 'h':
            $this->printUsage();
            return false;
        case 'V':
            $this->printVersion();
            return false;
        case 'R':
            $this->config->recursivelyScans = true;
            return true;
        case 'c':
            if (@include_once 'Console/Color.php') {
                $this->config->colors = true;
            }
            return true;
        case 'p':
            $this->config->preloadFile = $value;
            return true;
        case 'a':
            $this->config->enablesAutotest = true;
            return true;
        case 'w':
            $this->config->monitoringDirectories = explode(',', $value);
            return true;
        case 'g':
            if (@include_once 'Net/Growl.php') {
                $this->config->usesGrowl = true;
            }
            return true;
        case '--growl-password':
            $this->config->growlPassword = $value;
            return true;
        case 'm':
            foreach (explode(',', $value) as $testingMethod) {
                $this->config->addTestingMethod($testingMethod);
            }
            return true;
        case '--classes':
            foreach (explode(',', $value) as $testingClass) {
                $this->config->addTestingClass($testingClass);
            }
            return true;
        case '--log-junit':
            $this->config->logsResultsInJUnitXML = true;
            $this->config->junitXMLFile = $value;
            return true;
        case '--log-junit-realtime':
            $this->config->logsResultsInJUnitXMLInRealtime = true;
            return true;
        case '--stop-on-failure':
            $this->config->stopsOnFailure = true;
            return true;
        case '--phpunit-config':
            $this->config->phpunitConfigFile = $value;
            return true;
        case 'v':
            $this->config->printsDetailedProgressReport = true;
            return true;
        }
    }

    /**
     * @param string $arg
     * @return boolean
     */
    protected function configureByArg($arg)
    {
        $this->config->testingResources[] = $arg;
        return true;
    }

    /**
     */
    protected function doRun()
    {
        if (!count($this->config->testingResources)) {
            $this->config->testingResources[] = $this->config->workingDirectoryAtStartup;
        }

        if (!$this->config->enablesAutotest) {
            $this->runTests();
        } else {
            $this->monitorAlteration();
        }
    }

    /**
     * Prints the usage.
     */
    protected function printUsage()
    {
        echo "USAGE
  {$_SERVER['SCRIPT_NAME']} [OPTIONS] DIRECTORY_OR_FILE1 DIRECTORY_OR_FILE2 ...

NOTES
  If no directories and files are given, {$_SERVER['SCRIPT_NAME']} runs all the tests
  in the current directory.

OPTIONS

  -h
     Prints this help and exit.

  -V
     Prints version information and exit.

  -R
     Recursively runs tests in the specified directory.

  -c
     Colors test results.

  -p FILE
     Preloads FILE before running tests.

  -a
     Monitors for changes in the specified directories and run tests when
     changes are detected.

  -w DIRECTORY1,DIRECTORY2,...
     Specifies one or more directories to be monitored for changes.

  -g
     Notifies test results to Growl.

  --growl-password=PASSWORD
     Specifies PASSWORD for Growl.

  -m METHOD1,METHOD2,...
     Runs only the specified tests in the specified file.
     (PHPUnit and SimpleTest)

  --classes=CLASS1,CLASS2,...
     Runs only the specified test classes in the specified file.
     (PHPUnit and SimpleTest)

  --log-junit=FILE
     Logs test results into the specified file in the JUnit XML format.
     (PHPUnit, SimpleTest, and PHPT)

  --log-junit-realtime
     Logs test results in real-time into the specified file in the JUnit XML format.
     (PHPUnit, SimpleTest, and PHPT)

  -v
     Prints detailed progress report.
     (PHPUnit and PHPT)

  --stop-on-failure
     Stops the test run when the first failure or error is raised.
     (PHPUnit, SimpleTest, and PHPT)
";
    }

    /**
     * Prints the version.
     */
    protected function printVersion()
    {
        echo "Stagehand_TestRunner @package_version@ ({$this->config->framework})

Copyright (c) 2005-2010 KUBO Atsuhiro <kubo@iteman.jp>,
              2007 Masahiko Sakamoto <msakamoto-sf@users.sourceforge.net>,
              2010 KUMAKURA Yousuke <kumatch@gmail.com>,
All rights reserved.
";
    }

    /**
     * Monitors for changes in one or more target directories and runs tests in
     * the test directory recursively when changes are detected. And also the test
     * directory is always added to the directories to be monitored.
     *
     * @throws Stagehand_TestRunner_Exception
     * @since Method available since Release 2.1.0
     */
    protected function monitorAlteration()
    {
        $monitoringDirectories = array();
        foreach (array_merge($this->config->monitoringDirectories,
                             $this->config->testingResources) as $directory
                 ) {
            if (!is_dir($directory)) {
                throw new Stagehand_TestRunner_Exception(
                    'A specified path [ ' .
                    $directory .
                    ' ] is not found or not a directory'
                                                         );
            }

            $directory = realpath($directory);
            if ($directory === false) {
                throw new Stagehand_TestRunner_Exception(
                    'Cannnot get the absolute path of a specified directory [ ' .
                    $directory .
                    ' ]. Make sure all elements of the absolute path have valid permissions.'
                                                         );
            }

            if (!in_array($directory, $monitoringDirectories)) {
                $monitoringDirectories[] = $directory;
            }
        }

        if (array_key_exists('_', $_SERVER)) {
            $command = $_SERVER['_'];
        } elseif (array_key_exists('PHP_COMMAND', $_SERVER)) {
            $command = $_SERVER['PHP_COMMAND'];
        } else {
            $command = $_SERVER['argv'][0];
        }

        $options = array();
        if (preg_match('!^/cygdrive/([a-z])/(.+)!', $command, $matches)) {
            $command = $matches[1] . ':\\' . str_replace('/', '\\', $matches[2]);
        }

        if (!preg_match('/(?:phpspec|phpt|phpunit|simpletest)runner$/', $command)) {
            $configFile = get_cfg_var('cfg_file_path');
            if ($configFile !== false) {
                $options[] = '-c';
                $options[] = dirname($configFile);
            }

            $options[] = $_SERVER['argv'][0];
        }

        $options[] = '-R';

        if (!is_null($this->config->preloadFile)) {
            $options[] = '-p ' . $this->config->preloadFile;
        }

        if ($this->config->colors) {
            $options[] = '-c';
        }

        if ($this->config->usesGrowl) {
            $options[] = '-g';
        }

        if (!is_null($this->config->growlPassword)) {
            $options[] = '--growl-password=' . $this->config->growlPassword;
        }

        foreach ($this->config->testingResources as $testingResource) {
            $options[] = $testingResource;
        }

        $this->createAlterationMonitor($monitoringDirectories, $command, $options)->monitor();
    }

    /**
     * Runs tests.
     *
     * @since Method available since Release 2.1.0
     */
    protected function runTests()
    {
        if ($this->shouldPrepare()) {
            $this->createPreparator()->prepare();
        }
        $runner = $this->createRunner();
        $runner->run($this->createCollector()->collect());
        if ($this->config->usesGrowl) {
            $this->notifyGrowlOfResults($runner->getNotification());
        }
    }

    /**
     * @return Stagehand_TestRunner_Collector
     * @since Method available since Release 2.11.0
     */
    protected function createCollector()
    {
        $class = 'Stagehand_TestRunner_Collector_' . $this->config->framework . 'Collector';
        return new $class($this->config);
    }

    /**
     * @return Stagehand_TestRunner_Runner
     * @since Method available since Release 2.11.0
     */
    protected function createRunner()
    {
        $factory = new Stagehand_TestRunner_Runner_RunnerFactory($this->config);
        return $factory->create();
    }

    /**
     * @param stdClass $notification
     * @since Method available since Release 2.11.0
     */
    protected function notifyGrowlOfResults(stdClass $notification)
    {
        $growl = new Net_Growl(
                     new Net_Growl_Application(
                         'Stagehand_TestRunner',
                         array('Green', 'Red'),
                         $this->config->growlPassword
                     )
                 );
        $growl->notify(
            $notification->name,
            'Test Results by Stagehand_TestRunner',
            $notification->description
        );
    }

    /**
     * @return boolean
     * @since Method available since Release 2.12.0
     */
    protected function shouldPrepare()
    {
        $class = $this->getPreparetorClass();
        return class_exists($class);
    }

    /**
     * @return Stagehand_TestRunner_Preparator
     * @since Method available since Release 2.12.0
     */
    protected function createPreparator()
    {
        $class = $this->getPreparetorClass();
        return new $class();
    }

    /**
     * @return string
     * @since Method available since Release 2.12.0
     */
    protected function getPreparetorClass()
    {
        return 'Stagehand_TestRunner_Preparator_' . $this->config->framework . 'Preparator';
    }

    /**
     * @param array  $monitoringDirectories
     * @param string $command
     * @param array  $options
     * @return Stagehand_AlterationMonitor
     * @since Method available since Release 2.13.0
     */
    protected function createAlterationMonitor(array $monitoringDirectories, $command, array $options)
    {
        return new Stagehand_AlterationMonitor(
                       $monitoringDirectories,
                       create_function(
                           '',
                           "passthru('" .
                           $command .
                           ' ' .
                           implode(' ', $options) .
                           "');"
                       )
               );
    }
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
