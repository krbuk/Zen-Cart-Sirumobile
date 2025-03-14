<?php
namespace Siru\Tests\API;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Siru\Signature;
use Siru\Transport\TransportInterface;

abstract class AbstractApiTest extends TestCase
{

    /**
     * @var Signature
     */
    protected $signature;

    /**
     * @var TransportInterface|MockObject
     */
    protected $transport;

    public function setUp()
    {
        $this->signature = new Signature(1, 'xooxer');
        $this->transport = $this->createMock(TransportInterface::class);
    }

}
