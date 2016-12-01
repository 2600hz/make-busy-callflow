<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-23 - CF substitute set to TRUE
// should result in only FWD destination numbers ringing.
class SubstituteTest extends UserTestCase {

    public function setUp() {
        self::$b_user->enableUserCF(['enabled'=> TRUE, 'substitute'=> TRUE, 'number'=>self::C_NUMBER]);
    }

    public function tearDown() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target  = self::B_NUMBER .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        self::assertNull( self::$b_device_1->waitForInbound() );
        self::assertNull( self::$b_device_2->waitForInbound() );
        $channel_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $channel_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        self::hangupChannels($channel_a, $channel_c_1, $channel_c_2);
    }

}