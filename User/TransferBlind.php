<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferBlind extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER . '@' . $sip_uri;
        $target_2 = self::C_NUMBER . '@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device_1->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b);

        $channel_b->deflect($target_2);
        $channel_c = self::ensureChannel( self::$c_device_1->waitForInbound() );

        $channel_c->answer();
        $this->ensureTwoWayAudio($channel_a, $channel_c);
        $this->hangupBridged($channel_a, $channel_c);
    }

}