<?php

namespace Ouarea\Lpr\Cheantong;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

/**
 * Lpr notify service implemented with Cheantong's api
 */
class Service
{
    /**
     * http client
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $server;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $branchNo;

    /**
     * @var string
     */
    private $key;

    /**
     * @param $server                       ip and port of server
     * @param $from                         where query from
     * @param $branchNo                     number of branch
     * @param ClientInterface|null $client  client used to sending http request
     */
    public function __construct($server, $from, $branchNo, $key, ClientInterface $client = null)
    {
        $this->server   = $server;
        $this->from     = $from;
        $this->branchNo = $branchNo;
        $this->key      = $key;

        $this->client = $client ?: $this->createDefaultHttpClient();
    }

    /**
     * Query cars in dealer
     *
     * @param $startTime  format: Y-m-d H:i:s
     * @param $endTime    format: Y-m-d H:i:s
     * @return array      array of cars, keys taken:
     *                     - numno      (川A00000)
     *                     - carno      (川A00000)
     *                     - entertime  (2017-09-01 00:00:00)
     *                     - parkposi   (A)
     * @throws \Exception
     */
    public function queryCarsInDealer($startTime, $endTime)
    {
        $timestamp = $this->getTimestamp();
        $nonce     = $this->getNonce();
        $data      = [
            'credentialtype' => "1",
            'starttime'      => $startTime,
            'endtime'        => $endTime,
        ];;
        $sign      = $this->getSignature($this->from, $timestamp, $nonce, $data);

        $response = $this->client->request('POST', $this->server . '/api.aspx/park.in.info',
                                           [RequestOptions::FORM_PARAMS => [
                                               'from'      => $this->from,
                                               'timestamp' => $timestamp,
                                               'nonce'     => $nonce,
                                               'branchno'  => $this->branchNo,
                                               'data'      => $data,
                                               'sign'      => $sign,
                                           ]]);

        return $this->parseResponse($response);
    }

    /**
     * Query cars out dealer before
     *
     * @param $startTime  format: Y-m-d H:i:s, include given time
     * @param $endTime    format: Y-m-d H:i:s, include given time
     * @return array      array of cars, keys taken:
     *                     - numno      (川A00000)
     *                     - carno      (川A00000)
     *                     - entertime  (2017-09-01 00:00:00)
     *                     - exittime   (2017-09-02 00:00:00)
     * @throws \Exception
     */
    public function queryCarsOutDealer($startTime, $endTime)
    {
        $timestamp = $this->getTimestamp();
        $nonce     = $this->getNonce();
        $data      = [
            'credentialtype' => "1",
            'starttime'      => $startTime,
            'endtime'        => $endTime,
        ];;
        $sign      = $this->getSignature($this->from, $timestamp, $nonce, $data);

        $response = $this->client->request('POST', $this->server . '/api.aspx/park.out.info',
            [RequestOptions::FORM_PARAMS => [
                'from'      => $this->from,
                'timestamp' => $timestamp,
                'nonce'     => $nonce,
                'branchno'  => $this->branchNo,
                'data'      => $data,
                'sign'      => $sign,
            ]]);

        return $this->parseResponse($response);
    }

    private function parseResponse(Response $response)
    {
        /* @var $response \GuzzleHttp\Psr7\Response */
        if ($response->getStatusCode() != 200) {
            throw new \Exception('店面服务器异常');
        }

        $responseContent = $response->getBody()->getContents();

        $responseData = json_decode($responseContent, true);
        if (!$responseData['status'] || 200 != $responseData['code']) {
            throw new \Exception($responseData['message']);
        }

        return $responseData['data'];
    }

    private function getNonce()
    {
        return strval(rand(0, 99));
    }

    private function getTimestamp()
    {
        return date('Y-m-d H:i:s');
    }


    private function getSignature($from, $timestamp, $nonce, array $data)
    {
        return $this->signature($this->morphPendingSign($from, $timestamp, $nonce, $data), $this->key);
    }

    private function morphPendingSign($from, $timestamp, $nonce, array $data)
    {
        $values = [
            $from, $timestamp, $nonce,
        ];

        sort($values);

        $sortStr = '';
        foreach ($values as $value) {
            $sortStr .= $value;
        }

        return $sortStr . json_encode($data);
    }

    private function signature($src, $key) {
        if (function_exists('hash_hmac')) {
            return base64_encode(hash_hmac("sha1", $src, $key, true));
        }

        $blocksize = 64;
        $hashfunc = 'sha1';
        if (strlen($key) > $blocksize) {
            $key = pack('H*', $hashfunc($key));
        }
        $key  = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack(
            'H*', $hashfunc(
                ($key ^ $opad) . pack(
                    'H*', $hashfunc(
                        ($key ^ $ipad) . $src
                    )
                )
            )
        );

        return base64_encode($hmac);
    }

    /**
     * create default http client
     *
     * @param array $config        Client configuration settings. See \GuzzleHttp\Client::__construct()
     * @return \GuzzleHttp\Client
     */
    private function createDefaultHttpClient(array $config = [])
    {
        return new Client($config);
    }
}