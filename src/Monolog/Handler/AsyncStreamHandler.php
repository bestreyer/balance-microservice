<?php

namespace App\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use React\EventLoop\LoopInterface;
use React\Stream\WritableResourceStream;

class AsyncStreamHandler extends AbstractProcessingHandler
{
    /**
     * @var WritableResourceStream
     */
    private $stream;

    /**
     * AsyncStreamHandler constructor.
     *
     * @param LoopInterface $loop
     * @param string        $path
     * @param int           $level
     * @param bool          $bubble
     */
    public function __construct(LoopInterface $loop, string $path, $level = Logger::DEBUG, $bubble = true)
    {
        $this->stream = new WritableResourceStream(fopen($path, 'a+'), $loop);
        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     */
    protected function write(array $record)
    {
        $this->stream->write($record['formatted']);
    }
}
