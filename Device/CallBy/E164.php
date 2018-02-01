<?php
namespace KazooTests\Applications\Callflow\Device\CallBy;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class SipE164 extends DeviceTestCase {

    public function setUpTest() {
        self::$b_device->setInviteFormat("e164");
    }

    public function tearDownTest() {
        self::$b_device->setInviteFormat("username");
    }

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound('+1' . self::B_NUMBER) );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}