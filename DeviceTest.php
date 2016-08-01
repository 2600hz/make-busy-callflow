<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\RingGroup;
use \MakeBusy\Kazoo\Applications\Crossbar\Resource;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;

use Shared\Common\Log;

class DeviceTest extends CallflowTestCase
{

    private static $a_device;
    private static $b_device;
    private static $c_device;
    private static $register_device;
    private static $no_device;
    private static $offnet_resource;
    private static $emergency_resource;
    private static $ring_group;
    private static $realm;

    const A_NUMBER          = '5552221001';
    const A_EXT             = '1001';
    const B_NUMBER          = '5552221002';
    const B_EXT             = '1002';
    const C_NUMBER          = '5022221003';
    const C_EXT             = '1003';
    const NO_NUMBER         = '5552221100';
    const NO_EXT            = '1100';
    const RINGGROUP_EXT     = '1111';
    const MILLIWATT_NUMBER  = '5555555551';
    const CALL_FWD_ENABLE   = '*72';
    const CALL_FWD_DISABLE  = '*73';
    const OFFNET_NUMBER     = '5552345678';
    const EMERGENCY_NUMBER  = '911';
    const RESTRICTED_NUMBER = '6845551234';

    public static function setUpBeforeClass() {
        Log::notice("setting up for device test");
        parent::setUpBeforeClass();

        $test_account = self::getTestAccount();
        self::$realm = $test_account->getAccountRealm();

        self::$a_device = new Device($test_account);
        self::$a_device->createCallflow(array(self::A_EXT, self::A_NUMBER));

        self::$b_device = new Device($test_account);
        self::$b_device->createCallflow(array(self::B_EXT, self::B_NUMBER));

        self::$c_device = new Device($test_account);
        self::$c_device->createCallflow(array(self::C_EXT, self::C_NUMBER));

        self::$no_device = new Device($test_account, FALSE);
        self::$no_device->createCallflow(array(self::NO_EXT, self::NO_NUMBER));

        self::$register_device = new Device($test_account);

        self::$offnet_resource = new Resource($test_account, array("^\\+1(\d{10})$"), "+1");

        self::$emergency_resource = new Resource($test_account, array("^(911)$"), null, TRUE);

        self::$ring_group = new RingGroup(
            $test_account,
            [ self::RINGGROUP_EXT ],
            [
                [
                    "id" => self::$b_device->getId(),
                    "type" => "device"
                ],
                [
                    "id" => self::$no_device->getId(),
                    "type" => "device"
                ]
            ]
        );

        Log::notice("reloading sofia profiles");
        Profiles::loadFromAccounts();
        Profiles::syncGateways();

    }

    public function setUp() {
        // NOTE: this hangs up all channels, we may not want
        //  to do this if we plan on executing multiple tests
        //  at once
        self::getEsl()->flushEvents();
        self::getEsl()->api("hupall");
    }

    public function testRegistrations() {
        Log::notice("testing device registrations");
        $gateways = Profiles::getProfile('auth')->getGateways();
        $register_device_id = self::$register_device->getId();
        $this->assertTrue($gateways->findByName($register_device_id)->register());
    }

