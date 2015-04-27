<?php

namespace SuperTowers\QueueBundle\Core;

use \SuperTowers\QueueBundle\Exception\UnableToGatherCallbackForJobException;
use \ArrayAccess;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class responsible to understand and encapsulate queues config.
 *
 * @category QueueBundle
 * @package  Core
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueueConfig implements ArrayAccess, ContainerAwareInterface
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Stored result of latest modified time of the file.
     */
    private $fileModTime = 0;
    /**
     * Stored result of latest timestamp when the file was loaded.
     */
    private $previousTime = 0;
    /**
     * config
     *
     * @var array
     */
    private $data = null;


    const TIME_TO_RELOAD_CONFIG = 3000; // 10 secs

    const PRIORITY_NONE    = 9000;
    const PRIORITY_LOW     = 7000;
    const PRIORITY_MEDIUM  = 5000;
    const PRIORITY_HIGH    = 3000;
    const PRIORITY_TOP     = 1000;
    const PRIORITY_URGENT  = 0;

    const CONFIG_FILE = 'config/queues.php';

    /**
     * getCallback
     *
     * @author Pablo Lopez Torres <pablolopeztorres@gmail.com>
     * @param mixed $jobName
     * @return void
     * @todo pablo - 2014-12-18 : make a config for this
     */
    public function getCallback($jobName)
    {
        $jobConfig = $this['job-config'];

        if (isset($jobConfig[$jobName])) {
            $jobService = $jobConfig[$jobName];
            $queueJob = $this->container->get($jobService);
            $callback = array($queueJob, 'process');
        } else {
            throw new UnableToGatherCallbackForJobException($jobName);
        }

        return $callback;
    }


    private function reloadData()
    {
        $currentTime = (int) (microtime(true) * 1000);
        if ($this->previousTime + self::TIME_TO_RELOAD_CONFIG < $currentTime) {
            $baseDir = $this->container->get('kernel')->getRootDir();
            $filename = $baseDir . '/' .  self::CONFIG_FILE;

            clearstatcache(true, $filename);
            $modificationTime = filemtime($filename) * 1000;
            if ($modificationTime !== $this->fileModTime) {
                $this->data = include($filename);
                $this->fileModTime = $modificationTime;
            }
            $this->previousTime = $currentTime;
        }
    }

    //
    // ARRAY ACCESS
    //
    public function offsetExists($key)
    {
        $this->reloadData();
        return isset($this->data[$key]);
    }

    public function offsetGet($key)
    {
        $this->reloadData();
        if (isset($this->data[$key])) {
            $value = $this->data[$key];
        } else {
            $value = null;
            trigger_error("Unable to get element in config: '$key'", E_USER_WARNING);
        }

        return $value;
    }

    public function offsetSet($key, $value)
    {
        trigger_error('Write on this element is disabled', E_USER_WARNING);
    }

    public function offsetUnset($key)
    {
        trigger_error('Write on this element is disabled', E_USER_WARNING);
    }
}
