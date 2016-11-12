<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class UsernameChangeTest extends CallflowTestCase {

    public function testMain() {
        self::$a_device->setUsername("test_user");
        $this->assertFalse( self::$a_device->getGateway()->register() );

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::$a_device->originate($target);
            self::assertNull( $ch_a );
        }

        $a_device->getGateway()->kill();
        self::getProfile("auth")->rescan();

        $this->assertTrue( self::$a_device->getGateway()->register() );

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

            self::ensureAnswer($ch_a, $ch_b);
            self::ensureTwoWayAudio($ch_a, $ch_b);
            self::hangupBridged($ch_a, $ch_b);
        }
    }
}