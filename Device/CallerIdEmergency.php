<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallerIdEmergencyTest extends CallflowTestCase {

    public function testMain() {
        $channels = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();

        $uuid_base = "testCidEmergency-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $emergency_channel = $channels->waitForInbound(self::EMERGENCY_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $emergency_channel);
            $this->assertEquals(
                self::$a_device->getCidParam("emergency")->number,
                urldecode($emergency_channel->getEvent()->getHeader("Caller-Caller-ID-Number"))
            );
            $a_channel = $this->ensureAnswer("auth", $uuid, $emergency_channel);
            $this->ensureTwoWayAudio($a_channel, $emergency_channel);
            $this->hangupBridged($a_channel, $emergency_channel);
        }
    }

}