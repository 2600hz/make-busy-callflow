<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class Disable extends DeviceTestCase {

    public function setUp() {
        self::$b_device->disableDevice();
        self::assertFalse(self::$b_device->getGateway()->register());
    }

    public function tearDown() {
        self::$b_device->enableDevice();
    }

    public function test() {
        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::$b_device->waitForInbound();
            self::assertEmpty($ch_b);
        }
    }

}