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
        $pid = getmypid();
        $queue = strval($input->getArgument('queue'));
        $logger = $this->logger;
        $logger->info(sprintf('[%d] Starting to consume queue: %s', $pid, $queue));

        $start = time();
        pcntl_signal(SIGTERM, function () use ($logger, $pid, &$start) {
            $start = 0;
            $logger->logger->info(sprintf('[%d] SIGTERM received.', $pid));
        });

        do {
            $this->consumerService->receive($queue);
        } while (time() - $start < 600);

        $logger->info(sprintf('[%d] Finished to consume queue: %s', $pid, $queue));

        return Command::SUCCESS;
    }
}
