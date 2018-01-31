<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class Disable extends DeviceTestCase {

    public function tearDownTest() {
    	self::$b_device->enableDevice();
    	self::$b_device->getGateway()->register();
    }

    public function main($sip_uri) {
    	self::assertTrue(self::$b_device->getGateway()->unregister());
    	self::$b_device->disableDevice();
    	self::assertFalse(self::$b_device->getGateway()->register());
    	$target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::$b_device->waitForInbound();
        self::assertEmpty($channel_b);
        self::$b_device->enableDevice();
        self::assertTrue(self::$b_device->getGateway()->register());
    }

}