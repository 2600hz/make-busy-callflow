<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class FailoverTest extends DeviceTestCase {

    public function testMain() {
        self::$no_device->resetCfParams(self::C_EXT);
        self::$no_device->setCfParam("failover", TRUE);
        self::$no_device->setCfParam("enabled", FALSE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::NO_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }
    }

}