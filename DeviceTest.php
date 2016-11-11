<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\RingGroup;
use \MakeBusy\Kazoo\Applications\Crossbar\Resource;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;

use \MakeBusy\Common\Log;

class DeviceTest extends CallflowTestCase
{

    public function testCfDisable(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $b_device_id = self::$b_device->getId();

        self::$b_device->resetCfParams(self::C_EXT);
        $uuid_base = "testCfDisable-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::CALL_FWD_DISABLE . '@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($b_device_id, $target, $options);
            $channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $channel->waitDestroy();
            $this->assertFalse(self::$b_device->getCfParam("enabled"));
        }
        self::$b_device->resetCfParams();
    }

    public function testCfBasic(){
        Log::notice("%s", __METHOD__);
        $channels   = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        $uuid_base = "testCfBasic-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $this->ensureAnswer($uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();
    }

    public function testCfKeyPress(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
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
            $uuid      = $channels->gatewayOriginate($a_device_id, $target, $options);
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

    public function testCfSubstituteFalse(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $b_username  = self::$b_device->getSipUsername();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("substitute", FALSE);

        $uuid_base = "testCfSubstituteFalse-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $this->ensureAnswer($uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();
    }

    public function testCfSubstituteTrue(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $b_username  = self::$b_device->getSipUsername();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("substitute", TRUE);

        $uuid_base = "testCfSubstituteTrue-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertEmpty($b_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $this->ensureAnswer($uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();
    }


    public function testCfKeepCallerIdTrue(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("keep_caller_id", TRUE);

        $uuid_base = "testCfKeepCallerIdTrue-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $c_channel->answer();
            $this->assertEquals(
                $c_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("internal")->number
            );
            $a_channel = $this->ensureAnswer($uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();
    }

    public function testCfKeepCallerIdFalse(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("keep_caller_id", FALSE);

        $uuid_base = "testCfKeepCallerIdFalse-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $c_channel->answer();
            $this->assertEquals(
                $c_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$b_device->getCidParam("internal")->number
            );
            $a_channel = $this->ensureAnswer($uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();
    }

    public function testCfFailover(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $no_device_id = self::$no_device->getId();
        $c_username  = self::$c_device->getSipUsername();

        $test_account = self::getTestAccount();

        self::$no_device->resetCfParams(self::C_EXT);
        self::$no_device->setCfParam("failover", TRUE);
        self::$no_device->setCfParam("enabled", FALSE);

        $uuid_base = "testCfFailover-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::NO_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $this->ensureAnswer($uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
        self::$b_device->resetCfParams();

        self::$no_device->resetCFParams();
    }

    public function testCfDirectCallsOnly(){
        Log::notice("%s", __METHOD__);
        $channels     = self::getChannels();
        $a_device_id  = self::$a_device->getId();
        $b_device_id  = self::$b_device->getId();
        $no_device_id = self::$no_device->getId();
        $b_username   = self::$b_device->getSipUsername();
        $c_username   = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("direct_calls_only", TRUE);

        $test_account = self::getTestAccount();

        $uuid_base = "testCfDirectCallsOnly-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("placing a direct call, and expecting cf device %s to ring", $c_username);
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . "bext-" . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $a_channel = $this->ensureAnswer($uuid, $c_channel);
            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("placing a call via ring-group, expecting cf device %s to not ring", $c_username);
            $target  = self::RINGGROUP_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . "rgext-" . Utils::randomString(8));
            $uuid      = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertNull($c_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $a_channel = $this->ensureAnswer($uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
        self::$b_device->resetCfParams();
    }

    public function testCidOffnet(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id  = self::$a_device->getId();

        $uuid_base = "testCidOffnet-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = '1' . self::OFFNET_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $offnet_channel = $channels->waitForInbound('1' . self::OFFNET_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $offnet_channel);
            $this->assertEquals(
                $offnet_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("external")->number
            );
            $a_channel = $this->ensureAnswer($uuid, $offnet_channel);
            $this->ensureTwoWayAudio($a_channel, $offnet_channel);
            $this->hangupBridged($a_channel, $offnet_channel);
        }
    }

    public function testCidOnnet(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        $uuid_base = "testCidOnnet-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound("$b_username");
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->assertEquals(
                $b_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("internal")->number
            );
            $a_channel = $this->ensureAnswer($uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }

    public function testCidEmergency(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        $uuid_base = "testCidEmergency-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $emergency_channel = $channels->waitForInbound(self::EMERGENCY_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $emergency_channel);
            $this->assertEquals(self::$a_device->getCidParam("emergency")->number,
                                urldecode($emergency_channel->getEvent()->getHeader("Caller-Caller-ID-Number"))
            );
            $a_channel = $this->ensureAnswer($uuid, $emergency_channel);
            $this->ensureTwoWayAudio($a_channel, $emergency_channel);
            $this->hangupBridged($a_channel, $emergency_channel);
        }
    }

    public function testRestrictedCallAllow(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        $uuid_base = "testRestrictedCallAllow-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));

            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::RESTRICTED_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $a_channel = $this->ensureAnswer($uuid, $channel);
            $this->ensureTwoWayAudio($a_channel, $channel);
            $this->hangupBridged($a_channel, $channel);
        }
    }

    public function testRestrictedCallDeny(){
        Log::notice("%s", __METHOD__);
        $channels     = self::getChannels();
        $a_device_id  = self::$a_device->getId();

        self::$a_device->setRestriction("caribbean");

        $uuid_base = "testRestrictedCallDeny-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));

            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::RESTRICTED_NUMBER);
            $this->assertEmpty($channel);
        }

        self::$a_device->resetRestrictions();
    }

    public function testCalleeDisabled() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        self::$b_device->disableDevice();

        $gateways  = Profiles::getProfile('auth')->getGateways();

        //TODO: DISABLED until we fix the bug KAZOO-3079.
        //$this->assertFalse($gateways->findByName($b_device_id)->register());

        $uuid_base = "testCalleeDisabled-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            $this->assertEmpty($channel);
        }
    }

    public function testCalleeEnabled() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();
        $b_device_id = self::$b_device->getId();

        self::$b_device->enableDevice();

        $gateways  = Profiles::getProfile('auth')->getGateways();

        $this->assertTrue($gateways->findByName($b_device_id)->register());

        $uuid_base = "testCalleeEnabled-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $a_channel = $this->ensureAnswer($uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }

    public function testCallerDisabled() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
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
            //$a_channel = $this->ensureAnswer($uuid, $b_channel);
            //$this->ensureTwoWayAudio($a_channel, $b_channel);
            //$this->hangupBridged($a_channel, $b_channel);
        }
    }

    public function testCallerEnabled() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
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
            $a_channel = $this->ensureAnswer($uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }


    public function testUsernameChange(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        self::$a_device->setUsername("test_user");

        $gateways = Profiles::getProfile('auth')->getGateways();

        $this->assertFalse($gateways->findByName($a_device_id)->register());

        $uuid_base = "testUsernameChange-";

         foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
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
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . "x2-" . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $a_channel = $this->ensureAnswer($uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }

    public function testPasswordChange(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
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
    public function testDeviceBlindTransfer() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_name = self::$b_device->getSipUsername();
        $c_device_name = self::$c_device->getSipUsername();

        $uuid_base = "testDeviceBlindTransfer-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $target_2 = self::C_EXT . '@' . $sip_uri;

            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            Log::debug("trying target #1 %s", $target);
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);

            $this->ensureTwoWayAudio($a_channel, $b_channel);

            Log::debug("trying target #2 %s", $target_2);
            $b_channel->deflect($target_2);
            $c_channel = $channels->waitForInbound($c_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $c_channel->answer();

            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
    }

    public function testDeviceAttendedTransfer() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $b_device_name = self::$b_device->getSipUsername();
        $c_device_name = self::$c_device->getSipUsername();

        $uuid_base = "testDeviceAttendedTransfer-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $referred_by = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $transferee = self::C_EXT . '@' . $sip_uri;

            $options = array("origination_uuid" => $uuid_base . "aleg-" . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel_1= $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel_1);

            $b_channel_1->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $this->ensureTwoWayAudio($a_channel, $b_channel_1);

            $this->assertEquals($b_channel_1->getChannelCallState(), "ACTIVE");
            $b_channel_1->onHold();
            $this->assertEquals($b_channel_1->getChannelCallState(), "HELD");

            $options = array("origination_uuid" => $uuid_base . "transferee-" . Utils::randomString(8));
            $uuid_2 = $channels->gatewayOriginate($b_device_id, $transferee, $options);
            $c_channel = $channels->waitForInbound($c_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $c_channel);

            $c_channel->answer();
            $b_channel_2 = $channels->waitForOriginate($uuid_2);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel_2);
            $event = $b_channel_2->waitAnswer();

            $this->ensureTwoWayAudio($b_channel_2, $c_channel);

            $to_tag = $event->getHeader('variable_sip_to_tag');
            $from_tag = $event->getHeader('variable_sip_from_tag');
            $sip_uri = urldecode($event->getHeader('variable_sip_req_uri'));
            $call_uuid = $event->getHeader('variable_call_uuid');

            $refer_to =     '<sip:' . $sip_uri
                     . '?Replaces=' . $call_uuid
                   . '%3Bto-tag%3D' . $to_tag
                 . '%3Bfrom-tag%3D' . $from_tag
                 . '>';

            $b_channel_1->setVariables('sip_h_refer-to', $refer_to);
            $b_channel_1->setVariables('sip_h_referred-by', $referred_by);
            $b_channel_1->deflect($refer_to);
            $b_channel_1->waitDestroy();

            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
    }

    public function testRealmChangeRegistration() {
        Log::notice("%s", __METHOD__);
        $test_account = self::getTestAccount();
        $a_device_id = self::$a_device->getId();
        $gateways = Profiles::getProfile('auth')->getGateways();

        // test basic registration
        $this->assertTrue($gateways->findByName($a_device_id)->register());

        // unregister
        $this->assertTrue($gateways->findByName($a_device_id)->unregister());

        // change realm
        $test_account->setAccountRealm('blah.com');

        // ensure fail registration
        $this->assertFalse($gateways->findByName($a_device_id)->register());

        // update gateway with new realm
        $gateways->findByName($a_device_id)->setParam('realm', 'blah.com');
        $gateways->findByName($a_device_id)->setParam('from-domain', 'blah.com');
        // sync freeswitch with new gateway information
        Profiles::syncGateways();
        // re-register
        $this->assertTrue($gateways->findByName($a_device_id)->register());

        // change realm back and re-sync for non-existent future device test failures.
        $test_account->setAccountRealm(self::$realm);
        $gateways->findByName($a_device_id)->setParam('realm', self::$realm);
        $gateways->findByName($a_device_id)->setParam('from-domain', self::$realm);
        Profiles::syncGateways();
    }
}
