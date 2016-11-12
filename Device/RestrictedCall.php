<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class RestrictedCallTest extends CallflowTestCase {

    public function testAllow() {
        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;

            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$offnet_resource->waitForInbound('+1' . self::RESTRICTED_NUMBER) );

            self::ensureAnswer($ch_a, $ch_b);
            self::ensureTwoWayAudio($ch_a, $ch_b);
            self::hangupBridged($ch_a, $ch_b);
        }
    }

    public function testDeny() {
        self::$a_device->setRestriction("caribbean");

        $uuid_base = "testRestrictedCallDeny-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::$offnet_resource->waitForInbound('+1' . self::RESTRICTED_NUMBER);
            self::assertEmpty($channel);
        }

        self::$a_device->resetRestrictions();
    }

}