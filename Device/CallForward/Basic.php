<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class BasicTest extends DeviceTestCase {

    public function testMain() {
        self::$b_device->resetCfParams(self::C_EXT);

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@' . $sip_uri;

            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }

        self::$b_device->resetCfParams();
    }

}