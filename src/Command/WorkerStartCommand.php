<?php

namespace App\Command;

use App\Messenger\Receiver\RabbitMQReceiver;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerStartCommand extends Command
{
    /**
     * @var LoopInterface
     */
    private $loop;
    /**
     * @var RabbitMQReceiver
     */
    private $newMessageReceiver;
    /**
     * @var RabbitMQReceiver
     */
    private $tryAgainMessageReceiver;

    public function __construct(
        LoopInterface $loop,
        RabbitMQReceiver $newMessageReceiver,
        RabbitMQReceiver $tryAgainMessageReceiver
    ) {
        parent::__construct(null);

        $this->loop = $loop;
        $this->newMessageReceiver = $newMessageReceiver;
        $this->tryAgainMessageReceiver = $tryAgainMessageReceiver;
    }

    protected function configure()
    {
        $this
            ->setName('app:worker:start')
            ->addOption('delete-old-locks', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->newMessageReceiver->receive();
        $this->tryAgainMessageReceiver->receive();

        $this->loop->run();
    }
}
