<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CalleeTest extends CallflowTestCase {

    public function testDisabled() {
        self::$b_device->disableDevice();
        $this->assertFalse(self::$b_device->getGateway()->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $ch_o = $a_device->originate($target, $this->originate_uuid());
            $ch_i = $b_device->waitForInbound();
            $this->assertEmpty($ch_i);
        }
    }

    public function testEnabled() {
        self::$b_device->enableDevice();
        $this->assertTrue(self::$b_device->getGateway()->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $ch_a = $a_device->originate($target, $this->originate_uuid());
            $ch_b = $b_device->ensureInbound();
            $this->ensureAnswer($ch_a, $ch_b);
            $this->ensureTwoWayAudio($ch_i, $ch_b);
            $this->hangupBridged($ch_i, $ch_b);
        }
    }

}