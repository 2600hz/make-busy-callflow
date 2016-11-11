<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallForwardEnableTest extends CallflowTestCase {

    public function testMain(){
        $channels   = self::getChannels("auth");
        $b_device_id = self::$b_device->getId();
        self::$b_device->resetCfParams(self::C_EXT);

        $uuid_base = "testCfEnable-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::CALL_FWD_ENABLE . '@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($b_device_id, $target, $options);
            $channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $channel->sendDtmf(self::C_EXT);
            $channel->waitDestroy();
            $this->assertTrue(self::$b_device->getCfParam("enabled"));
            $this->assertEquals(self::$b_device->getCfParam("number"), self::C_EXT);
        }
    }

}
