<?php

namespace SuperTowers\QueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for recovering buried and waiting jobs.
 *
 * @category QueueBundle
 * @package  Command
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueueNecromancerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('queues:necromancer')
            ->setDescription('Job for revive buried jobs in a specific Queue')
            ->addArgument('tube', InputArgument::REQUIRED, 'Tube to revive elements')
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount to revive')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tube = $input->getArgument('tube');
        $amount = (int) $input->getArgument('amount');

        $consumer = $this->getContainer()->get('queue.consumer');
        $jobs = $consumer->kick($tube, $amount);
        if ($jobs > 0) {
            $output->write("Some jobs ($jobs) where kicked out!");
        } else {
            $output->write("No jobs where kicked out!");
        }

        $output->writeln("");
    }
}
