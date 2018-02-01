<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class RestrictedCall extends DeviceTestCase {

    public function setUpTest() {
        self::$a_device->setRestriction("caribbean");
    }

    public function tearDownTest() {
        self::$a_device->resetRestrictions();
    }

    public function main($sip_uri) {
        $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::$offnet_resource->waitForInbound('+1' . self::RESTRICTED_NUMBER);
        self::assertEmpty($channel_b);
    }

}