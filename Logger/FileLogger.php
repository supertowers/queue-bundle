<?php

namespace SuperTowers\QueueBundle\Logger;

use SuperTowers\QueueBundle\Core\QueueLogger;

/**
 * Simple file logger for the queues bundle.
 *
 * Is registered as a listener in the queue consumer and writes
 * the logs into /var/log/queues.log
 *
 * @category QueueBundle
 * @package  Base
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class FileLogger implements QueueLogger
{
    const DEFAULT_FILENAME = '/var/log/queues.log';

    public function __construct($filename = null)
    {
        $this->filename = static::DEFAULT_FILENAME;
        if ($filename) {
            $this->filename = $filename;
        }
    }
    private function getFilename()
    {
        return $this->filename;
    }
    public function info($message)
    {
        $this->write(sprintf('[INFO] %s - %s', date('Y-m-d h:i:s'), $message));
    }
    public function notice($message)
    {
        $this->write(sprintf('[NOTICE] %s - %s', date('Y-m-d h:i:s'), $message));
    }
    public function warning($message)
    {
        $this->write(sprintf('[WARNING] %s - %s', date('Y-m-d h:i:s'), $message));
    }
    public function error($message)
    {
        $this->write(sprintf('[ERROR] %s - %s', date('Y-m-d h:i:s'), $message));
    }

    /**
     * @param string $message
     */
    private function write($message)
    {
        file_put_contents($this->getFilename(), $message . "\n", FILE_APPEND);
    }
}
