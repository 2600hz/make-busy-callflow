<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class RestrictedCall extends DeviceTestCase {

    public function setUp() {
        self::$a_device->setRestriction("caribbean");
    }

    public function tearDown() {
        self::$a_device->resetRestrictions();
    }

    public function main($sip_uri) {
        $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::$offnet_resource->waitForInbound('+1' . self::RESTRICTED_NUMBER);
        self::assertEmpty($ch_b);
    }

}