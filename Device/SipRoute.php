<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class SipRouteTest extends CallflowTestCase {

    public function testMain() {
        $channels = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();

        $raw_sip_uri = self::getProfile("auth")->getSipUri();
        $route_uri   = preg_replace("/mod_sofia/", $b_device_id, $raw_sip_uri);

        self::$b_device->setInviteFormat("route", $route_uri);

        $uuid_base = "testSipRoute-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_device_id);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $channel);
            $this->ensureTwoWayAudio($a_channel, $channel);
            $this->hangupBridged($a_channel, $channel);
        }
        //reset invite format or pay a horrible price in blood!
        self::$b_device->setInviteFormat("username");
    }

}