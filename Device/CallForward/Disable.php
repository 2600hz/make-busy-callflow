<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallForwardDisableTest extends CallflowTestCase {
    
    public function testMain() {
        $channels = self::getChannels("auth");
        $b_device_id = self::$b_device->getId();

        self::$b_device->resetCfParams(self::C_EXT);
        $uuid_base = "testCfDisable-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::CALL_FWD_DISABLE . '@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($b_device_id, $target, $options);
            $channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $channel->waitDestroy();
            $this->assertFalse(self::$b_device->getCfParam("enabled"));
        }
    }

}