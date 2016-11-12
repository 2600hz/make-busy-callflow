<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallForwardKeyPressTest extends CallflowTestCase {

    public function testMain() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("require_keypress", TRUE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;

            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

            $ch_c->answer();
            self::assertFalse( $ch_a->getAnswerState() == "answered" );
            $ch_c->sendDtmf('1');
            self::ensureEvent( $ch_a->waitAnswer(5) );
            self::assertEquals("answered", $ch_a->getAnswerState());

            $this->ensureTwoWayAudio($ch_a, $ch_c);
            $this->hangupBridged($ch_a, $ch_c);
        }
        self::$b_device->resetCfParams();
    }

}