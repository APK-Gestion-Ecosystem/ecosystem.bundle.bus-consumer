<?php

namespace Ecosystem\BusConsumerBundle\Command;

use Ecosystem\BusConsumerBundle\Service\ConsumerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

#[AsCommand(name: 'ecosystem:bus:consume')]
class ConsumeCommand extends Command
{
    #[Required]
    public ConsumerService $consumerService;

    #[Required]
    public LoggerInterface $logger;

    protected function configure(): void
    {
        $this->addArgument('queue', InputArgument::REQUIRED, 'Queue name to consume');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = strval($input->getArgument('queue'));
        $start = time();

        do {
            $this->consumerService->receive($queue);
        } while (time() - $start < 600);

        return Command::SUCCESS;
    }
}
