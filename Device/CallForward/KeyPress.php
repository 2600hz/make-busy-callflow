<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallForwardKeyPressTest extends CallflowTestCase {

    public function testMain() {
        $channels  = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("require_keypress", TRUE);

        $uuid_base = "testCfKeyPress-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target    = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid  = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
            $c_channel->answer();
            $this->assertFalse($a_channel->getAnswerState() == "answered");
            $c_channel->sendDtmf('1');
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $a_channel->waitAnswer(60));
            $this->assertEquals("answered", $a_channel->getAnswerState());

            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();
    }

}