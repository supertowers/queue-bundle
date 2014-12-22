<?php

namespace SuperTowers\QueueBundle\Core;

/**
 * Interface that any logger should implement.
 *
 * It should be injected into the QueueConsumer.
 *
 * @category QueueBundle
 * @package  Core
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
interface QueueLogger
{
    /**
     * @return void
     */
    public function info($message);

    /**
     * @return void
     */
    public function notice($message);

    /**
     * @return void
     */
    public function warning($message);

    /**
     * @return void
     */
    public function error($message);
}
