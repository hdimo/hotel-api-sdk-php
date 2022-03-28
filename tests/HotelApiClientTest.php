<?php

/**
 * #%L
 * hotel-api-sdk
 * %%
 * Copyright (C) 2015 HOTELBEDS, S.L.U.
 * %%
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 2.1 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Lesser Public License for more details.
 *
 * You should have received a copy of the GNU General Lesser Public
 * License along with this program.  If not, see
 * <http://www.gnu.org/licenses/lgpl-2.1.html>.
 * #L%
 */

namespace hotelbeds\hotel_api_sdk\Tests;

use DateTime;
use hotelbeds\hotel_api_sdk\helpers\Availability;
use hotelbeds\hotel_api_sdk\helpers\Booking;
use hotelbeds\hotel_api_sdk\helpers\CheckRate;
use hotelbeds\hotel_api_sdk\HotelApiClient;
use hotelbeds\hotel_api_sdk\messages\BookingConfirmRS;
use hotelbeds\hotel_api_sdk\messages\CheckRateRS;
use hotelbeds\hotel_api_sdk\model\BookingRoom;
use hotelbeds\hotel_api_sdk\model\Destination;
use hotelbeds\hotel_api_sdk\model\Holder;
use hotelbeds\hotel_api_sdk\model\Occupancy;
use hotelbeds\hotel_api_sdk\model\Pax;
use hotelbeds\hotel_api_sdk\model\PaymentData;
use hotelbeds\hotel_api_sdk\model\Rate;
use hotelbeds\hotel_api_sdk\model\Stay;
use hotelbeds\hotel_api_sdk\types\ApiVersion;
use hotelbeds\hotel_api_sdk\types\ApiVersions;
use hotelbeds\hotel_api_sdk\messages\AvailabilityRS;
use hotelbeds\hotel_api_sdk\messages\BookingListRS;
use hotelbeds\hotel_api_sdk\types\HotelSDKException;
use Laminas\Config\Reader\Ini;
use PHPUnit\Framework\TestCase;

class HotelApiClientTest extends TestCase
{
    private $apiClient;

    public function setUp() : void
    {
        $reader = new Ini();
        $config = $reader->fromFile(__DIR__ . '/HotelApiClient.ini');
        $cfgApi = $config["apiclient"];

        $this->apiClient = new HotelApiClient(
            $cfgApi["url"],
            $cfgApi["apikey"],
            $cfgApi["sharedsecret"],
            new ApiVersion(ApiVersions::V1_0),
            $cfgApi["timeout"]
        );
    }

    /**
     * API Status Method test
     */

    public function testStatus()
    {
        $this->assertEquals($this->apiClient->status()->status, "OK");
    }

    /**
     * API Availability method test
     *
     * @depends testStatus
     */

    public function testAvailRQ()
    {
        $rqData = new Availability();
        $rqData->stay = new Stay(
            DateTime::createFromFormat("Y-m-d", "2022-06-01"),
            DateTime::createFromFormat("Y-m-d", "2022-06-10"));

        $rqData->destination = new Destination("PMI");
        $occupancy = new Occupancy();
        $occupancy->adults = 2;
        $occupancy->children = 1;
        $occupancy->rooms = 1;

        $occupancy->paxes = [new Pax(Pax::AD, 30, "Miquel", "Fiol"), new Pax(Pax::AD, 27, "Margalida", "Soberats"), new Pax(Pax::CH, 8, "Josep", "Fiol")];
        $rqData->occupancies = [$occupancy];
        $this->assertTrue(true);
        return $this->apiClient->availability($rqData);
    }

    /**
     * Testing AvailabilityRS results of Availability method
     *
     * @depends testAvailRQ
     */

