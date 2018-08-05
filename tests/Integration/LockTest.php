<?php

namespace App\Tests\Integration;

class LockTest extends IntegrationTestCase
{
    public function testLock()
    {
        $this->lock(1, false, true);
        $this->lock(1, false, false);
    }

    public function testTwoDifferentAccountLock()
    {
        $this->lock(1, false, true);
        $this->lock(2, false, true);

        $this->unlock(1, false);
        $this->lock(1, false, true);

        $this->lock(2, false, false);
    }

    public function testSuccessUnlock()
    {
        $this->lock(1, false, true);
        $this->unlock(1, false);
        $this->lock(1, false, true);
    }

    public function testSuccessWaitConfirmationUnlock()
    {
        $this->lock(1, true, true);
        $this->unlock(1, true);
        $this->lock(1, true, true);
    }

    public function testFailedUnlock()
    {
        $this->lock(1, true, true);
        $this->unlock(1, false);
        $this->lock(1, false, false);
    }

    public function testFailedWaitConfirmationUnlock()
    {
        $this->lock(1, false, true);
        $this->unlock(1, true);
        $this->lock(1, true, false);
    }
}
