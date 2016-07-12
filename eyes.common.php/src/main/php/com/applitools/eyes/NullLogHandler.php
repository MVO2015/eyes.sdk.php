<?php
require "LogHandler.php";
/**
 * Ignores all log messages.
 */
class NullLogHandler implements LogHandler {

    public function onMessage($verbose, $logString) {}

    public function open() {}

    public function close() {}
}