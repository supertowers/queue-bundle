<?php

namespace SuperTowers\QueueBundle\Core;

use \Pheanstalk\Pheanstalk;
use \SuperTowers\QueueBundle\ContainerAware;
use \SuperTowers\QueueBundle\Exception\UnableToGatherCallbackForJobException;

use \ErrorException;
use \Exception;

/**
 * Queue Consumer class definition.
 *
 * The consumer is responsible of listening the queues daemon and forking for
 * running the processes.
 *
 * @category QueueBundle
 * @package  Core
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueueConsumer
{
    protected $countWorkers = 1;

    private static $isErrorHandlerBinded = false;

    private $connection;

    private $connectionOptions = array(
        'host' => '127.0.0.1',
        'port' => 11300,
    );

    const DEFAULT_TIMEOUT = 1;
    const BUCKET_SIZE = 40;

    const JOBS_PER_CHILD = 100;

    private $queueTimer;

    const STATUS_OK = 1;
    const STATUS_DELAY = 2;
    const STATUS_ERROR = 3;
    const STATUS_EMPTY = 5;

    const DEFAULT_JOB_DELAY = 30;

    private $configObject;

    private $ok = 0;
    private $delay = 0;

    protected $jobsStarted = 0;

    protected $workers = array();
    protected $currentJobsCount = 0;

    protected $signalQueue = array();
    protected $parentPid;

    private $forks = 0;

    private $listener;

    private $logger;

    private $parentPID;

    private $stats = array(
        self::STATUS_OK    => 0,
        self::STATUS_DELAY => 0,
        self::STATUS_ERROR => 0,
        self::STATUS_EMPTY => 0,
    );

    //
    // ERROR HANDLING
    //
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }


    public function __construct(array $config, QueueConfig $configObject, QueueLogger $logger = null)
    {
        $this->setConnectionOptions($config[0], $config[1]);
        $this->configObject = $configObject;

        if ($logger) {
            $this->setLogger($logger);
        }
    }

    public function setCountWorkers($count = 1)
    {
        $this->countWorkers = $count;
    }

    private function setConnectionOptions($host, $port)
    {
        $this->connectionOptions = array(
            'host' => $host,
            'port' => $port,
        );
    }

    public function setLogger(QueueLogger $logger)
    {
        $this->logger = $logger;
    }

    protected function tubeWatchOnly($driver, $watchedNames)
    {
        if (! is_array($watchedNames)) {
            $watchedNames = array($watchedNames);
        }

        // special case for selecting all the tubes
        if ($watchedNames[0] === '%') {
            $watchedNames = $driver->listTubes();
        }

        foreach ($watchedNames as $watchedName) {
            $driver->watch($watchedName);
        }

        foreach ($driver->listTubesWatched() as $tube) {
            if (! in_array($tube, $watchedNames)) {
                $driver->ignore($tube);
            }
        }
    }

    /**
     * Watches several tubes in order to dequeue jobs and process them.
     *
     * @author Pablo Lopez Torres <pablolopeztorres@gmail.com>
     * @param string|array $tubeNames Array of strings or a single string with the name of the tubes to watch.
     * @param integer $workers Number of concurrent workers to fork.
     * @param integer $elements Maximum number of elements to process before ending.
     * @return void
     */
    public function watch($tubeNames, $countWorkers, $elements)
    {
        // forking config
        $this->parentPID = getmypid();
        pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));
        $this->setCountWorkers($countWorkers);

        // jobs per bucket = 2

        $this->stats['startTime'] = microtime(true);

        // assure only one execution
        if (! self::$isErrorHandlerBinded) {
            set_error_handler(array(__CLASS__, "errorHandler"));
            self::$isErrorHandlerBinded = true;
        }

        // set config values
        $this->queueTimer = $this->getConfigValue('default-speed');

        while ($elements > 0) {
            $jobsToProcess = min(static::JOBS_PER_CHILD, $elements);
            $this->processJobBucket($jobsToProcess, $tubeNames);
            $elements -= $jobsToProcess;
        }

        // Wait for child processes to finish before exiting here
        while (count($this->workers)) {
            sleep(.4);
            pcntl_signal_dispatch();
        }
    }

    private function processJobBucket($countJobs, $tubeNames)
    {
        while (count($this->workers) >= $this->countWorkers) {
            sleep(2);
            pcntl_signal_dispatch();
        }

        $this->forks++;
        $pid = pcntl_fork();

        if ($pid === -1) {
            error_log('Unable to fork process. Exiting...');
            return null;
        } elseif ($pid) {
            // PARENT
            $this->parentProcessJob($pid, $countJobs);
        } else {
            // CHILD
            $driver = $this->createDriver();
            $this->tubeWatchOnly($driver, $tubeNames);

            $i = 0;
            while ($i < $countJobs) {
                $job = $driver->reserve(self::DEFAULT_TIMEOUT);
                $this->childProcessJob($pid, $job);
                $i++;
            }
            exit(0);
        }

    }


    private function logException(Exception $e)
    {
        // log exception
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();

        if ($this->logger) {
            $this->logger->error(">>>>>>>>>>>>>>>>");
            $this->logger->error("$message ($file:$line)");
            foreach ($e->getTrace() as $traceRow) {
                $traceClass = isset($traceRow['class']) ? $traceRow['class'] : '';
                $traceMethod = isset($traceRow['function']) ? $traceRow['function'] : '';
                $traceFile = isset($traceRow['file']) ? $traceRow['file'] : '';
                $traceLine = isset($traceRow['line']) ? $traceRow['line'] : '';
                $this->logger->error(" > $traceClass->$traceMethod ($traceFile:$traceLine)");
            }
            $this->logger->error("<<<<<<<<<<<<<<<<");
        } else {
            print("[ ERROR ] $message ($file:$line)\n");
        }
    }

    private function handleJobFinished($job, $status)
    {
        $driver = $this->getDriver();

        switch ($status) {
            case self::STATUS_OK:
                $this->ok++;
                $driver->delete($job);
                break;
            case self::STATUS_DELAY:
                $this->delay = 1;
                $jobStats = $driver->statsJob($job);
                $driver->release($job, $jobStats['pri'], self::DEFAULT_JOB_DELAY);
                break;
            case self::STATUS_ERROR:
                $this->delay = $this->getConfigValue('error-produce-delays');
                $jobStats = $driver->statsJob($job);
                $driver->bury($job, $jobStats['pri']);
                break;
            case self::STATUS_EMPTY:
                // do not do anything
                break;
            default:
                throw new Exception('unable to detect output: ' . json_encode($status));
        }

        $this->handleStatus($job ? $job->getId() : null, $status);
    }

    /**
     * @param null|integer $status
     */
    private function handleStatus($jobId, $status)
    {
        switch ($status) {
            case self::STATUS_OK:
                $this->notify('JOB:OK', array('id' => $jobId));
                break;
            case self::STATUS_DELAY:
                $this->notify('JOB:DELAY', array('id' => $jobId));
                break;
            case self::STATUS_ERROR:
                $this->notify('JOB:ERROR', array('id' => $jobId));
                break;
            case self::STATUS_EMPTY:
                $this->notify('JOB:EMPTY');
                break;
            default:
                throw new Exception('unable to detect output: ' . json_encode($status));
        }
    }

    private function parentProcessJob($pid, $countJobs)
    {
        // PARENT PROCESS
        $this->workers[$pid] = $countJobs;
        $this->currentJobsCount += $countJobs;

        if (isset($this->signalQueue[$pid])) {
            echo "found $pid in the signal queue, processing it now \n";
            $this->childSignalHandler(SIGCHLD, $pid, $this->signalQueue[$pid]);
            unset($this->signalQueue[$pid]);
        }
    }

    private function childProcessJob($pid, $job)
    {
        // CHILD PROCESS
        // catch them all!!!!!!!
        try {
            ob_start();

            $jobName = null;

            if ($job === false) {
                $status = self::STATUS_EMPTY;
            } else {
                list($jobName, $jobData) = $this->unserializeJob($job->getData());

                // real execution of the job (could throw UnableToGatherCallbackForJobException)
                $callback = $this->getConfig()->getCallback($jobName);
                $status = call_user_func($callback, $jobData);
            }

            $output = ob_get_contents();
            ob_end_clean();

            if ($this->logger && strlen($output) > 0) {
                $this->logger->info($output);
            }

        } catch (Exception $e) {
            $this->logException($e);

            // mark job to be buried
            $status = self::STATUS_ERROR;
        }

        $this->handleJobFinished($job, $status);
    }


    /**
     * Executes a dequeued Job
     *
     * It should be called only from itself or from the QueueManager running a Sync job.
     *
     * @author Pablo Lopez Torres <pablolopeztorres@gmail.com>
     * @param mixed $job
     * @return void
     */
    public function processJob($job)
    {
        list($jobName, $jobData) = $this->unserializeJob($job->getData());
        $callback = $this->getConfig()->getCallback($jobName);
        call_user_func($callback, $jobData);
    }

    private function serializeJob($jobName, $parameters, $priority, $delay, $ttr)
    {
        return json_encode(array($jobName, $parameters, $priority, $delay, $ttr));
    }

    private function unserializeJob($data)
    {
        return json_decode($data, true);
    }

    public function kick($tubeName, $elements)
    {
        $driver = $this->getDriver();
        $driver->useTube($tubeName);
        return $driver->kick($elements);
    }

    /**
     * getDriver
     *
     * @author Pablo Lopez Torres <pablolopeztorres@gmail.com>
     * @return Pheanstalk
     * @todo pablo - 2014-12-18 : abstract this
     */
    protected function getDriver()
    {
        if ($this->connection === null) {
            $this->createDriver();
        }
        return $this->connection;
    }

    protected function createDriver()
    {
        $this->connection = new Pheanstalk(
            $this->connectionOptions['host'],
            $this->connectionOptions['port']
        );
        return $this->connection;
    }

    protected function getConfig()
    {
        return $this->configObject;
    }

    /**
     * @param string $key
     */
    protected function getConfigValue($key)
    {
        return $this->configObject[$key];
    }


    public function registerListener($listener)
    {
        $this->listener = $listener;
    }

    /**
     * @param string $event
     */
    public function notify($event, $data = array())
    {
        if ($this->listener !== null) {
            $this->listener->trigger($event, $data);
        }
    }


    //
    // VELOCITY RELATED
    //
    private function brake()
    {
        $this->queueTimer *= $this->getConfigValue('brake-multiplier');

        if ($this->queueTimer > $this->getConfigValue('min-speed')) {
            $this->queueTimer = $this->getConfigValue('min-speed');
        }
    }

    private function accelerate()
    {
        $this->queueTimer /= $this->getConfigValue('accelerator-multiplier');

        if ($this->queueTimer < $this->getConfigValue('max-speed')) {
            $this->queueTimer = $this->getConfigValue('max-speed');
        }
    }


    public function childSignalHandler($signo, $pid = null, $status = null)
    {
        //If no pid is provided, that means we're getting the signal from the system.  Let's fig
        //which child process ended
        if (!$pid) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        //Make sure we get all of the exited children
        while ($pid > 0) {
            if ($pid && isset($this->workers[$pid])) {
                $this->currentJobsCount -= $this->workers[$pid];
                unset($this->workers[$pid]);

            } elseif ($pid) {
                //Oh no, our job has finished before this parent process could even note that it
                //Let's make note of it and handle it when the parent process is ready for it
                echo "..... Adding $pid to the signal queue ..... \n";
                $this->signalQueue[$pid] = $status;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        return true;
    }
}
