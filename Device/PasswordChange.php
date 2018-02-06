<?php
namespace KazooTests\Applications\Callflow\Device;

use \KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class PasswordChange extends DeviceTestCase {

	private $password;
	
    public function setUpTest() {
//         $this->password = self::$a_device->getPassword();
//         self::$a_device->setPassword("test_password");
//         self::assertFalse( self::$a_device->getGateway()->register() );
    }

    public function tearDownTest() {
        self::$a_device->setPassword($this->password);
        self::$a_device->getGateway()->kill();
        self::getProfile('auth')->rescan();
        self::$a_device->getGateway()->register();
    }

    public function main($sip_uri) {
    	$this->password = self::$a_device->getPassword();
    	self::$a_device->setPassword("test_password");
    	self::assertFalse( self::$a_device->getGateway()->register() );
    	
    	$target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::$a_device->originate($target);
        $this->assertEmpty($channel_a);

        self::$a_device->getGateway()->kill();
        self::getProfile('auth')->rescan();

        $this->assertTrue( self::$b_device->getGateway()->register() );
        $this->assertTrue( self::$a_device->getGateway()->register() );

        $target  = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );
        self::hangupChannels($channel_b);
    }

}