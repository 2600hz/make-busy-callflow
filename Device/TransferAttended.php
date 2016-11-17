<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferAttended extends DeviceTestCase {

    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $transferee = self::C_EXT . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_b);
        self::ensureEvent($ch_a->waitPark());
        self::ensureTwoWayAudio($ch_a, $ch_b);

        self::assertEquals($ch_b->getChannelCallState(), "ACTIVE");
        $ch_b->onHold();
        self::assertEquals($ch_b->getChannelCallState(), "HELD");

        $ch_b_2 = self::ensureChannel( self::$b_device->originate($transferee) );
        $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

        $ch_c->answer();
        self::ensureEvent($ch_c->waitAnswer());
        self::ensureEvent($ch_b_2->waitAnswer());
        $ch_b_2->waitPark();
        self::ensureTwoWayAudio($ch_b_2, $ch_c);

        $ch_b->deflectChannel($ch_b_2, $referred_by);
        $ch_b->waitDestroy();

        self::ensureTwoWayAudio($ch_a, $ch_c);
        self::hangupBridged($ch_a, $ch_c);
    }
}