<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-73: Blind transfer, auto (park)
class BlindPark extends ParkingTestCase {
    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        $ch_b->answer();
        $ch_b->waitAnswer();

        $ch_b->deflect($parking_spot);

        $ch_c = self::ensureChannel( self::$c_device->originate($parking_spot) );
        $ch_c->waitPark();

        self::ensureTalking($ch_a, $ch_c);
        self::hangupBridged($ch_a, $ch_c);
    }
}