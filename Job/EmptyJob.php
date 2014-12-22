<?php

namespace SuperTowers\QueueBundle\Job;

use SuperTowers\QueueBundle\Core\QueueJob;

/**
 * This is the simplest job can be run.
 *
 * It can be used for emptying existant queues without restarting the service,
 * or as a boilerplate for other jobs.
 *
 * @category QueueBundle
 * @package  Job
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class EmptyJob extends QueueJob
{
    protected function run(array $data)
    {
        // simply do NOTHING
    }
}
