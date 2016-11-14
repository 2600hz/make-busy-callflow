<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferBlind extends DeviceTestCase {

    public function main($sip_uri) {
        $target_b = self::B_EXT . '@' . $sip_uri;
        $target_c = self::C_EXT . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target_b) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_b);
        self::ensureEvent($ch_a->waitPark());
        self::ensureTwoWayAudio($ch_a, $ch_b);

        $ch_b->deflect($target_c);
        $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );
        $ch_c->answer();
        $this->ensureTwoWayAudio($ch_a, $ch_c);
        $this->hangupBridged($ch_a, $ch_c);
    }

}