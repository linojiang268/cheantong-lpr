<?php
namespace spec\Ouarea\Lpr\Cheantong;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $client)
    {
        $this->beAnInstanceOf(\Ouarea\Lpr\Cheantong\Service::class, [
            'HTTP://SERVER:PORT',
            'FROM',
            'BRANCENO',
            'KEY',
            $client,
        ]);
    }

    //=====================================
    //          Query Cars In Dealer
    //=====================================
    function it_should_have_in_dealer_cars_queried(ClientInterface $client)
    {
        $client->request('POST', Argument::any(), Argument::any())
               ->willReturn(new Response(200, [], '{"status":true,"message":null,"code":200,"data":[{"numno":"川AH529K","carno":"川AH529K","entertime":"2017-06-02 16:51:15","parkposi":"A"},{"numno":"川A07U59","carno":"川A07U59","entertime":"2017-06-02 17:04:18","parkposi":"A"}],"time":"2017-07-06 17:39:34"}'));


        $this->queryCarsInDealer('2017-05-01 00:00:00', '2017-07-01 00:00:00')->shouldReturn([
            [
                "numno"     => "川AH529K",
                "carno"     => "川AH529K",
                "entertime" => "2017-06-02 16:51:15",
                "parkposi"  => "A"
            ],
            [
                "numno"     => "川A07U59",
                "carno"     => "川A07U59",
                "entertime" => "2017-06-02 17:04:18",
                "parkposi"  => "A"
            ],
        ]);
    }

    //=====================================
    //          Query Cars Out Dealer
    //=====================================
    function it_should_have_out_dealer_cars_queried(ClientInterface $client)
    {
        $client->request('POST', Argument::any(), Argument::any())
               ->willReturn(new Response(200, [], '{"status":true,"message":null,"code":200,"data":[{"numno":"川AH529K","carno":"川AH529K","entertime":"2017-06-02 16:51:15","exittime":"2017-06-02 16:55:19"},{"numno":"川A07U59","carno":"川A07U59","entertime":"2017-06-02 17:04:18","exittime":"2017-06-02 18:06:15"}],"time":"2017-07-06 17:39:34"}'));

        $this->queryCarsInDealer('2017-05-01 00:00:00', '2017-07-01 00:00:00')->shouldReturn([
            [
                "numno"     => "川AH529K",
                "carno"     => "川AH529K",
                "entertime" => "2017-06-02 16:51:15",
                "exittime"  => "2017-06-02 16:55:19",
            ],
            [
                "numno"     => "川A07U59",
                "carno"     => "川A07U59",
                "entertime" => "2017-06-02 17:04:18",
                "exittime"  => "2017-06-02 18:06:15",
            ],
        ]);
    }
}