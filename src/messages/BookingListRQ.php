<?php
/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 11/17/2015
 * Time: 7:14 PM
 */

namespace hotelbeds\hotel_api_sdk\messages;

use hotelbeds\hotel_api_sdk\helpers\BookingList;
use hotelbeds\hotel_api_sdk\types\ApiUri;
use Laminas\Http\Request;

class BookingListRQ extends ApiRequest
{
    public function __construct(ApiUri $baseUri, BookingList $bookDataRQ)
    {
        parent::__construct($baseUri, self::BOOKING);
        $this->request->setMethod(Request::METHOD_GET);
        $this->setDataRequest($bookDataRQ);
    }
}