    public function testCallBasic() {
        Log::notice("testing device basic call");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        $uuid_base = "testCallBasic-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::notice("%s - basic call from device %s to SIP URI %s", __METHOD__, $a_device_id, $sip_uri);
            // TODO: This is a call to milliwatt, we are crossing
            //  Kazoo applications....DONT CROSS THE STREAMS!
            $target = self::MILLIWATT_NUMBER . '@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
	        Log::debug("attempting to hang up call %s on SIP URI %s ...", $uuid, $sip_uri);
            $channel->hangup();
        }
    }

    public function testSipUsername() {
        Log::notice("testing SIP username");
        $channels   = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username = self::$b_device->getSipUsername();

        $uuid_base = "testSipUsername-";

        foreach (self::getSipTargets() as $sip_uri) {
	        Log::notice("%s - call from device %s to SIP URI %s" __METHOD__, $a_device_id, $sip_uri);
            $target = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $this->ensureAnswer($uuid, $channel);
        }
    }

    public function testSipNPAN() {
        Log::notice("testing SIP NPAN");
	    $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        self::$b_device->setInviteFormat("npan");

        $uuid_base = "testSipNPAN-";

        foreach (self::getSipTargets() as $sip_uri) {
	        $Log::notice("%s - call from device %s to SIP URI %s", __METHOD__, $a_device_id, $sip_uri);
            $target = self::B_NUMBER .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound(self::B_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
	        $this->ensureAnswer($uuid, $channel);
        }
    }

    public function testSip1NPAN() {
    	Log::notice("testing SIP 1 NPAN");
	    $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        self::$b_device->setInviteFormat("1npan");
        $uuid_base = "testSip1NPAN-";

        foreach (self::getSipTargets() as $sip_uri) {
	    $Log::notice("%s - call from device %s to SIP URI %s", __METHOD__, $a_device_id, $sip_uri);
            $target = self::B_NUMBER .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('1'. self::B_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $this->ensureAnswer($uuid, $channel);
        }
    }

    public function testSipE164() {
       	Log::notice("testing SIP E164");
	    $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        self::$b_device->setInviteFormat("e164");
        $uuid_base = "testSipE164-";

        foreach (self::getSipTargets() as $sip_uri) {
	    $Log::notice("%s - call from device %s to SIP URI %s", __METHOD__, $a_device_id, $sip_uri);
            $target = self::B_NUMBER .'@'. $sip_uri;
            $target = self::B_NUMBER .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::B_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $this->ensureAnswer($uuid, $channel);
        }
    }

    public function testSipRoute() {
	    Log::notice("testing SIP Route");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();

        $raw_sip_uri = Profiles::getProfile('auth')->getSipUri();
        $route_uri   = preg_replace("/mod_sofia/", $b_device_id, $raw_sip_uri);

        self::$b_device->setInviteFormat("route", $route_uri);

        $uuid_base = "testSipRoute-";

        foreach (self::getSipTargets() as $sip_uri) {
	    $Log::notice("%s - call from device-A %s to SIP URI %s and device-B %s", __METHOD__, $a_device_id, $sip_uri, $b_device_id);
            $target = self::B_NUMBER .'@'. $sip_uri;
            $target = self::B_EXT .'@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_device_id);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $this->ensureAnswer($uuid, $channel);
        }
        //reset invite format or pay a horrible price in blood!
        self::$b_device->setInviteFormat("username");

    }

    public function testCfEnable(){
	    Log::notice("testing Call Forward enable");
        $channels   = self::getChannels();
        $b_device_id = self::$b_device->getId();

        $uuid_base = "testCfEnable-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::notice("%s - device %s calls SIP URI %s", __METHOD__, $b_device_id, $sip_uri);
            $target  = self::CALL_FWD_ENABLE . '@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($b_device_id, $target, $options);
            $channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $channel->sendDtmf(self::C_EXT);
            $channel->waitDestroy();
            $this->assertTrue(self::$b_device->getCfParam("enabled"));
            $this->assertEquals(self::$b_device->getCfParam("number"), self::C_EXT);
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::notice("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCfDisable(){
	    Log::notice("testing Call Forward disable");
        $channels    = self::getChannels();
        $b_device_id = self::$b_device->getId();

        self::$b_device->resetCfParams(self::C_EXT);
        $uuid_base = "testCfDisable-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::notice("%s - device %s calls SIP URI %s", __METHOD__, $b_device_id, $sip_uri);
            $target  = self::CALL_FWD_DISABLE . '@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($b_device_id, $target, $options);
            $channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $channel->waitDestroy();
            $this->assertFalse(self::$b_device->getCfParam("enabled"));
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCfBasic(){
	    Log::notice("testing Call Forward basic");
        $channels   = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        $uuid_base = "testCfBasic-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::notice("%s - device %s calls SIP URI %s and forwards to %s", __METHOD__, $a_device_id, $sip_uri, $c_username);
            $target = self::B_EXT .'@' . $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $this->ensureAnswer($uuid, $c_channel);
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCfKeyPress(){
        Log::notice("testing Call Forward key press, AKA answering a forwarded call");
	    $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("require_keypress", TRUE);

        $uuid_base = "testCfKeyPress-";

        foreach (self::getSipTargets() as $sip_uri) {
            Log::notice("%s - device %s calls SIP URI %s and username %s ", __METHOD__, $a_device_id, $sip_uri, $c_username);
            $target    = self::B_EXT .'@'. $sip_uri;
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

            $this->ensureTalking($a_channel, $c_channel, 1600);
            $this->ensureTalking($c_channel, $a_channel, 600);
            $this->hangupChannels($a_channel, $c_channel);

        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCfSubstituteFalse(){
	    Log::notice("testing Call Forward substitute false");
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
            Log::notice("%s - device %s calls SIP URI %s\n
                        and username-B %s on device-B %s\n
                        and username-C %s on device-C %s",
                        __METHOD__, $a_device_id, $sip_uri,
                        $b_username, $b_device_id,
                        $c_username, $c_device_id);

            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $this->ensureAnswer($uuid, $c_channel);
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCfSubstituteTrue(){
	    Log::notice("testing Call Forward substitute true");
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
            Log::notice("%s - device %s calls SIP URI %s\n
                        and username-B %s on device-B %s\n
                        and username-C %s on device-C %s",
                        __METHOD__, $a_device_id, $sip_uri,
                        $b_username, $b_device_id,
                        $c_username, $c_device_id);

            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertEmpty($b_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $this->ensureAnswer($uuid, $c_channel);
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }


    public function testCfKeepCallerIdTrue(){
	    Log::notice("testing Call Forward keep caller ID true");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("keep_caller_id", TRUE);

        $uuid_base = "testCfKeepCallerIdTrue-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $c_channel->answer();
            $this->assertEquals(
                $c_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("internal")->number
            );
            $this->ensureAnswer($uuid, $c_channel);
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCfKeepCallerIdFalse(){
	    Log::notice("testing Call Forward keep caller ID false");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_username  = self::$c_device->getSipUsername();

        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("keep_caller_id", FALSE);

        $uuid_base = "testCfKeepCallerIdFalse-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $c_channel->answer();
            $this->assertEquals(
                $c_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$b_device->getCidParam("internal")->number
            );
            $this->ensureAnswer($uuid, $c_channel);
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCfFailover(){
	    Log::notice("testing Call Forward failover");
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
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $this->ensureAnswer($uuid, $c_channel);
        }
        Log::debug("Resetting Call Flow paramaters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);

        Log::debug("Resetting Call Flow paramaters for device %s ...", $no_device_id);
        self::$no_device->resetCFParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $no_device_id);
    }

    public function testCfDirectCallsOnly(){
	    Log::notice("testing Call Forward direct calls");
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
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . "bext-" . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
            $this->ensureAnswer($uuid, $c_channel);
        }

       foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RINGGROUP_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . "rgext-" . Utils::randomString(8));
            $uuid      = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertNull($c_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->ensureAnswer($uuid, $b_channel);
        }
        Log::debug("Resetting Call Flow parameters for device %s ...", $b_device_id);
        self::$b_device->resetCfParams();
        Log::info("Successfully reset Call Flows parameters for device %s.", $b_device_id);
    }

    public function testCidOffnet(){
	    Log::notice("testing Caller ID off net");
        $channels    = self::getChannels();
        $a_device_id  = self::$a_device->getId();

        $uuid_base = "testCidOffnet-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = '1' . self::OFFNET_NUMBER .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $offnet_channel = $channels->waitForInbound('1' . self::OFFNET_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $offnet_channel);
            $this->assertEquals(
                $offnet_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("external")->number
            );
            $this->ensureAnswer($uuid, $offnet_channel);
        }
    }

    public function testCidOnnet(){
	    Log::notice("testing Caller ID on net");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        $uuid_base = "testCidOnnet-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound("$b_username");
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->assertEquals(
                $b_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("internal")->number
            );
            $this->ensureAnswer($uuid, $b_channel);
        }
    }

    public function testCidEmergency(){
	    Log::notice("testing Caller ID emergency");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        $uuid_base = "testCidEmergency-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $emergency_channel = $channels->waitForInbound(self::EMERGENCY_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $emergency_channel);
            $this->assertEquals(
                $emergency_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_device->getCidParam("emergency")->number
            );
            $this->ensureAnswer($uuid, $emergency_channel);
        }
    }

    public function testRestrictedCallAllow(){
	    Log::notice("testing allowed Restricted Calls");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        $uuid_base = "testRestrictedCallAllow-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));

            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::RESTRICTED_NUMBER);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            $this->ensureAnswer($uuid, $channel);
        }
    }

    public function testRestrictedCallDeny(){
	    Log::notice("testing denied Restricted Calls");
        $channels     = self::getChannels();
        $a_device_id  = self::$a_device->getId();

        self::$a_device->setRestriction("caribbean");

        $uuid_base = "testRestrictedCallDeny-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RESTRICTED_NUMBER .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));

            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound('+1' . self::RESTRICTED_NUMBER);
            $this->assertEmpty($channel);
        }

        self::$a_device->resetRestrictions();
    }

    public function testCalleeDisabled() {
	    Log::notice("testing disabled Callee");
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
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            $this->assertEmpty($channel);
        }
    }

    public function testCalleeEnabled() {
	    Log::notice("testing enabled Callee");
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
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->ensureAnswer($uuid, $b_channel);
        }
    }

    public function testCallerDisabled() {
	    Log::notice("testing disabled Caller");
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
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            //TODO:  DISABLED UNTIL WE FIX BUG KAZOO-3109
            //$this->assertNull($b_channel);
            //for now we do this so we dont leave a channel behind by this failing to fail.
            //$this->ensureAnswer($uuid, $b_channel);
        }
    }

    public function testCallerEnabled() {
	    Log::notice("testing enabled Caller");
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
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->ensureAnswer($uuid, $b_channel);
        }
    }


    public function testUsernameChange(){
	    Log::notice("testing change user name");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        self::$a_device->setUsername("test_user");

        $gateways = Profiles::getProfile('auth')->getGateways();

        //TODO: changed from assertFalse to assertTrue to bypass bug KAZOO-1331
        $this->assertTrue($gateways->findByName($a_device_id)->register());

        $uuid_base = "testUsernameChange-";

         foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            $this->assertNull($channel);
        }

        $gateways->findByName($a_device_id)->kill();
        Profiles::getProfile('auth')->rescan();

        //TODO: bypass bug KAZOO-1331
        $this->assertTrue($gateways->findByName($a_device_id)->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . "x2-" . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $this->ensureAnswer($uuid, $b_channel);
        }
    }

    public function testPasswordChange(){
	    Log::notice("testing change password");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_username  = self::$b_device->getSipUsername();

        self::$a_device->setPassword("test_password");

        $gateways = Profiles::getProfile('auth')->getGateways();

        //TODO: changed to assertTrue (should be assertFalse) to bypass bug KAZOO-1331
        $this->assertTrue($gateways->findByName($a_device_id)->register());

        $uuid_base = "testPasswordChange-";

         foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            $this->assertNull($channel);
        }

        $gateways->findByName($a_device_id)->kill();
        Profiles::getProfile('auth')->rescan();

        //TODO: Changed from assertFalse to assertTrue to bypass issue with KAZOO-1331
        $this->assertFalse($gateways->findByName($a_device_id)->register());

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_EXT .'@'. $sip_uri;
            $options = array("origination_uuid" => $uuid_base . "x2-" . Utils::randomString(8));
            $uuid    = $channels->gatewayOriginate($a_device_id, $target, $options);
            $channel = $channels->waitForInbound($b_username);
            //TODO: disabled due to bug KAZOO-1331
            //$this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
            //$channel->hangup();
        }
    }
    public function testDeviceBlindTransfer() {
    	Log::notice("testing device's blind transfer");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_name = self::$b_device->getSipUsername();
        $c_device_name = self::$c_device->getSipUsername();

        $uuid_base = "testDeviceBlindTransfer-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $target_2 = self::C_EXT . '@' . $sip_uri;

            $options = array("origination_uuid" => $uuid_base . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);

            $this->ensureTalking($a_channel, $b_channel);
            $this->ensureTalking($b_channel, $a_channel);

            $b_channel->deflect($target_2);
            $c_channel = $channels->waitForInbound($c_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $c_channel->answer();

            $this->ensureTalking($a_channel, $c_channel);
            $this->ensureTalking($c_channel, $a_channel);
            $this->hangupChannels($a_channel, $c_channel);
        }
    }

    public function testDeviceAttendedTransfer() {
	    Log::notice("testing device's attended transfer");
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $b_device_name = self::$b_device->getSipUsername();
        $c_device_name = self::$c_device->getSipUsername();

        $uuid_base = "testDeviceAttendedTransfer-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $referred_by = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $transferee = self::C_EXT . '@' . $sip_uri;

            $options = array("origination_uuid" => $uuid_base . "aleg-" . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel_1= $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel_1);

            $b_channel_1->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $this->ensureTalking($a_channel, $b_channel_1);
            $this->ensureTalking($b_channel_1, $a_channel);

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

            $this->ensureTalking($b_channel_2, $c_channel);
            $this->ensureTalking($c_channel, $b_channel_2);

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

            $this->ensureTalking($a_channel, $c_channel);
            $this->ensureTalking($c_channel, $a_channel);
            $this->hangupChannels($a_channel, $c_channel);
        }
    }

    public function testRealmChangeRegistration() {
	    Log::notice("testing Realm change registration");
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


    private function ensureAnswer($bg_uuid, $b_channel){
        Log::debug("attempting to ensure an answer on call %s", $b_channel->getId());
        $channels = self::getChannels();

        $b_channel->answer();

        $a_channel = $channels->waitForOriginate($bg_uuid, 30);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);

        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $a_channel->waitAnswer(60));

        $a_channel->log("we are connected!");
        Log::debug("Call connected!");

        $this->ensureTalking($a_channel, $b_channel, 1600);
        $this->ensureTalking($b_channel, $a_channel, 600);
        $this->hangupChannels($b_channel, $a_channel);
    }

    private function ensureTalking($first_channel, $second_channel, $freq = 600){
    	Log::debug("ensuring tonal match between first channel %s and second channel %s on frequency %s ...", $first_channel, $second_channel, $freq);
        $first_channel->playTone($freq, 3000, 0, 5);
        $tone = $second_channel->detectTone($freq, 20);
        $first_channel->breakout();
        $this->assertEquals($freq, $tone);
    }

    private function hangupChannels($hangup_channel, $other_channels){
        Log::debug("hanging up channel %s ...", $hangup_channel);
        $hangup_channel->hangup();
        Log::info("hung up channel %s!", $hangup_channel);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $hangup_channel->waitDestroy(30));

        if (is_array($other_channels)){
            foreach ($other_channels as $channel){
                Log::debug("hanging up channel %s", $channel);
                $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $channel->waitDestroy(30));
            }
        } else {
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $other_channels->waitDestroy(60));
        }
    }
}
