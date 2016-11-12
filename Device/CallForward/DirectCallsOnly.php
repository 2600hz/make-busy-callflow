<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class DirectCallsOnlyTest extends CallflowTestCase {

    public function testMain() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("direct_calls_only", TRUE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("placing a call via ring-group, expecting cf device %s to not ring", $c_username);
            $target  = self::RINGGROUP_EXT .'@'. $sip_uri;
            
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            self::assertNull( self::$c_device->waitForInbound() );

            self::ensureAnswer($ch_a, $ch_b);
            self::ensureTwoWayAudio($ch_a, $ch_b);
            self::hangupBridged($ch_a, $ch_b);
        }
    }

}