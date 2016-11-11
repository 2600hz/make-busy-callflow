<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallForwardTest extends CallflowTestCase {

    public function testMain() {
        $channels   = self::getChannels("auth");

        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        $uuid_base = "testCfBasic-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();
    }

}