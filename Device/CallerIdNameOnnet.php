<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class CallerIdNameOnnet extends DeviceTestCase {
	
	private $saved_callerid;
	private $name = "101 - Bör 去 Škofja Loka";

	public function setUpTest() {
		$this->saved_callerid = self::$a_device->getCidParam("internal");
	}

	public function tearDownTest() {
		self::$a_device->setCid($this->saved_callerid->number, $this->saved_callerid->name, "internal");
	}

    public function main($sip_uri) {
    	self::$a_device->setCid(self::A_EXT, $this->name, "internal");
        $target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );
        self::assertEquals(
       		$this->name,
            urldecode($channel_b->getEvent()->getHeader("Caller-Caller-ID-Name"))
        );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}
