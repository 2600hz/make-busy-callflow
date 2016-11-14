<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferBlind extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER . '@' . $sip_uri;
        $target_2 = self::C_NUMBER . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device_1->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_b);
        self::ensureEvent($ch_a->waitPark());
        self::ensureTwoWayAudio($ch_a, $ch_b);

        $ch_b->deflect($target_2);
        $ch_c = self::ensureChannel( self::$c_device_1->waitForInbound() );

        $ch_c->answer();
        $this->ensureTwoWayAudio($ch_a, $ch_c);
        $this->hangupBridged($ch_a, $ch_c);
    }

}