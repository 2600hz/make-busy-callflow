<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallForwardKeepCallerIdTest extends CallflowTestCase {

    public function testTrue() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("keep_caller_id", TRUE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );
            $this->assertEquals(
                $ch_c->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("internal")->number
            );
            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }
        self::$b_device->resetCfParams();
    }

    public function testFalse() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("keep_caller_id", FALSE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );
            $this->assertEquals(
                $ch_c->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$b_device->getCidParam("internal")->number
            );
            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }
        self::$b_device->resetCfParams();
    }

}