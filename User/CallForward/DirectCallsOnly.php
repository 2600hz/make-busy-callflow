<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36
// Ensurue direct calls get forwarded and group calls do not
class DirectCallsOnly extends UserTestCase {

	private $cf;
	
    public function setUpTest() {
    	$this->cf = self::$b_user->getCallForward();
        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("direct_calls_only", TRUE);
    }

    public function tearDownTest() {
        self::$b_user->setCallForward($this->cf);
    }

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device_1->waitForInbound() );
        $channel_c = self::ensureChannel( self::$c_device_1->waitForInbound() );

        self::hangupChannels($channel_a, $channel_b, $channel_c);

        $target  = self::RINGGROUP_NUMBER .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b_1 = self::ensureChannel( self::$b_device_1->waitForInbound() );
        $channel_b_2 = self::ensureChannel( self::$b_device_2->waitForInbound() );

        self::assertNull( self::$c_device_1->waitForInbound() );
        self::hangupChannels($channel_a, $channel_b_1, $channel_b_2);
    }

}