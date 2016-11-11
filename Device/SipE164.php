<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class SipE164Test extends CallflowTestCase {

    public function testMain() {
        $channels = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        self::$b_device->setInviteFormat("e164");
        $uuid_base = "testSipE164-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::B_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $channel);
            $this->ensureTwoWayAudio($a_channel, $channel);
            $this->hangupBridged($a_channel, $channel);
        }
    }

}