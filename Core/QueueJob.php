<?php

namespace SuperTowers\QueueBundle\Core;

use \SuperTowers\QueueBundle\Exception\DelayException;
use \Exception;

/**
 * Base job that needs to be inherited for current jobs to run.
 *
 * @category QueueBundle
 * @package  Job
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
abstract class QueueJob
{
    abstract protected function run(array $data);

    public function process($data)
    {
        try {
            // $this->assureCpuNotBurning();
            $this->run($data);
        } catch (DelayException $e) {
            return QueueConsumer::STATUS_DELAY;
        } catch (Exception $e) {
            // @todo pablo - 2014-12-18 : LOG ERROR!!!!
            throw $e;

            return QueueConsumer::STATUS_ERROR;
        }

        return QueueConsumer::STATUS_OK;
    }

    protected function assureCpuNotBurning($load = 8)
    {
        $cpu = sys_getloadavg();
        if ($cpu[0] > $load) {
            throw new DelayException();
        }
    }
}
