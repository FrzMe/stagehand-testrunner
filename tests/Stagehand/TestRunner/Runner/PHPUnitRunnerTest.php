<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2009 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2009 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.10.0
 */

// {{{ Stagehand_TestRunner_Runner_PHPUnitRunnerTest

/**
 * @package    Stagehand_TestRunner
 * @copyright  2009 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.10.0
 */
class Stagehand_TestRunner_Runner_PHPUnitRunnerTest extends PHPUnit_Framework_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $backupGlobals = false;

    /**#@-*/

    /**#@+
     * @access private
     */

    private $tmpDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */
 
    public function setUp()
    {
        $this->tmpDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    public function tearDown()
    {
        $directoryScanner = new Stagehand_DirectoryScanner(array($this, 'removeJUnitXMLFile'));
        $directoryScanner->addExclude('^.*');
        $directoryScanner->addInclude('\.xml$');
        $directoryScanner->scan($this->tmpDirectory);
    }

    public function removeJUnitXMLFile($element)
    {
        unlink($element);
    }

    /**
     * @test
     */
    public function logsTestResultsIntoTheSpecifiedFileInTheJunitXmlFormat()
    {
        $config = new Stagehand_TestRunner_Config();
        $config->junitLogFile = $this->tmpDirectory . '/' . __FUNCTION__ . '.xml';
        $suite = new PHPUnit_Framework_TestSuite();
        $suite->addTestSuite('Stagehand_TestRunner_PHPUnitPassTest');
        $suite->addTestSuite('Stagehand_TestRunner_PHPUnitFailureTest');
        $suite->addTestSuite('Stagehand_TestRunner_PHPUnitErrorTest');
        ob_start();
        $runner = new Stagehand_TestRunner_Runner_PHPUnitRunner($config);
        $runner->run($suite);
        ob_end_clean();
        $this->assertFileExists($config->junitLogFile);

        $junitXML = new DOMDocument();
        $junitXML->load($config->junitLogFile);
        $this->assertTrue($junitXML->hasChildNodes());

        $this->assertEquals(1, $junitXML->childNodes->length);
        $this->assertEquals('testsuites', $junitXML->childNodes->item(0)->nodeName);
        $this->assertEquals(1, $junitXML->childNodes->item(0)->childNodes->length);
        $this->assertEquals('testsuite', $junitXML->childNodes->item(0)->childNodes->item(0)->nodeName);

        $parentTestsuite = $junitXML->childNodes->item(0)->childNodes->item(0);
        $this->assertTrue($parentTestsuite->hasChildNodes());
        $this->assertTrue($parentTestsuite->hasAttribute('name'));
        $this->assertTrue($parentTestsuite->hasAttribute('tests'));
        $this->assertEquals(5, $parentTestsuite->getAttribute('tests'));
        $this->assertTrue($parentTestsuite->hasAttribute('assertions'));
        $this->assertEquals(5, $parentTestsuite->getAttribute('assertions'));
        $this->assertTrue($parentTestsuite->hasAttribute('failures'));
        $this->assertEquals(1, $parentTestsuite->getAttribute('failures'));
        $this->assertTrue($parentTestsuite->hasAttribute('errors'));
        $this->assertEquals(1, $parentTestsuite->getAttribute('errors'));
        $this->assertEquals(3, $parentTestsuite->childNodes->length);
        $this->assertTrue($parentTestsuite->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $parentTestsuite->getAttribute('time'));

        $childTestsuite = $parentTestsuite->childNodes->item(0);
        $this->assertTrue($childTestsuite->hasChildNodes());
        $this->assertTrue($childTestsuite->hasAttribute('name'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitPassTest',
                            $childTestsuite->getAttribute('name'));
        $this->assertTrue($childTestsuite->hasAttribute('file'));
        $class = new ReflectionClass('Stagehand_TestRunner_PHPUnitPassTest');
        $this->assertEquals($class->getFileName(), $childTestsuite->getAttribute('file'));
        $this->assertTrue($childTestsuite->hasAttribute('tests'));
        $this->assertEquals(3, $childTestsuite->getAttribute('tests'));
        $this->assertTrue($childTestsuite->hasAttribute('assertions'));
        $this->assertEquals(4, $childTestsuite->getAttribute('assertions'));
        $this->assertTrue($childTestsuite->hasAttribute('failures'));
        $this->assertEquals(0, $childTestsuite->getAttribute('failures'));
        $this->assertTrue($childTestsuite->hasAttribute('errors'));
        $this->assertEquals(0, $childTestsuite->getAttribute('errors'));
        $this->assertEquals(3, $childTestsuite->childNodes->length);
        $this->assertTrue($childTestsuite->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $childTestsuite->getAttribute('time'));

        $testcase = $childTestsuite->childNodes->item(0);
        $this->assertFalse($testcase->hasChildNodes());
        $this->assertTrue($testcase->hasAttribute('name'));
        $this->assertEquals('passWithAnAssertion', $testcase->getAttribute('name'));
        $this->assertTrue($testcase->hasAttribute('class'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitPassTest',
                            $testcase->getAttribute('class'));
        $this->assertTrue($testcase->hasAttribute('file'));
        $this->assertEquals($class->getFileName(), $testcase->getAttribute('file'));
        $this->assertTrue($testcase->hasAttribute('line'));
        $method = $class->getMethod('passWithAnAssertion');
        $this->assertEquals($method->getStartLine(), $testcase->getAttribute('line'));
        $this->assertTrue($testcase->hasAttribute('assertions'));
        $this->assertEquals(1, $testcase->getAttribute('assertions'));
        $this->assertTrue($testcase->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $testcase->getAttribute('time'));

        $testcase = $childTestsuite->childNodes->item(1);
        $this->assertFalse($testcase->hasChildNodes());
        $this->assertTrue($testcase->hasAttribute('name'));
        $this->assertEquals('passWithMultipleAssertions',
                            $testcase->getAttribute('name'));
        $this->assertTrue($testcase->hasAttribute('class'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitPassTest',
                            $testcase->getAttribute('class'));
        $this->assertTrue($testcase->hasAttribute('file'));
        $this->assertEquals($class->getFileName(), $testcase->getAttribute('file'));
        $this->assertTrue($testcase->hasAttribute('line'));
        $method = $class->getMethod('passWithMultipleAssertions');
        $this->assertEquals($method->getStartLine(), $testcase->getAttribute('line'));
        $this->assertTrue($testcase->hasAttribute('assertions'));
        $this->assertEquals(2, $testcase->getAttribute('assertions'));
        $this->assertTrue($testcase->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $testcase->getAttribute('time'));

        $testcase = $childTestsuite->childNodes->item(2);
        $this->assertFalse($testcase->hasChildNodes());
        $this->assertTrue($testcase->hasAttribute('name'));
        $this->assertEquals('日本語を使用できる', $testcase->getAttribute('name'));
        $this->assertTrue($testcase->hasAttribute('class'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitPassTest',
                            $testcase->getAttribute('class'));
        $this->assertTrue($testcase->hasAttribute('file'));
        $this->assertEquals($class->getFileName(), $testcase->getAttribute('file'));
        $this->assertTrue($testcase->hasAttribute('line'));
        $method = $class->getMethod('日本語を使用できる');
        $this->assertEquals($method->getStartLine(), $testcase->getAttribute('line'));
        $this->assertTrue($testcase->hasAttribute('assertions'));
        $this->assertEquals(1, $testcase->getAttribute('assertions'));
        $this->assertTrue($testcase->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $testcase->getAttribute('time'));

        $childTestsuite = $parentTestsuite->childNodes->item(1);
        $this->assertTrue($childTestsuite->hasChildNodes());
        $this->assertTrue($childTestsuite->hasAttribute('name'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitFailureTest',
                            $childTestsuite->getAttribute('name'));
        $this->assertTrue($childTestsuite->hasAttribute('file'));
        $class = new ReflectionClass('Stagehand_TestRunner_PHPUnitFailureTest');
        $this->assertEquals($class->getFileName(), $childTestsuite->getAttribute('file'));
        $this->assertTrue($childTestsuite->hasAttribute('tests'));
        $this->assertEquals(1, $childTestsuite->getAttribute('tests'));
        $this->assertTrue($childTestsuite->hasAttribute('assertions'));
        $this->assertEquals(1, $childTestsuite->getAttribute('assertions'));
        $this->assertTrue($childTestsuite->hasAttribute('failures'));
        $this->assertEquals(1, $childTestsuite->getAttribute('failures'));
        $this->assertTrue($childTestsuite->hasAttribute('errors'));
        $this->assertEquals(0, $childTestsuite->getAttribute('errors'));
        $this->assertEquals(1, $childTestsuite->childNodes->length);
        $this->assertTrue($childTestsuite->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $childTestsuite->getAttribute('time'));

        $testcase = $childTestsuite->childNodes->item(0);
        $this->assertTrue($testcase->hasChildNodes());
        $this->assertTrue($testcase->hasAttribute('name'));
        $this->assertEquals('isFailure', $testcase->getAttribute('name'));
        $this->assertTrue($testcase->hasAttribute('class'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitFailureTest',
                            $testcase->getAttribute('class'));
        $this->assertTrue($testcase->hasAttribute('file'));
        $this->assertEquals($class->getFileName(), $testcase->getAttribute('file'));
        $this->assertTrue($testcase->hasAttribute('line'));
        $method = $class->getMethod('isFailure');
        $this->assertEquals($method->getStartLine(), $testcase->getAttribute('line'));
        $this->assertTrue($testcase->hasAttribute('assertions'));
        $this->assertEquals(1, $testcase->getAttribute('assertions'));
        $this->assertTrue($testcase->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $testcase->getAttribute('time'));
        $this->assertEquals(1, $testcase->childNodes->length);
        $failure = $testcase->childNodes->item(0);
        $this->assertTrue($failure->hasChildNodes());
        $this->assertTrue($failure->hasAttributes());
        $this->assertTrue($failure->hasAttribute('type'));
        $this->assertEquals('PHPUnit_Framework_ExpectationFailedException',
                            $failure->getAttribute('type'));
        $this->assertRegexp('/^Stagehand_TestRunner_PHPUnitFailureTest::isFailure\s+This is an error message\./', $failure->nodeValue);

        $childTestsuite = $parentTestsuite->childNodes->item(2);
        $this->assertTrue($childTestsuite->hasChildNodes());
        $this->assertTrue($childTestsuite->hasAttribute('name'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitErrorTest',
                            $childTestsuite->getAttribute('name'));
        $this->assertTrue($childTestsuite->hasAttribute('file'));
        $class = new ReflectionClass('Stagehand_TestRunner_PHPUnitErrorTest');
        $this->assertEquals($class->getFileName(), $childTestsuite->getAttribute('file'));
        $this->assertTrue($childTestsuite->hasAttribute('tests'));
        $this->assertEquals(1, $childTestsuite->getAttribute('tests'));
        $this->assertTrue($childTestsuite->hasAttribute('assertions'));
        $this->assertEquals(0, $childTestsuite->getAttribute('assertions'));
        $this->assertTrue($childTestsuite->hasAttribute('failures'));
        $this->assertEquals(0, $childTestsuite->getAttribute('failures'));
        $this->assertTrue($childTestsuite->hasAttribute('errors'));
        $this->assertEquals(1, $childTestsuite->getAttribute('errors'));
        $this->assertEquals(1, $childTestsuite->childNodes->length);
        $this->assertTrue($childTestsuite->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $childTestsuite->getAttribute('time'));

        $testcase = $childTestsuite->childNodes->item(0);
        $this->assertTrue($testcase->hasChildNodes());
        $this->assertTrue($testcase->hasAttribute('name'));
        $this->assertEquals('isError', $testcase->getAttribute('name'));
        $this->assertTrue($testcase->hasAttribute('class'));
        $this->assertEquals('Stagehand_TestRunner_PHPUnitErrorTest',
                            $testcase->getAttribute('class'));
        $this->assertTrue($testcase->hasAttribute('file'));
        $this->assertEquals($class->getFileName(), $testcase->getAttribute('file'));
        $this->assertTrue($testcase->hasAttribute('line'));
        $method = $class->getMethod('isError');
        $this->assertEquals($method->getStartLine(), $testcase->getAttribute('line'));
        $this->assertTrue($testcase->hasAttribute('assertions'));
        $this->assertEquals(0, $testcase->getAttribute('assertions'));
        $this->assertTrue($testcase->hasAttribute('time'));
        $this->assertRegExp('/^\d+\.\d+$/', $testcase->getAttribute('time'));
        $this->assertEquals(1, $testcase->childNodes->length);
        $error = $testcase->childNodes->item(0);
        $this->assertTrue($error->hasChildNodes());
        $this->assertTrue($error->hasAttributes());
        $this->assertTrue($error->hasAttribute('type'));
        $this->assertEquals('Stagehand_LegacyError_PHPError_Exception',
                            $error->getAttribute('type'));
        $this->assertRegexp('/^Stagehand_TestRunner_PHPUnitErrorTest::isError\s+Stagehand_LegacyError_PHPError_Exception:/', $error->nodeValue);
    }

    /**
     * @test
     */
    public function logsTestResultsIntoTheSpecifiedFileInTheJunitXmlFormatIfNoTestsAreFound()
    {
        $config = new Stagehand_TestRunner_Config();
        $config->junitLogFile = $this->tmpDirectory . '/' . __FUNCTION__ . '.xml';
        $suite = new PHPUnit_Framework_TestSuite();
        ob_start();
        $runner = new Stagehand_TestRunner_Runner_PHPUnitRunner($config);
        $runner->run($suite);
        ob_end_clean();
        $this->assertFileExists($config->junitLogFile);

        $junitXML = new DOMDocument();
        $junitXML->load($config->junitLogFile);
        $this->assertTrue($junitXML->hasChildNodes());

        $this->assertEquals(1, $junitXML->childNodes->length);
        $this->assertEquals('testsuites', $junitXML->childNodes->item(0)->nodeName);
        $this->assertEquals(1, $junitXML->childNodes->item(0)->childNodes->length);
        $this->assertEquals('testsuite', $junitXML->childNodes->item(0)->childNodes->item(0)->nodeName);

        $parentTestsuite = $junitXML->childNodes->item(0)->childNodes->item(0);
        $this->assertFalse($parentTestsuite->hasChildNodes());
        $this->assertTrue($parentTestsuite->hasAttribute('name'));
        $this->assertTrue($parentTestsuite->hasAttribute('tests'));
        $this->assertEquals(0, $parentTestsuite->getAttribute('tests'));
        $this->assertFalse($parentTestsuite->hasAttribute('assertions'));
        $this->assertTrue($parentTestsuite->hasAttribute('failures'));
        $this->assertEquals(0, $parentTestsuite->getAttribute('failures'));
        $this->assertTrue($parentTestsuite->hasAttribute('errors'));
        $this->assertEquals(0, $parentTestsuite->getAttribute('errors'));
        $this->assertEquals(0, $parentTestsuite->childNodes->length);
        $this->assertFalse($parentTestsuite->hasAttribute('time'));
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}

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