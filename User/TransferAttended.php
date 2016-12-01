<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferAttended extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER . '@' . $sip_uri;
        $referred_by = self::$b_device_1->makeReferredByUri();
        $transferee = self::C_NUMBER . '@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device_1->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b);

        self::assertEquals($channel_b->getChannelCallState(), "ACTIVE");
        $channel_b->onHold();
        self::assertEquals($channel_b->getChannelCallState(), "HELD");

        $channel_b_2 = self::ensureChannel( self::$b_device_1->originate($transferee) );
        $channel_c = self::ensureChannel( self::$c_device_1->waitForInbound() );

        $channel_c->answer();
        $channel_c->waitAnswer();
        self::ensureEvent( $channel_b_2->waitAnswer() );
        self::ensureEvent( $channel_b_2->waitPark() );
        self::ensureTwoWayAudio($channel_b_2, $channel_c);

        $channel_b->deflectChannel($channel_b_2, $referred_by);
        $channel_b->waitDestroy();

        self::ensureTwoWayAudio($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }
}