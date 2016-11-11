<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class TransferBlindTest extends CallflowTestCase {

    public function testMain() {
        $channels    = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_device_name = self::$b_device->getSipUsername();
        $c_device_name = self::$c_device->getSipUsername();

        $uuid_base = "testDeviceBlindTransfer-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $target_2 = self::C_EXT . '@' . $sip_uri;

            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            Log::debug("trying target #1 %s", $target);
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);

            $this->ensureTwoWayAudio($a_channel, $b_channel);

            Log::debug("trying target #2 %s", $target_2);
            $b_channel->deflect($target_2);
            $c_channel = $channels->waitForInbound($c_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $c_channel->answer();

            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
    }

}