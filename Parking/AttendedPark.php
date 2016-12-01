<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-72: Attended transfer, auto (park)
class AttendedPark extends ParkingTestCase {
    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        $channel_b->answer();
        $channel_b->waitAnswer();

        $this->assertEquals($channel_b->getChannelCallState(), "ACTIVE");
        $channel_b->onHold();
        $this->assertEquals($channel_b->getChannelCallState(), "HELD");

        $channel_b_2 = self::ensureChannel( self::$b_device->originate($parking_spot) );
        $channel_b_2->waitAnswer();
        $channel_b_2->waitPark();

        $channel_b->deflectChannel($channel_b_2, $referred_by);

        $channel_c = self::ensureChannel( self::$c_device->originate($parking_spot) );
        self::ensureTalking($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }
}