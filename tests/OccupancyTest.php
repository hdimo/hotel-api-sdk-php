<?php
namespace hotelbeds\hotel_api_sdk\Tests;


use \hotelbeds\hotel_api_sdk\model\Occupancy;
use \hotelbeds\hotel_api_sdk\model\Pax;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertTrue;

class OccupancyTest extends TestCase
{
    private $occupancy;
    public function setUp() : void
    {
        $this->occupancy = new Occupancy();
        $this->occupancy->adults = 1;
        $this->occupancy->children = 1;
        $this->occupancy->rooms = 1;
        $this->occupancy->paxes = [ new Pax(Pax::AD, 30), new Pax(Pax::CH, 8) ];
    }

    public function testConf()
    {
        $this->assertEquals($this->occupancy->adults, 1);
        $this->assertEquals($this->occupancy->children, 1);
        $this->assertEquals($this->occupancy->rooms, 1);
    }

    public function testPaxes()
    {
        $this->assertCount(2, $this->occupancy->paxes);
    }

    /**
     * @depends testConf
     * @depends testPaxes
     */
    public function testJson()
    {
       fwrite(STDERR, json_encode($this->occupancy->toArray())."\n");
        assertTrue(true);
    }
}
