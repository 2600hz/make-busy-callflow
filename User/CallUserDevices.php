<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-23
// call to devices assigned to owner should ring both devices.
class CallUserDevices extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );

        $ch_b_1 = self::ensureChannel( self::$b_device_1->waitForInbound() );
        $ch_b_2 = self::ensureChannel( self::$b_device_2->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_b_1);
        self::ensureEvent($ch_a->waitPark());
        self::ensureTwoWayAudio($ch_a, $ch_b_1);
        self::hangupBridged($ch_a, $ch_b_1);
    }

}