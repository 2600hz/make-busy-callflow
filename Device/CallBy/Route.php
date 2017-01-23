<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class SipRoute extends DeviceTestCase {

    public function setUpTest() {
        $b_device_id = self::$b_device->getId();
        $raw_sip_uri = self::getProfile("auth")->getSipUri();
        $route_uri = preg_replace("/mod_sofia/", self::$b_device->getId(), $raw_sip_uri);
        self::$b_device->setInviteFormat("route", $route_uri);
    }

    public function tearDownTest() {
        self::$b_device->setInviteFormat("username");
    }

    public function main($sip_uri) {
        $b_device_id = self::$b_device->getId();
        $target = self::B_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound($b_device_id) );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}