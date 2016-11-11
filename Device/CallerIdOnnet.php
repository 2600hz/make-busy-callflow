<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallerIdOnnetTest extends CallflowTestCase {

    public function testMain() {
        $channels = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        $uuid_base = "testCidOnnet-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound("$b_username");
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->assertEquals(
                $b_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("internal")->number
            );
            $a_channel = $this->ensureAnswer("auth", $uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }

}