<?php

namespace BullPublisher;

use Ramsey\Uuid\Uuid;
use Predis\Client;
use Mockery;
use PHPUnit\Framework\TestCase;
use BullPublisher\BullPublisher;

class BullPublisherTest extends TestCase
{
    /**
     * @covers BullPublisher\BullPublisher::__construct
     */
    public function testBullPublisherCanBeInstanciated()
    {
        $bullPublisher = new BullPublisher();
        $this->assertInstanceOf(BullPublisher::class, $bullPublisher);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
