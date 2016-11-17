<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferAttended extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER . '@' . $sip_uri;
        $referred_by = self::$b_device_1->makeReferredByUri();
        $transferee = self::C_NUMBER . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device_1->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_b);
        self::ensureEvent($ch_a->waitPark());
        self::ensureTwoWayAudio($ch_a, $ch_b);

        self::assertEquals($ch_b->getChannelCallState(), "ACTIVE");
        $ch_b->onHold();
        self::assertEquals($ch_b->getChannelCallState(), "HELD");

        $ch_b_2 = self::ensureChannel( self::$b_device_1->originate($transferee) );
        $ch_c = self::ensureChannel( self::$c_device_1->waitForInbound() );

        $ch_c->answer();
        $ch_c->waitAnswer();
        self::ensureEvent( $ch_b_2->waitAnswer() );
        self::ensureEvent( $ch_b_2->waitPark() );
        self::ensureTwoWayAudio($ch_b_2, $ch_c);

        $ch_b->deflectChannel($ch_b_2, $referred_by);
        $ch_b->waitDestroy();

        self::ensureTwoWayAudio($ch_a, $ch_c);
        self::hangupBridged($ch_a, $ch_c);
    }
}