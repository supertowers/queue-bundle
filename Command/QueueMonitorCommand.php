<?php

namespace SuperTowers\QueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to watch the status of the current queues.
 *
 * @category QueueBundle
 * @package  Command
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class QueueMonitorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('queues:monitor')
            ->setDescription('Command for monitoring queues')
        ;
    }

    private $values = array(' ', '.', ':', '-', '=', '*', '#', '%', '@');

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('queue.manager');

        $output->writeln("<comment>     _________________       </comment>");
        $output->writeln("<comment>   //                 \\\\   </comment>");
        $output->writeln("<comment>  //   QUEUES STATUS   \\\\  </comment>");
        $output->writeln("<comment> //_____________________\\\\ </comment>");
        $output->writeln("");

        foreach ($manager->getTubesStatus() as $tube => $tubeData) {
            $output->writeln("> $tube:");
            foreach (array('ready', 'reserved', 'delayed', 'buried') as $status) {
                $jobs = $tubeData["current-jobs-$status"];
                $output->writeln(
                    "  `- " . $status . str_repeat(' ', 12 - strlen($status)) .
                    " ::\t" . $jobs .
                    "\t" .
                    str_repeat(
                        $this->values[8],
                        min(sqrt($jobs * 10) / 9, 100)
                    ) .
                    $this->values[$jobs % 9]
                );
            }

            $output->writeln("");
        }
    }
}
