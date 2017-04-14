<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-73: Blind transfer, auto (park)
class BlindPark extends ParkingTestCase {
    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        $channel_b->answer();
        $channel_b->waitAnswer();
        $channel_a->waitAnswer();

        $channel_b->deflect($parking_spot);
        $channel_b->waitDestroy();

        // FIXME: sync
        sleep(5); // Give Kazoo some time to realize call is parked

        $channel_c = self::ensureChannel( self::$c_device->originate($parking_spot) );
        $channel_c->waitPark();

        self::ensureTalking($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }
}