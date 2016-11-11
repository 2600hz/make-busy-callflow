<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class PasswordChangeTest extends CallflowTestCase {

    public function testMain() {
        $channels    = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        self::$a_device->setPassword("test_password");

        $gateways = Profiles::getProfile('auth')->getGateways();

        $this->assertFalse($gateways->findByName($a_device_id)->register());

        $uuid_base = "testPasswordChange-";

         foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying to call target %s with invalid credentials", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            $this->assertNull($channel);
        }

        $gateways->findByName($a_device_id)->kill();
        Profiles::getProfile('auth')->rescan();

        $this->assertTrue($gateways->findByName($a_device_id)->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying to call target %s with valid credentials", $target);
            $options = array("origination_uuid" => $uuid_base . "x2-" . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $channel->hangup();
        }
    }

}