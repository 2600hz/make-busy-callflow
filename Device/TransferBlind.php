<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferBlind extends DeviceTestCase {

    public function main($sip_uri) {
        $target_b = self::B_EXT . '@' . $sip_uri;
        $target_c = self::C_EXT . '@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target_b) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b);

        $channel_b->deflect($target_c);
        $channel_c = self::ensureChannel( self::$c_device->waitForInbound() );
        $channel_c->answer();
        $this->ensureTwoWayAudio($channel_a, $channel_c);
        $this->hangupBridged($channel_a, $channel_c);
    }

}