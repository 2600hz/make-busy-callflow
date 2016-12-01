<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-24
// If device has no CID for emergency, use user CID
class CallerIdEmergencySet extends UserTestCase {

    private $name;
    private $number;

    public function setUp() {
        $this->name = self::$a_device_1->getCidParam("emergency")->name;
        $this->number = self::$a_device_1->getCidParam("emergency")->number;
        self::$a_device_1->unsetCid("emergency");
    }

    public function tearDown() {
        self::$a_device_1->setCid($this->number, $this->name, "emergency");
    }

    public function main($sip_uri) {
        $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b = self::ensureChannel( self::$emergency_resource->waitForInbound(self::EMERGENCY_NUMBER) );
        self::assertEquals(
            self::$a_user->getCidParam("emergency")->number,
            urldecode($channel_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
        );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}