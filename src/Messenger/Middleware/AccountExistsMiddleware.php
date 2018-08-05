<?php

namespace App\Messenger\Middleware;

use App\Repository\BalanceRepository;
use function React\Promise\all;
use function React\Promise\resolve;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class AccountExistsMiddleware implements MiddlewareInterface
{
    /**
     * @var BalanceRepository
     */
    private $balanceRepository;

    /**
     * AccountExistsMiddleware constructor.
     *
     * @param BalanceRepository $balanceRepository
     */
    public function __construct(BalanceRepository $balanceRepository)
    {
        $this->balanceRepository = $balanceRepository;
    }

    /**
     * @param object $message
     *
     * @return mixed
     */
    public function handle($message, callable $next)
    {
        if (property_exists($message, 'accountId')) {
            $promiseAccount = $this->balanceRepository->exists($message->accountId);
        } else {
            $promiseAccount = resolve();
        }

        if (property_exists($message, 'toAccountId')) {
            $promiseToAccount = $this->balanceRepository->exists($message->toAccountId);
        } else {
            $promiseToAccount = resolve();
        }

        return all([$promiseAccount, $promiseToAccount])
            ->then(function ($exists) use ($message) {
                return $this->checkAccountExists($exists, $message);
            })
            ->then(function () use ($next, $message) {
                return $next($message);
            });
    }

    private function checkAccountExists($exists, $message)
    {
        $violations = [];

        if (false === $exists[0]) {
            $violations[] = new ConstraintViolation(
                'Account doesn\'t exist',
                '',
                [],
                null,
                'accountId',
                null
            );
        }

        if (false === $exists[1]) {
            $violations[] = new ConstraintViolation(
                'Account doesn\'t exist',
                '',
                [],
                null,
                'toAccountId',
                null
            );
        }

        if (count($violations) > 0) {
            throw new ValidationFailedException($message, new ConstraintViolationList($violations));
        }

        return resolve();
    }
}
