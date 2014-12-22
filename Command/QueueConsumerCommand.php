<?php

namespace SuperTowers\QueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command runner for the Queues Consumer.
 *
 * @category QueueBundle
 * @package  Command
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueueConsumerCommand extends ContainerAwareCommand
{
    const DEFAULT_WORKERS = 1;
    const DEFAULT_ELEMENTS = 10000;

    protected function configure()
    {
        $this
            ->setName('queues:consumer')
            ->setDescription('Job for consuming a specific Queue')
            ->addArgument('tubes', InputArgument::REQUIRED, 'Comma separated name of tubes to subscribe')
            ->addArgument('workers', InputArgument::OPTIONAL, 'Workers to use', self::DEFAULT_WORKERS)
            ->addArgument('elements', InputArgument::OPTIONAL, 'Elements to process', self::DEFAULT_ELEMENTS)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tubes = explode(',', $input->getArgument('tubes'));

        $workers = (int) $input->getArgument('workers');
        $elements = (int) $input->getArgument('elements');

        $listener = new QueueConsumerCliListener($output);

        $consumer = $this->getContainer()->get('queue.consumer');
        $consumer->registerListener($listener);

        $consumer->watch($tubes, $workers, $elements);

        $output->writeln('');
    }
}
