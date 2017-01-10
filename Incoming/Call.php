<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class Call extends IncomingTestCase {

    public function main($sip_uri) {
        $number = self::$carrier_number->toNpan();
        $target = self::CARRIER_NUMBER .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$offnet->originate($target) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}