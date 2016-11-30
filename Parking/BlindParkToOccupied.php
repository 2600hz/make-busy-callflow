<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-77: Blind transfer, park, occupied slot.
class BlindParkToOccupied extends ParkingTestCase {
    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target) );

        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
        $ch_b->answer();
        $ch_b->waitAnswer();

        $ch_b->deflect($parking_spot);
        $ch_b->waitDestroy();

        $ch_c = self::ensureChannel( self::$c_device->originate($target) );

        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
        $ch_b->answer();
        $ch_b->waitAnswer();

        $ch_b->setVariables('sip_h_referred-by', $referred_by);
        $ch_b->deflect($parking_spot);
        $ch_b->waitDestroy();

        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
        $ch_b->answer();
        $ch_b->waitAnswer();

        self::ensureTalking($ch_b, $ch_c);
        self::hangupChannels($ch_a, $ch_b, $ch_c);
    }
}