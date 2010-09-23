<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2009-2010 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2009-2010 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.7.0
 */

/**
 * @package    Stagehand_TestRunner
 * @copyright  2009-2010 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.7.0
 */
class Stagehand_TestRunner_Config
{
    public $testingResources = array();
    public $recursivelyScans = false;
    public $colors = false;
    public $preloadFile;
    public $enablesAutotest = false;
    public $monitoringDirectories = array();
    public $usesGrowl = false;
    public $growlPassword;
    public $testsOnlySpecifiedMethods = false;
    public $testsOnlySpecifiedClasses = false;
    public $printsDetailedProgressReport = false;
    public $junitXMLFile;
    public $logsResultsInJUnitXML = false;
    public $logsResultsInJUnitXMLInRealtime = false;
    public $framework;
    public $runnerClass;
    public $stopsOnFailure = false;
    public $workingDirectoryAtStartup;
    public $phpunitConfigFile;
    public $cakephpAppPath;
    public $cakephpCorePath;
    public $fileSuffixes = array();
    protected $testingMethods = array();
    protected $testingClasses = array();

    /**
     */
    public function __construct()
    {
        $this->workingDirectoryAtStartup = getcwd();
    }

    /**
     * @param string $testingMethod
     * @since Method available since Release 2.8.0
     */
    public function addTestingMethod($testingMethod)
    {
        $this->testsOnlySpecifiedMethods = true;
        $this->testingMethods[] = strtolower($testingMethod);
    }

    /**
     * @param string $testingClass
     * @since Method available since Release 2.8.0
     */
    public function addTestingClass($testingClass)
    {
        $this->testsOnlySpecifiedClasses = true;
        $this->testingClasses[] = strtolower($testingClass);
    }

    /**
     * @return boolean
     * @since Method available since Release 2.8.0
     */
    public function testsOnlySpecified()
    {
        return $this->testsOnlySpecifiedMethods || $this->testsOnlySpecifiedClasses;
    }

    /**
     * @param string $class
     * @param string $method
     * @return boolean
     * @since Method available since Release 2.10.0
     */
    public function isTestingMethod($class, $method)
    {
        foreach (array($class . '::' . $method, $method) as $fullyQualifiedMethodName) {
            if (in_array(strtolower($fullyQualifiedMethodName), $this->testingMethods)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $class
     * @return boolean
     * @since Method available since Release 2.10.0
     */
    public function isTestingClass($class)
    {
        return in_array(strtolower($class), $this->testingClasses);
    }

    /**
     * @return boolean
     * @since Method available since Release 2.14.0
     */
    public function usesPHPUnitConfigFile()
    {
        return !is_null($this->phpunitConfigFile);
    }

    /**
     * @param string $fileSuffix
     * @since Method available since Release 2.12.0
     */
    public function addFileSuffix($fileSuffix)
    {
        $this->fileSuffixes[] = $fileSuffix;
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
