<?php

namespace App\Command;

use App\Repository\BalanceRepository;
use Clue\React\Stdio\Stdio;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFakeAccountsCommand extends Command
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Stdio
     */
    private $stdio;

    /**
     * @var BalanceRepository
     */
    private $balanceRepository;

    public function __construct(LoopInterface $loop, BalanceRepository $balanceRepository, Stdio $stdio)
    {
        parent::__construct(null);

        $this->loop = $loop;
        $this->stdio = $stdio;
        $this->balanceRepository = $balanceRepository;
    }

    protected function configure()
    {
        $this
            ->setName('app:generate-fake-users')
            ->addOption(
                'amount',
                null,
                InputOption::VALUE_OPTIONAL,
                'Amount of users, which will be generated',
                1000
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $amountFakeUsers = (int) $input->getOption('amount');

        $this->generateClients($amountFakeUsers);

        $this->loop->run();
    }

    private function generateClients(int $amount, int $alreadyGenerated = 0)
    {
        $this->stdio->write(sprintf("\rAmount generated users: %d", $alreadyGenerated));

        if ($amount === $alreadyGenerated) {
            return $this->loop->stop();
        }

        $balance = strval(mt_rand(0, 1000000));

        $this
            ->balanceRepository
            ->createWithNextAccountId($balance)
            ->then(function () use ($amount, $alreadyGenerated) {
                $this->generateClients($amount, $alreadyGenerated + 1);
            })
            ->done()
        ;
    }
}
