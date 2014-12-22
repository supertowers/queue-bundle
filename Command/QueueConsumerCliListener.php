<?php

namespace SuperTowers\QueueBundle\Command;

use Symfony\Component\Console\Output\OutputInterface;
use SuperTowers\QueueBundle\Core\QueueConsumer;

/**
 * Listener to write execution progress of the consumer into the console.
 *
 * @category QueueBundle
 * @package  Command
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueueConsumerCliListener
{
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function trigger($event, $data)
    {
        switch ($event) {
            case 'JOB:EMPTY':
                $this->output->write('0');
                break;
            case 'JOB:OK':
                $this->output->write('.');
                break;
            case 'JOB:DELAY':
                $this->output->write('w');
                break;
            case 'JOB:ERROR':
                $this->output->write('b');
                break;

            case 'BUCKET:FINISHED':

                // autocalculated fields
                $total = $data[QueueConsumer::STATUS_OK] +
                    $data[QueueConsumer::STATUS_DELAY] +
                    $data[QueueConsumer::STATUS_ERROR];
                $jobsPerSecond = round(1000000 / $data['timer'], 2);
                $realJobsPerSecond = round($total / (microtime(true) - $data['startTime']), 2);

                $this->output->write(sprintf(
                    " - %sp %sw %se - %s j/s %s rj/s\n",
                    $data[QueueConsumer::STATUS_OK],
                    $data[QueueConsumer::STATUS_DELAY],
                    $data[QueueConsumer::STATUS_ERROR],
                    $jobsPerSecond,
                    $realJobsPerSecond
                ));

                break;
            default:
                trigger_error("Unhandled event '$event' in QueueConsumerCliListener", E_USER_NOTICE);
                break;
        }
    }
}
