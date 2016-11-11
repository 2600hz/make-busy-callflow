<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CalleeTest extends CallflowTestCase {

    public function testDisabled() {
        self::$b_device->disableDevice();
        self::assertFalse(self::$b_device->getGateway()->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::$b_device->waitForInbound();
            self::assertEmpty($ch_a);
        }
    }

    public function testEnabled() {
        self::$b_device->enableDevice();
        self::assertTrue(self::$b_device->getGateway()->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            self::ensureAnswer($ch_a, $ch_b);
            self::ensureTwoWayAudio($ch_a, $ch_b);
            self::hangupBridged($ch_a, $ch_b);
        }
    }

}