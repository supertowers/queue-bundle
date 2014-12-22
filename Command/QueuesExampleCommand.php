<?php

namespace SuperTowers\QueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use \SuperTowers\QueueBundle\Core\QueueManager;

use \Socket_Beanstalk;

use \Exception;

/**
 * Command that enqueues some example jobs to be processed.
 *
 * @category QueueBundle
 * @package  Command
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueuesExampleCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('queues:example')
            ->setDescription('Example Job for testing queues')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('queue.manager');

        for ($i = 0; $i < 100; $i++) {
            $manager->enqueue('example1:job', array('data' => 'data'));
            $manager->enqueue('example1:job', array('data' => 'data'));
            $manager->enqueue('example1:job', array('data' => 'data'));
            $manager->enqueue('example1:job', array('data' => 'data'));
            $manager->enqueue('example2:job', array('data' => 'data'));
            $manager->enqueue('example2:job', array('data' => 'data'));
            $manager->enqueue('example3:job', array('data' => 'data'));
            $manager->enqueue('example4:job', array('data' => 'data'));
            // echo '.';
        }
        echo "\n";
    }
}
