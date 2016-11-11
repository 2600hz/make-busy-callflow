<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class BasicCallTest extends CallflowTestCase {

    public function testMain() {
        $channels = $this->getChannels("auth");
        $no_device_id = self::$no_device->getId();

        $uuid_base = "testCallBasic-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::MILLIWATT_NUMBER . '@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($no_device_id, $target, $options);
            $channel = $this->waitForOriginate("auth", $uuid);
            $this->hangupChannels($channel);
        }
    }

}