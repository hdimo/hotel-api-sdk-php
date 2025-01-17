<?php

/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 11/4/2015
 * Time: 9:41 PM
 */

namespace hotelbeds\hotel_api_sdk\Tests;

use DateTime;
use hotelbeds\hotel_api_sdk\model\Stay;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertTrue;


class StayTest extends TestCase
{
    private $stay;
    public function setUp() : void
    {
        $this->stay = new Stay();

        $dateIn = DateTime::createFromFormat("Y-m-d", date("Y-m-d"));
        $dateOut = DateTime::createFromFormat("Y-m-d", date("Y-m-d"));

        $this->stay->checkIn = $dateIn;
        $this->stay->checkOut = $dateOut->add(new \DateInterval('P2W'));
        $this->stay->shiftDays = 1;
        $this->stay->allowOnlyShift = false;
    }

    public function testFields()
    {
        $this->assertEquals($this->stay->shiftDays, 1);
        $this->assertFalse($this->stay->allowOnlyShift);
    }

    public function testDates()
    {
        $dateIn = DateTime::createFromFormat("Y-m-d", date("Y-m-d"));
        $dateOut =  DateTime::createFromFormat("Y-m-d", date("Y-m-d"));
        $dateOut->add(new \DateInterval('P2W'));

        $this->assertEquals($this->stay->checkIn->getTimestamp(), $dateIn->getTimestamp());
        $this->assertEquals($this->stay->checkOut->getTimestamp(), $dateOut->getTimestamp());
    }

    /**
     * @depends testFields
     * @depends testDates
     */
    public function testJson()
    {
        fwrite(STDERR, json_encode($this->stay->toArray())."\n");
        assertTrue(true);
    }
}
