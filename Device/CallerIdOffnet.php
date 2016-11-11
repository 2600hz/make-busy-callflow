<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallerIdOffnetTest extends CallflowTestCase {

    public function testMain() {
        $channels = self::getChannels("auth");
        $a_device_id  = self::$a_device->getId();

        $uuid_base = "testCidOffnet-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = '1' . self::OFFNET_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $offnet_channel = $channels->waitForInbound('1' . self::OFFNET_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $offnet_channel);
            $this->assertEquals(
                $offnet_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("external")->number
            );
            $a_channel = $this->ensureAnswer("auth", $uuid, $offnet_channel);
            $this->ensureTwoWayAudio($a_channel, $offnet_channel);
            $this->hangupBridged($a_channel, $offnet_channel);
        }
    }

}