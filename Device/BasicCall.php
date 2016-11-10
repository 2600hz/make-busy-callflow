<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class BasicCallTest extends CallflowTestCase {

    public function testMain() {
        $channels = self::getChannels("auth");
        $no_device_id = self::$no_device->getId();

        $uuid_base = "testCallBasic-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::MILLIWATT_NUMBER . '@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($no_device_id, $target, $options);
            $channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $channel->hangup();
        }
    }

}