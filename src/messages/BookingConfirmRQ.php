<?php
/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 11/26/2015
 * Time: 12:12 AM
 */

namespace hotelbeds\hotel_api_sdk\messages;

use hotelbeds\hotel_api_sdk\helpers\Booking;
use hotelbeds\hotel_api_sdk\types\ApiUri;
use Laminas\Http\Request;

class BookingConfirmRQ extends ApiRequest
{
    public function __construct(ApiUri $baseUri, Booking $bookingDataRQ)
    {
        parent::__construct($baseUri, self::BOOKING);
        $this->request->setMethod(Request::METHOD_POST);
        $this->setDataRequest($bookingDataRQ);
    }
}