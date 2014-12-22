<?php

namespace SuperTowers\QueueBundle\Job;

use SuperTowers\QueueBundle\Core\QueueJob;
use SuperTowers\QueueBundle\Exception\DelayException;

/**
 * This is a simple example job.
 *
 * @category QueueBundle
 * @package  Job
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class ExampleJob extends QueueJob
{
    protected function run(array $data)
    {
        // echo 'Simulating a simple job: ' . json_encode($data) . "\n";
        if (rand(0, 90) === 0) {
            throw new DelayException();
        }
    }
}
