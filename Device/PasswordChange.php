<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class PasswordChangeTest extends CallflowTestCase {

    public function testMain() {

        self::$a_device->setPassword("test_password");
        $this->assertTrue( self::$b_device->getGateway()->register() );

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::$b_device->waitForInbound();
            $this->assertNull($channel);
        }

        self::$a_device->getGateway()->kill();
        Profiles::getProfile('auth')->rescan();

        $this->assertTrue( self::$b_device->getGateway()->register() );

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            self::hangupChannels($ch_b);
        }
    }

}