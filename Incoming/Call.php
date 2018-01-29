<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class Call extends IncomingTestCase {

    public function setUpTest() {
        self::setConfig("block_anonymous_caller_id", false);
    }

    public function main($sip_uri) {
        $number = self::$carrier_number->toNpan();
        $target = self::$number .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$offnet->originate($target) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );

        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}