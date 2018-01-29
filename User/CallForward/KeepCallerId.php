<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36 - test keep caller id true
// Caller Id presented to c_devices should be A_user internal CID
class KeepCallerId extends UserTestCase {

	private $cf;
	
    public function setUpTest() {
    	$this->cf = self::$b_user->getCallForward();    	
        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("keep_caller_id", TRUE);
    }

    public function tearDownTest() {
//        self::$b_user->resetCfParams();
        self::$b_user->setCallForward($this->cf);
        
    }

    public function main($sip_uri) {
    	Log::info("Known issue, KAZOO-5116");
        $this->markTestIncomplete("");
        $target  = self::B_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_c = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $this->assertEquals(
            $channel_c->getEvent()->getHeader("Caller-Caller-ID-Number"),
            self::$a_user->getCidParam("internal")->number
        );
        self::ensureAnswer($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }

}