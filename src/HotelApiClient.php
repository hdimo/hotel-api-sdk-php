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

namespace hotelbeds\hotel_api_sdk;

use hotelbeds\hotel_api_sdk\messages\AvailabilityRS;
use hotelbeds\hotel_api_sdk\messages\StatusRS;

use hotelbeds\hotel_api_sdk\messages\CheckRateRS;
use hotelbeds\hotel_api_sdk\messages\BookingConfirmRS;
use hotelbeds\hotel_api_sdk\messages\BookingListRS;

use hotelbeds\hotel_api_sdk\model\AuditData;
use hotelbeds\hotel_api_sdk\types\ApiVersion;
use hotelbeds\hotel_api_sdk\types\HotelSDKException;
use hotelbeds\hotel_api_sdk\messages\ApiRequest;

use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Uri\UriFactory;

/**
 * Class HotelApiClient
 * @package hotelbeds\hotel_api_sdk
 * @method StatusRS status Get status of hotel-api service
 * @method AvailabilityRS availability Do availability accomodation request
 * @method CheckRateRS checkRate Check different room rates for booking
 * @method BookingConfirmRS bookingConfirm Method allows confirmation of the rate keys selected.  There is an option of confirming more than one rate key for the same hotel/room/board.
 * @method BookingListRS bookingList To get a list of bookings
 *
 */
class HotelApiClient
{
    private $apiKey, $signature;
    private $httpClient, $apiUri;
    private $lastRequest;

    public function __construct($url, $apiKey, $sharedSecret, ApiVersion $version, $timeout=30)
    {
        $this->lastRequest = null;

        $this->apiKey = trim($apiKey);
        $this->signature = hash("sha256", $apiKey.trim($sharedSecret).time());
        $this->httpClient = new Client();
        $this->httpClient->setOptions(["timeout" => $timeout]);

        UriFactory::registerScheme("https","hotelbeds\\hotel_api_sdk\\types\\ApiUri");
        $this->apiUri = UriFactory::factory($url);
        $this->apiUri->prepare($version);
    }


    /**
     * @param $sdkMethod string Method request name.
     * @param $args array only specify a ApiHelper class type for encapsulate request arguments
     * @return mixed Class of response. Each call type returns response class: For example AvailabilityRQ returns AvailabilityRS
     * @throws HotelSDKException Specific exception of call
     * @throws \Exception General exception for not implemented requested method
     */

    public function __call($sdkMethod, $args)
    {
        $sdkClassRQ = "hotelbeds\\hotel_api_sdk\\messages\\".$sdkMethod."RQ";
        $sdkClassRS = "hotelbeds\\hotel_api_sdk\\messages\\".$sdkMethod."RS";

        if (!class_exists($sdkClassRQ) && !class_exists($sdkClassRS))
            throw new \Exception("$sdkClassRQ or $sdkClassRS not implemented in SDK");


        if ($args !== null && count($args) > 0)
            $req = new $sdkClassRQ($this->apiUri, $args[0]);
        else $req = new $sdkClassRQ($this->apiUri);

        return new $sdkClassRS($this->callApi($req));
    }


    /**
     * @param ApiRequest $request API Abstract request helper for contruct request
     * @return mixed Response data into array format
     * @throws HotelSDKException
     * @throws \Exception
     */
    private function callApi(ApiRequest $request)
    {
        try {
            $this->lastRequest = $request->prepare($this->apiKey, $this->signature);
            $response = $this->httpClient->send($this->lastRequest);
        } catch (\Exception $e) {
            throw new HotelSDKException("Error accessing API: " . $e->getMessage());
        }

        if ($response->getStatusCode() === 403)
            throw new \Exception("Not authorized, review your api-key and secret!");

        if ($response->getStatusCode() !== 200) {
           $auditData = null;$message="";
           if ($response->getBody() !== null) {
                $errorResponse = \Laminas\Json\Json::decode($response->getBody(), \Laminas\Json\Json::TYPE_ARRAY);
                $auditData = new AuditData($errorResponse["auditData"]);
                $message = $errorResponse["error"]["message"];
           }
           throw new HotelSDKException($response->getReasonPhrase().". ".$message, $auditData);
        }

        return \Laminas\Json\Json::decode($response->getBody(), \Laminas\Json\Json::TYPE_ARRAY);
    }

    /**
     * @return Request getLastRequest Returns entire raw request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }
}
