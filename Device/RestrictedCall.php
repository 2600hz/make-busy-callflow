<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class RestrictedCallTest extends CallflowTestCase {

    public function testAllow() {
        $channels = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();

        $uuid_base = "testRestrictedCallAllow-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));

            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::RESTRICTED_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $channel);
            $this->ensureTwoWayAudio($a_channel, $channel);
            $this->hangupBridged($a_channel, $channel);
        }
    }

    public function testDeny() {
        $channels = self::getChannels("auth");
        $a_device_id  = self::$a_device->getId();

        self::$a_device->setRestriction("caribbean");

        $uuid_base = "testRestrictedCallDeny-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));

            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::RESTRICTED_NUMBER);
            $this->assertEmpty($channel);
        }

        self::$a_device->resetRestrictions();
    }

}