<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class DirectCallsOnlyTest extends CallflowTestCase {

    public function testMain() {
        $channels = self::getChannels("auth");
        $a_device_id  = self::$a_device->getId();
        $b_device_id  = self::$b_device->getId();
        $no_device_id = self::$no_device->getId();
        $b_username   = self::$b_device->getSipUsername();
        $c_username   = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("direct_calls_only", TRUE);

        $test_account = self::getTestAccount();

        $uuid_base = "testCfDirectCallsOnly-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("placing a direct call, and expecting cf device %s to ring", $c_username);
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . "bext-" . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("placing a call via ring-group, expecting cf device %s to not ring", $c_username);
            $target  = self::RINGGROUP_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . "rgext-" . Utils::randomString(8));
            $uuid      = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertNull($c_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }

}