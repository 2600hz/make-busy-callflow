<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-77: Blind transfer, park, occupied slot.
class BlindParkToOccupied extends ParkingTestCase {
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

        $channel_c = self::ensureChannel( self::$c_device->originate($target) );

        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );
        $channel_b->answer();
        $channel_b->waitAnswer();

        $channel_b->setVariables('sip_h_referred-by', $referred_by);
        $channel_b->deflect($parking_spot);
        $channel_b->waitDestroy();

        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );
        $channel_b->answer();
        $channel_b->waitAnswer();

        self::ensureTalking($channel_b, $channel_c);
        self::hangupChannels($channel_a, $channel_b, $channel_c);
    }
}