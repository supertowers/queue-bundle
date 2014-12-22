<?php

namespace SuperTowers\QueueBundle\Exception;

use \Exception;

/**
 * Exception thrown when a job is dequeued and there is no callback for it.
 *
 * @category QueueBundle
 * @package  Exception
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class UnableToGatherCallbackForJobException extends Exception
{
    private $customMessage = "Unable to gather callback for job '%s'";

    public function __construct($jobName)
    {
        parent::__construct(sprintf($this->customMessage, $jobName));
    }
}
