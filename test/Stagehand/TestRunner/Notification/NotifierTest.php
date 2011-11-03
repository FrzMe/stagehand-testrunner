<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2011 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2011 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.20.0
 */

/**
 * @package    Stagehand_TestRunner
 * @copyright  2011 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.20.0
 */
class Stagehand_TestRunner_Notification_NotifierTest extends PHPUnit_Framework_TestCase
{
    const NOTIFICATION_MESSAGE = 'NOTIFICATION_MESSAGE';

    /**
     * @test
     * @dataProvider decisionTable
     * @param string $result
     * @param string $os
     * @param string $commandRegex
     * @since Method available since Release 2.21.0
     */
    public function notifiesTheResultByTheAppropriateCommandForTheCurrentHost($result, $os, $commandRegex)
    {
        $notifier = Phake::partialMock('Stagehand_TestRunner_Notification_Notifier');

        if ($os == 'win') {
            Phake::when($notifier)->isWin()->thenReturn(true);
            Phake::when($notifier)->isDarwin()->thenReturn(false);
            Phake::when($notifier)->isLinux()->thenReturn(false);
        } elseif ($os == 'darwin') {
            Phake::when($notifier)->isWin()->thenReturn(false);
            Phake::when($notifier)->isDarwin()->thenReturn(true);
            Phake::when($notifier)->isLinux()->thenReturn(false);
        } elseif ($os == 'linux') {
            Phake::when($notifier)->isWin()->thenReturn(false);
            Phake::when($notifier)->isDarwin()->thenReturn(false);
            Phake::when($notifier)->isLinux()->thenReturn(true);
        }

        Phake::when($notifier)->executeNotifyCommand($this->anything())->thenReturn(null);

        $notifier->notifyResult(
            new Stagehand_TestRunner_Notification_Notification(
                $result,
                self::NOTIFICATION_MESSAGE
        ));

        Phake::verify($notifier)->executeNotifyCommand(Phake::capture($command));
        $this->assertThat($command, $this->matchesRegularExpression($commandRegex));
    }

    /**
     * @return array
     * @since Method available since Release 2.21.0
     */
    public function decisionTable()
    {
        $resultPassed = Stagehand_TestRunner_Notification_Notification::RESULT_PASSED;
        $resultFailed = Stagehand_TestRunner_Notification_Notification::RESULT_FAILED;
        $resultStopped = Stagehand_TestRunner_Notification_Notification::RESULT_STOPPED;
        $titlePassed = Stagehand_TestRunner_Notification_Notifier::TITLE_PASSED;
        $titleFailed = Stagehand_TestRunner_Notification_Notifier::TITLE_FAILED;
        $titleStopped = Stagehand_TestRunner_Notification_Notifier::TITLE_STOPPED;
        $iconPassed = Stagehand_TestRunner_Notification_Notifier::$ICON_PASSED;
        $iconFailed = Stagehand_TestRunner_Notification_Notifier::$ICON_FAILED;
        $iconStopped = Stagehand_TestRunner_Notification_Notifier::$ICON_STOPPED;

        return array(
            array($resultPassed, 'win', $this->buildCommandRegexForWin($titlePassed, $iconPassed)),
            array($resultPassed, 'darwin', $this->buildCommandRegexForDarwin($titlePassed, $iconPassed)),
            array($resultPassed, 'linux', $this->buildCommandRegexForLinux($titlePassed, $iconPassed)),
            array($resultFailed, 'win', $this->buildCommandRegexForWin($titleFailed, $iconFailed)),
            array($resultFailed, 'darwin', $this->buildCommandRegexForDarwin($titleFailed, $iconFailed)),
            array($resultFailed, 'linux', $this->buildCommandRegexForLinux($titleFailed, $iconFailed)),
            array($resultStopped, 'win', $this->buildCommandRegexForWin($titleStopped, $iconStopped)),
            array($resultStopped, 'darwin', $this->buildCommandRegexForDarwin($titleStopped, $iconStopped)),
            array($resultStopped, 'linux', $this->buildCommandRegexForLinux($titleStopped, $iconStopped)),
        );
    }

    /**
     * @param string $title
     * @param string $icon
     * @return string
     * @since Method available since Release 2.21.0
     */
    protected function buildCommandRegexForWin($title, $icon)
    {
        return '!^growlnotify /t:' . escapeshellarg($title) .
            ' /p:-2 /i:' . escapeshellarg(preg_quote($icon)) .
            ' /a:Stagehand_TestRunner /r:' .
            escapeshellarg(Stagehand_TestRunner_Notification_Notifier::TITLE_PASSED) . ',' .
            escapeshellarg(Stagehand_TestRunner_Notification_Notifier::TITLE_FAILED) . ',' .
            escapeshellarg(Stagehand_TestRunner_Notification_Notifier::TITLE_STOPPED) .
            ' /n:' . escapeshellarg($title) .
            ' /silent:true ' . escapeshellarg(self::NOTIFICATION_MESSAGE) . '$!';
    }

    /**
     * @param string $title
     * @param string $icon
     * @return string
     * @since Method available since Release 2.21.0
     */
    protected function buildCommandRegexForDarwin($title, $icon)
    {
        return '!^growlnotify --name ' . escapeshellarg($title) .
            ' --priority -2 --image ' . escapeshellarg(preg_quote($icon)) .
            ' --title ' . escapeshellarg($title) .
            ' --message ' . escapeshellarg('.+') . '$!';
    }

    /**
     * @param string $title
     * @param string $icon
     * @return string
     * @since Method available since Release 2.21.0
     */
    protected function buildCommandRegexForLinux($title, $icon)
    {
        return'!^notify-send --urgency=low --icon=' .
            escapeshellarg(preg_quote($icon)) .
            ' ' . escapeshellarg($title) .
            ' ' . escapeshellarg('.+') . '$!';
    }

    /**
     * @test
     * @link http://redmine.piece-framework.com/issues/332
     */
    public function addsABackslashForEachBackslashInTheMessageOnLinuxToPreventLosingOriginalBackslashes()
    {
        $notifier = Phake::partialMock('Stagehand_TestRunner_Notification_Notifier');

        Phake::when($notifier)->isWin()->thenReturn(false);
        Phake::when($notifier)->isDarwin()->thenReturn(false);
        Phake::when($notifier)->isLinux()->thenReturn(true);
        Phake::when($notifier)->executeNotifyCommand($this->anything())->thenReturn(null);

        $notifier->notifyResult(
            new Stagehand_TestRunner_Notification_Notification(
                Stagehand_TestRunner_Notification_Notification::RESULT_STOPPED,
                'Foo\Bar\Baz::qux()'
            )
        );

        Phake::verify($notifier)->executeNotifyCommand(
            $this->matchesRegularExpression('/' . preg_quote('Foo\\\\Bar\\\\Baz::qux()') . '/')
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