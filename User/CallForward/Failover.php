<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36
// With call fowarding disabled, and failover true, Calls to offline devices should be forwarded.
class FailoverTest extends UserTestCase {

    public function setUp() {
        self::$offline_user->resetCfParams(self::C_NUMBER);
        self::$offline_user->setCfParam("failover", TRUE);
        self::$offline_user->setCfParam("enabled", FALSE);
    }

    public function tearDown() {
        self::$offline_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::OFFLINE_NUMBER .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        
        self::assertNull( self::$b_device_1->waitForInbound() );
        self::assertNull( self::$b_device_2->waitForInbound() );
        $ch_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $ch_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        self::hangupChannels($ch_a, $ch_c_1, $ch_c_2);
    }

}