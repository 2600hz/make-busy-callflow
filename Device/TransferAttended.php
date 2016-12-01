<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferAttended extends DeviceTestCase {

    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $transferee = self::C_EXT . '@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b);

        self::assertEquals($channel_b->getChannelCallState(), "ACTIVE");
        $channel_b->onHold();
        self::assertEquals($channel_b->getChannelCallState(), "HELD");

        $channel_b_2 = self::ensureChannel( self::$b_device->originate($transferee) );
        $channel_c = self::ensureChannel( self::$c_device->waitForInbound() );

        $channel_c->answer();
        self::ensureEvent($channel_c->waitAnswer());
        self::ensureEvent($channel_b_2->waitAnswer());
        $channel_b_2->waitPark();
        self::ensureTwoWayAudio($channel_b_2, $channel_c);

        $channel_b->deflectChannel($channel_b_2, $referred_by);
        $channel_b->waitDestroy();

        self::ensureTwoWayAudio($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }
}