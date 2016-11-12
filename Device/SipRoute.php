<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class SipRouteTest extends DeviceTestCase {

    public function testMain() {

        $b_device_id = self::$b_device->getId();
        $raw_sip_uri = self::getProfile("auth")->getSipUri();
        $route_uri   = preg_replace("/mod_sofia/", self::$b_device->getId(), $raw_sip_uri);

        self::$b_device->setInviteFormat("route", $route_uri);

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound($b_device_id) );
            self::ensureAnswer($ch_a, $ch_b);
            self::ensureTwoWayAudio($ch_a, $ch_b);
            self::hangupBridged($ch_a, $ch_b);
        }

        self::$b_device->setInviteFormat("username");
    }

}