    public function testAvailRS(AvailabilityRS $availRS)
    {
        $firstRate = "";
        // Check is response is empty or not
        $this->assertFalse($availRS->isEmpty(), "Response is empty!");

        // Check some fields of response
        // Iterate response hotels, rooms, rates...

        foreach ($availRS->hotels->iterator() as $hotelCode => $hotelData) {
            $this->assertNotEmpty($hotelData->name);

            foreach ($hotelData->iterator() as $roomCode => $roomData) {
                $this->assertNotEmpty($roomData->code);

                foreach ($roomData->rateIterator() as $rateKey => $rateData) {
                    $firstRate = $rateData;

                    $this->assertNotEmpty($rateData->net);
                    $this->assertNotEmpty($rateData->allotment);
                    $this->assertNotEmpty($rateData->boardCode);

                    // Check cancellation policies
                    foreach ($rateData->cancellationPoliciesIterator() as $policyKey => $policyData) {
                        $this->assertNotEmpty($policyData->amount);
                        $this->assertNotEmpty($policyData->from);
                    }

                    // Check taxes
                    $taxes = $rateData->getTaxes();
                    foreach ($taxes->iterator() as $tax) {
                        //print_r($tax);
                        //$this->assertNotEmpty($tax->type);
                    }

                    // Promotions
                    foreach ($rateData->promotionsIterator() as $promoCode => $promoData) {
                        $this->assertNotEmpty($promoData->name);
                    }
                }

            }
        }

        return $firstRate;
    }

    /**
     * API CheckRate Method test using first ratekey of availiability result test
     *
     * @depends testAvailRS
     */

    public function testCheckRate(Rate $firstRate)
    {
        $this->assertNotEmpty($firstRate->rateKey);
        $this->assertRegExp("/^[0-9]{8}|[0-9]{8}/", $firstRate->rateKey);

        $rqCheck = new CheckRate();
        $rqCheck->rooms = [["rateKey" => $firstRate->rateKey]];

        return $this->apiClient->checkRate($rqCheck);
    }

    /**
     * @depends testCheckRate
     */

    public function testCheckRateRS(CheckRateRS $checkRS)
    {
        $this->assertNotEmpty($checkRS->hotel->totalNet);
        $this->assertNotEmpty($checkRS->hotel->totalSellingRate);
    }


    /**
     * @depends testCheckRate
     */

    public function testBookingConfirm(CheckRateRS $checkRS)
    {
        $rqBookingConfirm = new Booking();
        $rqBookingConfirm->holder = new Holder("Tomeu TEST", "Capo TEST");

        // Use this iterator for multiple pax distributions, this example have one only pax distribution.

        $paxes = [new Pax(Pax::AD, 30, "Miquel", "Fiol", 1), new Pax(Pax::AD, 27, "Margalida", "Soberats", 1), new Pax(Pax::CH, 8, "Josep", "Fiol", 1)];
        $bookRooms = [];
        $atWeb = false;
        foreach ($checkRS->hotel->iterator() as $roomCode => $roomData) {
            if ($roomData->rates[0]["rateType"] !== "BOOKABLE")
                continue;

            $bookingRoom = new BookingRoom($roomData->rates[0]["rateKey"]);
            $bookingRoom->paxes = $paxes;
            $bookRooms[] = $bookingRoom;

            $atWeb = ($roomData->rates[0]["paymentType"] === "AT_WEB");
        }

        // Check all bookable rooms are inserted for confirmation.

        $this->assertNotEmpty($bookRooms);
        $rqBookingConfirm->rooms = $bookRooms;

        // Define payment data for booking confirmation
        $rqBookingConfirm->clientReference = "PHP_TEST_2";
        if (!$atWeb) {
            $rqBookingConfirm->paymentData = new PaymentData();

            $rqBookingConfirm->paymentData->paymentCard = [
                "cardType" => "VI",
                "cardNumber" => "4444333322221111",
                "cardHolderName" => "AUTHORISED",
                "expiryDate" => "0620",
                "cardCVC" => "123"
            ];

            $rqBookingConfirm->paymentData->contactData = [
                "email" => "integration@test.com",
                "phoneNumber" => "654654654"
            ];
        }

        try {
            $confirmRS = $this->apiClient->bookingConfirm($rqBookingConfirm);
            return $confirmRS;
        } catch (HotelSDKException $e) {
            echo "\n" . $e->getMessage() . "\n";
            echo "\n" . $this->apiClient->getLastRequest()->getContent() . "\n";
            $this->fail($e->getMessage());
        }

        return null;
    }

    /**
     * @depends testBookingConfirm
     */
    public function testBookingRS(BookingConfirmRS $bookingRS)
    {
        $this->assertNotEmpty($bookingRS->booking);
    }


}
