<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallForwardKeyPressTest extends DeviceTestCase {

    public function setUp() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("require_keypress", TRUE);
    }

    public function tearDown() {
        self::$b_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::B_EXT .'@'. $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

        $ch_c->answer();
        self::assertFalse( $ch_a->getAnswerState() == "answered" );
        $ch_c->sendDtmf('1');
        self::ensureEvent( $ch_a->waitAnswer(5) );
        self::assertEquals("answered", $ch_a->getAnswerState());

        self::ensureTwoWayAudio($ch_a, $ch_c);
        self::hangupBridged($ch_a, $ch_c);
    }

}