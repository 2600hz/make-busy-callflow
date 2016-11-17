<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-78
//Test answering parked call ring back via attended transfer.
class AttendedParkIgnoreRingback extends ParkingTestCase {
    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        $ch_b->answer();
        $ch_b->waitAnswer();

        $this->assertEquals($ch_b->getChannelCallState(), "ACTIVE");
        $ch_b->onHold();
        $this->assertEquals($ch_b->getChannelCallState(), "HELD");

        $ch_b_2 = self::ensureChannel( self::$b_device->originate($parking_spot) );
        $ch_b_2->waitAnswer();
        $ch_b_2->waitPark();

        $ch_b->deflectChannel($ch_b_2, $referred_by);

        $ch_b_3 = self::ensureChannel( self::$b_device->waitForInbound(self::$b_device->getSipUsername(), 30) );
        $ch_b_3->waitHangup(30);

        $ch_b_4 = self::ensureChannel( self::$b_device->waitForInbound(self::$b_device->getSipUsername(), 30) );
        $ch_b_4->answer();
        $ch_b_4->waitAnswer();

        self::ensureTalking($ch_a, $ch_b_4);
        self::hangupBridged($ch_a, $ch_b_4);
    }
}