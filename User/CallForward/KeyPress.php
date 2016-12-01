<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36 Cf keypress
// Call should not be answered until answered AND a key is pressed by c_device_1
class KeyPress extends UserTestCase {

    public function setUp() {
        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("require_keypress", TRUE);
    }

    public function tearDown() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $ch_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $ch_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        $ch_c_1->answer();
        self::assertFalse( $ch_a->getAnswerState() == "answered" );
        $ch_c_1->sendDtmf('1');
        self::ensureEvent( $ch_a->waitAnswer() );
        self::assertEquals("answered", $ch_a->getAnswerState());
        self::hangupChannels($ch_a, $ch_c_1, $ch_c_2);
    }

}