<?php

namespace SuperTowers\QueueBundle\Core;

use \SuperTowers\QueueBundle\ContainerAware;

use \Pheanstalk\Pheanstalk;
use \Pheanstalk\Job;

/**
 * Queue Manager that ensables all the pieces of the puzzle.
 *
 * Is the one to be used to enqueue jobs.
 *
 * @category QueueBundle
 * @package  Core
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueueManager
{
    const RUN_SYNC = 1;
    const RUN_ASYNC = 2;

    private $connection;

    private $connectionOptions = array(
        'host' => '127.0.0.1',
        'port' => 11300,
    );


    private $queueConsumer;

    public function __construct(array $config, QueueConsumer $queueConsumer)
    {
        $this->setConnectionOptions($config[0], $config[1]);
        $this->queueConsumer = $queueConsumer;
    }


    private function setConnectionOptions($host, $port)
    {
        $this->connectionOptions = array(
            'host' => $host,
            'port' => $port,
        );
    }

    public function enqueue($jobName, $parameters, $options = array())
    {
        list($tube, $priority, $delay, $ttr, $mode) = $this->getConfig($jobName);

        if (isset($options['priority'])) {
            $priority = $options['priority'];
        }

        if (isset($options['delay'])) {
            $delay = $options['delay'];
        }


        if ($mode === self::RUN_ASYNC) {
            $jid = $this->persist($tube, $jobName, $parameters, $priority, $delay, $ttr);
        } elseif ($mode === self::RUN_SYNC) {
            $job = new Job(
                true,
                $this->serializeJob($jobName, $parameters, $priority, $delay, $ttr)
            );
            $status = $this->queueConsumer->processJob($job);

            // @todo pablo - 2014-12-18 : do here something with the status
            $jid = true;
        } else {
            trigger_error('Unknown JOB mode: ' . $mode, E_USER_NOTICE);
        }

        return $jid;
    }

    //
    // STATUS
    //
    public function getStatus()
    {
        return $this->getDriver()->stats();
    }


    public function getTubes()
    {
        return $this->getDriver()->listTubes();
    }

    public function getTubesStatus()
    {
        $tubes = array();
        foreach ($this->getDriver()->listTubes() as $tube) {
            $tubes[$tube] = $this->getDriver()->statsTube($tube);
        }
        return $tubes;
    }

    public function getTubeInfo($tube)
    {
        return $this->getDriver()->statsTube($tube);
    }

    public function getErrors()
    {
        return $this->getDriver()->errors();
    }

    private $chosenTube;

    /**
     * @param integer $ttr
     */
    protected function persist($tube, $jobName, $parameters, $priority, $delay, $ttr)
    {
        if ($this->chosenTube !== $tube) {
            $this->chosenTube = $this->getDriver()->useTube($tube);
        }

        $job = $this->serializeJob($jobName, $parameters, $priority, $delay, $ttr);

        return $this->getDriver()->put($job, $priority, $delay, $ttr);
    }

    /**
     * @param integer $ttr
     */
    private function serializeJob($jobName, $parameters, $priority, $delay, $ttr)
    {
        return json_encode(array($jobName, $parameters, $priority, $delay, $ttr));
    }

    private function unserializeJob($data)
    {
        return json_decode($data, true);
    }

    protected function getDriver()
    {
        if ($this->connection === null) {
            $this->connection = new Pheanstalk(
                $this->connectionOptions['host'],
                $this->connectionOptions['port']
            );
        }
        return $this->connection;
    }

    /**
     * Get array of configuration options for the specific job name.
     *
     * @author Pablo Lopez Torres <pablolopeztorres@gmail.com>
     * @param string $jobName
     * @return void
     */
    private function getConfig($jobName)
    {
        $tokens = explode(':', $jobName);
        $tube = preg_replace('/[^A-Za-z0-9]/', '', $tokens[0]);

        $mode = self::RUN_ASYNC;
        // @todo pablo - 2014-12-18 : add support for running it sync / async
        // if ($tube === 'mail')
        // {
        //     $mode = self::RUN_SYNC;
        // }

        return array(
            $tube,                              // tube
            QueueConfig::PRIORITY_MEDIUM,      // priority
            0,                                // delay (0 secs)
            30,                              // ttr (30 secs)
            $mode,                          // mode of running
        );
    }
}
