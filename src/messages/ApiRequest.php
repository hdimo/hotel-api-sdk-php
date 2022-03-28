<?php
/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 10/27/2015
 * Time: 3:13 AM
 */

namespace hotelbeds\hotel_api_sdk\messages;

use hotelbeds\hotel_api_sdk\helpers\ApiHelper;
use hotelbeds\hotel_api_sdk\types\ApiUri;
use Laminas\Http\Request;
use Laminas\Uri\Http;
use Laminas\Stdlib\Parameters;

abstract class ApiRequest implements ApiCallTypes
{
    protected $request, $baseUri;
    private $dataRQ;

    public function __construct(ApiUri $baseUri, $operation)
    {
        $this->request = new Request();
        $this->baseUri = new Http($baseUri);
        $this->baseUri->setPath($baseUri->getPath()."/".$operation);
    }

    protected function setDataRequest(ApiHelper $dataRQ)
    {
        $this->dataRQ = $dataRQ;
    }

    public function prepare($apiKey, $signature)
    {
        if (empty($apiKey) || empty($signature))
            throw new \InvalidArgumentException("HotelApiClient cannot be created without specifying an API key and signature");

        $this->request->setUri($this->baseUri);
        $this->request->getHeaders()->addHeaders([
            'Api-Key' => $apiKey,
            'X-Signature' => $signature,
            'Accept' => 'application/json'
        ]);

        if (!empty($this->dataRQ)) {
            switch($this->request->getMethod()) {
                case Request::METHOD_GET:
                        $this->request->setQuery(new Parameters($this->dataRQ->toArray()));
                        break;
                case Request::METHOD_POST:
                        $this->request->getHeaders()->addHeaders(['Content-Type' => 'application/json']);
                        $this->request->setContent("".$this->dataRQ);
            }
        }

        return $this->request;
    }
}
