<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2009-2011 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2009-2011 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @link       http://simpletest.org/
 * @since      File available since Release 2.10.0
 */

/**
 * @package    Stagehand_TestRunner
 * @copyright  2009-2011 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @link       http://simpletest.org/
 * @since      Class available since Release 2.10.0
 */
class Stagehand_TestRunner_Runner_SimpleTestRunner_JUnitXMLReporter extends SimpleReporter implements Stagehand_TestRunner_Runner_JUnitXMLWriterAdapter
{
    /**
     * @var Stagehand_TestRunner_JUnitXMLWriter
     */
    protected $xmlWriter;

    /**
     * @var Stagehand_TestRunner_TestSuite_SimpleTestTestSuite
     */
    protected $suite;
    protected $methodStartTime;
    protected $assertionCount;
    protected $reportedFailure;

    /**
     * @var Stagehand_TestRunner_Config
     */
    protected $config;

    /**
     * @param Stagehand_TestRunner_JUnitXMLWriter $xmlWriter
     */
    public function setXMLWriter(Stagehand_TestRunner_JUnitXMLWriter $xmlWriter)
    {
        $this->xmlWriter = $xmlWriter;
    }

    /**
     * @param Stagehand_TestRunner_TestSuite_SimpleTestTestSuite $suite
     */
    public function setTestSuite(Stagehand_TestRunner_TestSuite_SimpleTestTestSuite $suite)
    {
        $this->suite = $suite;
    }

    /**
     * @param Stagehand_TestRunner_Config $config
     */
    public function setConfig(Stagehand_TestRunner_Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string  $testName
     * @param integer $size
     */
    public function paintGroupStart($testName, $size)
    {
        parent::paintGroupStart($testName, $size);
        $this->xmlWriter->startTestSuite($testName, $this->suite->countTests());
    }

    /**
     * @param string $testName
     */
    public function paintGroupEnd($testName)
    {
        parent::paintGroupEnd($testName);
        $this->xmlWriter->endTestSuite();
    }

    /**
     * @param string $testName
     */
    public function paintCaseStart($testName)
    {
        parent::paintCaseStart($testName);
        $this->xmlWriter->startTestSuite(
            $testName,
            $this->suite->countTestsInTestCase(SimpleTest::getContext()->getTest())
        );
    }

    /**
     * @param string $testName
     */
    public function paintCaseEnd($testName)
    {
        parent::paintCaseEnd($testName);
        $this->xmlWriter->endTestSuite();
    }

    /**
     * @param string $testName
     */
    public function paintMethodStart($testName)
    {
        parent::paintMethodStart($testName);
        $this->xmlWriter->startTestCase(
            $testName,
            SimpleTest::getContext()->getTest()
        );
        $this->methodStartTime = microtime(true);
        $this->assertionCount = 0;
        $this->reportedFailure = false;
    }

    /**
     * @param string $testName
     */
    public function paintMethodEnd($testName)
    {
        $elapsedTime = microtime(true) - $this->methodStartTime;
        parent::paintMethodEnd($testName);
        $this->xmlWriter->endTestCase($elapsedTime, $this->assertionCount);
    }

    /**
     * @param string $testName
     */
    public function paintHeader($testName)
    {
        parent::paintHeader($testName);
        $this->xmlWriter->startTestSuites();
    }

    /**
     * @param string $testName
     */
    public function paintFooter($testName)
    {
        parent::paintFooter($testName);
        $this->xmlWriter->endTestSuites();
    }

    /**
     * @param string $message
     */
    public function paintPass($message)
    {
        parent::paintPass($message);
        ++$this->assertionCount;
    }

    /**
     * @param string $message
     */
    public function paintFail($message)
    {
        if ($this->reportedFailure) return;
        parent::paintFail($message);
        $this->xmlWriter->writeFailure($message);
        ++$this->assertionCount;
        $this->reportedFailure = true;
    }

    /**
     * @param string $message
     */
    public function paintError($message)
    {
        parent::paintError($message);
        $this->xmlWriter->writeError($message);
    }

    /**
     * @param Exception $e
     */
    public function paintException(Exception $e)
    {
        parent::paintException($e);
        $this->xmlWriter->writeError(
            get_class($e) . ': ' . $e->getMessage() . PHP_EOL . PHP_EOL .
            $e->getFile() . ':' . $e->getLine() . PHP_EOL .
            $this->buildFailureTrace($e->getTrace())
        );
    }

    /**
     * @param string $message
     */
    public function paintSkip($message)
    {
        parent::paintSkip($message);
        $this->xmlWriter->writeError($message);
    }

    /**
     * @param array $backtrace
     */
    protected function buildFailureTrace(array $backtrace)
    {
        $failureTrace = '';
        for ($i = 0, $count = count($backtrace); $i < $count; ++$i) {
            if (!array_key_exists('file', $backtrace[$i])) {
                continue;
            }

            $failureTrace .=
                $backtrace[$i]['file'] .
                ':' .
                (array_key_exists('line', $backtrace[$i]) ? $backtrace[$i]['line']
                                                          : '?') .
                "\n";
        }

        return $failureTrace;
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
