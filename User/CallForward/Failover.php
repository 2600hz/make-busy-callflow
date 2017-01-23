<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36
// With call fowarding disabled, and failover true, Calls to offline devices should be forwarded.
class FailoverTest extends UserTestCase {

    public function setUpTest() {
        self::$offline_user->resetCfParams(self::C_NUMBER);
        self::$offline_user->setCfParam("failover", TRUE);
        self::$offline_user->setCfParam("enabled", FALSE);
    }

    public function tearDownTest() {
        self::$offline_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::OFFLINE_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        
        self::assertNull( self::$b_device_1->waitForInbound() );
        self::assertNull( self::$b_device_2->waitForInbound() );
        $channel_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $channel_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        self::hangupChannels($channel_a, $channel_c_1, $channel_c_2);
    }

}