<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CalleeTest extends CallflowTestCase {

    public function testDisabled() {
        self::$b_device->disableDevice();
        $this->assertFalse(self::$b_device->getGateway()->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_o = self::$a_device->originate($target, 5, $this->originate_uuid());
            $ch_i = self::$b_device->waitForInbound();
            $this->assertEmpty($ch_i);
        }
    }

    public function testEnabled() {
        self::$b_device->enableDevice();
        $this->assertTrue(self::$b_device->getGateway()->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target, 5, $this->originate_uuid()) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            $this->ensureAnswer($ch_a, $ch_b);
            $this->ensureTwoWayAudio($ch_i, $ch_b);
            $this->hangupBridged($ch_i, $ch_b);
        }
    }

}