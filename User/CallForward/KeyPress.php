<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36 Cf keypress
// Call should not be answered until answered AND a key is pressed by c_device_1
class KeyPress extends UserTestCase {

    public function setUpTest() {
        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("require_keypress", TRUE);
    }

    public function tearDownTest() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $channel_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        $channel_c_1->answer();
        self::assertFalse( $channel_a->getAnswerState() == "answered" );
        $channel_c_1->sendDtmf('1');
        self::ensureEvent( $channel_a->waitAnswer() );
        self::assertEquals("answered", $channel_a->getAnswerState());
    }

}