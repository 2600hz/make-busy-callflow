<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class SubstituteTest extends DeviceTestCase {

    public function testFalse() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("substitute", FALSE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;

            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );
            
            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }
        self::$b_device->resetCfParams();
    }

    public function testTrue(){
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("substitute", TRUE);

        $uuid_base = "testCfSubstituteTrue-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;

            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::assertNull( self::$b_device->waitForInbound() );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }
        self::$b_device->resetCfParams();
    }

}