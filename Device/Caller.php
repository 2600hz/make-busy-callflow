<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class CallerTest extends CallflowTestCase {
 
    public function testDisabled() {
        $channels    = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        self::$a_device->disableDevice();

        $gateways  = Profiles::getProfile('auth')->getGateways();
        //TODO: DISABLED TILL WE FIX KAZOO-3079:
        //$this->assertFalse($gateways->findByName($a_device_id)->register());

        $uuid_base = "testCallerDisabled-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            //TODO:  DISABLED UNTIL WE FIX BUG KAZOO-3109
            //$this->assertNull($b_channel);
            //for now we do this so we dont leave a channel behind by this failing to fail.
            //$a_channel = $this->ensureAnswer("auth", $uuid, $b_channel);
            //$this->ensureTwoWayAudio($a_channel, $b_channel);
            //$this->hangupBridged($a_channel, $b_channel);
        }
    }

    public function testEnabled() {
        $channels    = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();
        $b_device_id = self::$b_device->getId();

        self::$a_device->enableDevice();

        $gateways  = Profiles::getProfile('auth')->getGateways();

        $this->assertTrue($gateways->findByName($b_device_id)->register());

        $uuid_base = "testCallerEnabled-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }

}
