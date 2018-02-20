<?php
namespace KazooTests\Applications\Callflow\Device;

use \KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Utils;

class PasswordChange extends DeviceTestCase {

	private $password;
	
    public function setUpTest() {
    	$this->password = self::$a_device->getPassword();
    }

    public function tearDownTest() {
        self::$a_device->setPassword($this->password);
        self::$a_device->getGateway()->kill();
        self::rescanProfile('auth');
        self::$a_device->getGateway()->register();
    }

    public function main($sip_uri) {
    	self::$a_device->setPassword(Utils::randomString());
    	self::assertFalse( self::$a_device->getGateway()->register() );
    	
    	$target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::$a_device->originate($target);
        $this->assertEmpty($channel_a);

        self::$a_device->getGateway()->kill();
        self::rescanProfile('auth');
        
        $this->assertTrue( self::$b_device->getGateway()->register() );
        $this->assertTrue( self::$a_device->getGateway()->register() );

        $target  = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );
        self::hangupChannels($channel_b);
    }

}