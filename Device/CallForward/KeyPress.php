<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class KeyPress extends DeviceTestCase {

    public function setUpTest() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("require_keypress", TRUE);
    }

    public function tearDownTest() {
        self::$b_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::B_EXT .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_c = self::ensureChannel( self::$c_device->waitForInbound() );

        $channel_c->answer();
        self::assertFalse( $channel_a->getAnswerState() == "answered" );
        $channel_c->sendDtmf('1');
        self::ensureEvent( $channel_a->waitAnswer() );
        self::assertEquals("answered", $channel_a->getAnswerState());
        self::hangupBridged($channel_a, $channel_c);
    }

}