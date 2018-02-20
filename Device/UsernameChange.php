<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class UsernameChange extends DeviceTestCase {

    private $username;

    public function setUpTest() {
    	$this->username = self::$a_device->getUsername();
    }

    public function tearDownTest() {
        self::$a_device->setUsername($this->username);
        self::$a_device->getGateway()->kill();
        self::rescanProfile('auth');
    }

    public function main($sip_uri) {
    	self::$a_device->setUsername("test_user");
    	self::assertFalse( self::$a_device->getGateway()->register() );
    	
    	$target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::$a_device->originate($target);
        self::assertEmpty( $channel_a );

        self::$a_device->getGateway()->kill();
        self::rescanProfile("auth");

        $this->assertTrue( self::$a_device->getGateway()->register() );

        $target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}