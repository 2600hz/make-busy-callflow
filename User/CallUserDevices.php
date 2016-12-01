<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-23
// call to devices assigned to owner should ring both devices.
class CallUserDevices extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );

        $channel_b_1 = self::ensureChannel( self::$b_device_1->waitForInbound() );
        $channel_b_2 = self::ensureChannel( self::$b_device_2->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b_1);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b_1);
        self::hangupBridged($channel_a, $channel_b_1);
    }

}