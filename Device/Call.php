<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class UserName extends DeviceTestCase {

    public function main($sip_uri) {
        $target = self::B_EXT .'@'. $sip_uri;
        $ch_a = self::$a_device->originate($target);
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_b);
        self::ensureEvent($ch_a->waitPark());
        self::ensureTwoWayAudio($ch_a, $ch_b);
        self::hangupBridged($ch_a, $ch_b);
    }

}