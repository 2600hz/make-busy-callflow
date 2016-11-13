<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class PasswordChange extends DeviceTestCase {

    public function setUp() {
        self::$a_device->setPassword("test_password");
        $this->assertTrue( self::$b_device->getGateway()->register() );
    }

    public function main($sip_uri) {
        $target = self::B_EXT .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::$b_device->waitForInbound();
        $this->assertNull($channel);

        self::$a_device->getGateway()->kill();
        self::getProfile('auth')->rescan();

        $this->assertTrue( self::$b_device->getGateway()->register() );

        $target  = self::B_EXT .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
        self::hangupChannels($ch_b);
    }

}