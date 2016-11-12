<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class SipE164Test extends CallflowTestCase {

    public function testMain() {
        self::$b_device->setInviteFormat("e164");

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound('+1' . self::B_NUMBER) );
            self::ensureAnswer($ch_a, $ch_b);
            self::ensureTwoWayAudio($ch_a, $ch_b);
            self::hangupBridged($ch_a, $ch_b);
        }

        self::$b_device->setInviteFormat("username");
    }

}