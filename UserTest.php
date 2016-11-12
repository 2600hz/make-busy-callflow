<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\User;
use \MakeBusy\Kazoo\Applications\Crossbar\RingGroup;
use \MakeBusy\Kazoo\Applications\Crossbar\Resource;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class UserTest extends DeviceTestCase
{

    private static $test_account;
    private static $a_user;
    private static $a_device_1;
    private static $a_device_2;
    private static $b_user;
    private static $b_device_1;
    private static $b_device_2;
    private static $c_user;
    private static $c_device_1;
    private static $c_device_2;
    private static $offline_user;
    private static $offline_device_1;
    private static $offline_device_2;
    private static $offnet_resource;
    private static $emergency_resource;

    const A_NUMBER         = '2000';
    const B_NUMBER         = '2001';
    const C_NUMBER         = '2002';
    const OFFLINE_NUMBER   = '2003';
    const EMERGENCY_NUMBER = '911';
    const RINGGROUP_NUMBER  = '2111';
    const CALL_FWD_ENABLE  = '*72';
    const CALL_FWD_DISABLE = '*73';

    public function setUp() {
        // NOTE: this hangs up all channels, we may not want
        //  to do this if we plan on executing multiple tests
        //  at once
        self::getEsl()->api("hupall");
        self::getEsl()->flushEvents();
    }

    // setup default states,
    //NOTE: ALL tests that change owner assignment should change back to these values at end of test!
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        sleep(5);

        $test_account = self::getTestAccount();

        self::$a_user= new User($test_account);
        self::$a_user->createUserCallFlow(array(self::A_NUMBER));

        self::$b_user= new User($test_account);
        self::$b_user->createUserCallFlow(array(self::B_NUMBER));

        self::$c_user= new User($test_account);
        self::$c_user->createUserCallFlow(array(self::C_NUMBER));

        self::$offline_user= new User($test_account);
        self::$offline_user->createUserCallFlow(array(self::OFFLINE_NUMBER));

        self::$a_device_1 = new Device($test_account,TRUE, array('owner_id' => self::$a_user->getId()));
        self::$a_device_2 = new Device($test_account,TRUE, array('owner_id' => self::$a_user->getId()));

        self::$b_device_1 = new Device($test_account,TRUE, array('owner_id' => self::$b_user->getId()));
        self::$b_device_2 = new Device($test_account,TRUE, array('owner_id' => self::$b_user->getId()));

        self::$c_device_1 = new Device($test_account,TRUE, array('owner_id' => self::$c_user->getId()));
        self::$c_device_2 = new Device($test_account,TRUE, array('owner_id' => self::$c_user->getId()));

        self::$offline_device_1 = new Device($test_account, FALSE, array('owner_id' => self::$offline_user->getId()));
        self::$offline_device_2 = new Device($test_account, FALSE, array('owner_id' => self::$offline_user->getId()));

        self::$offnet_resource    = new Resource($test_account, array("^\\+1(\d{10})$"), "+1");
        self::$emergency_resource = new Resource($test_account, array("^(911)$"), null, TRUE);

        $b_user_id = self::$b_user->getId();
        $offline_user_id = self::$offline_user->getId();

        Profiles::loadFromAccounts();
        Profiles::syncGateways();

        $ring_group = new RingGroup(
            $test_account,
            [ self::RINGGROUP_NUMBER ],
            [
                [
                    "id" => $b_user_id,
                    "type" => "user"
                ],
                [
                    "id" => $offline_user_id,
                    "type" => "user"
                ]
            ]
        );
    }

    //MKBUSY-23 - call to devices assigned to owner should ring both devices.
    public function testOwnerAssignment() {
        Log::notice("%s", __METHOD__);
        $channels  = self::getChannels();

        $a_device_1_id   = self::$a_device_1->getId();

        $b_device_1_name = self::$b_device_1->getSipUsername();
        $b_device_2_name = self::$b_device_2->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("trying SIP URI %s", $sip_uri);
            $target = self::B_NUMBER .'@'. $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $b_channel_1 = $channels->waitForInbound($b_device_1_name);
            $b_channel_2 = $channels->waitForInbound($b_device_2_name);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_2);

            $a_channel = $this->ensureAnswer($uuid, $b_channel_1);
            $this->ensureTwoWayAudio($a_channel, $b_channel_1);
            $this->hangupBridged($a_channel, $b_channel_1);
        }
    }

    //MKBUSY-23 - Changing owner assignmnets.
    public function testOwnerChange() {
	$this->markTestIncomplete('Known issue, KAZOO-5115');
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        $b_device_1_name = self::$b_device_1->getSipUsername();
        $b_device_2_name = self::$b_device_2->getSipUsername();

        self::$b_device_2->setDeviceParam('owner_id', self::$c_user->getId());

        //make sure b_device_2 is not reacable via b_user.
        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER .'@'. $sip_uri;
            Log::debug("testing cannot reach user %s on device %s", $target, $b_device_2_name);
            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);
            $b_channel_1 = $channels->waitForInbound($b_device_1_name);
            $b_channel_2 = $channels->waitForInbound($b_device_2_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);
            $this->assertEmpty($b_channel_2);

            $a_channel = $this->ensureAnswer($uuid, $b_channel_1);
            $this->ensureTwoWayAudio($a_channel, $b_channel_1);
            $this->hangupBridged($a_channel, $b_channel_1);
        }

        // also when we call c user, b_device_2 should ring
        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::C_NUMBER .'@'. $sip_uri;
            Log::debug("testing can reach user %s on device %s", $target, $b_device_2_name);
            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);
            $b_channel_2 = $channels->waitForInbound($b_device_2_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_2);

            $a_channel = $this->ensureAnswer($uuid, $b_channel_2);
            $this->ensureTwoWayAudio($a_channel, $b_channel_2);
            $this->hangupBridged($a_channel, $b_channel_2);
        }

        //reset owner assignment back to b user
        self::$b_device_2->setDeviceParam('owner_id', self::$b_user->getId());

    }


    //MKBUSY-36 - Cf enabled by feature code
    // b_device_1 should not have forwarding enabled, b_user should have forwarding enabled to C
    public function testCfEnable(){
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();

        $b_device_1_id = self::$b_device_1->getId();

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::CALL_FWD_ENABLE ."@". $sip_uri;

            $uuid    = $channels->gatewayOriginate($b_device_1_id, $target);
            $b_channel_1 = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);

            $b_channel_1->sendDtmf(self::C_NUMBER . '#');
            $b_channel_1->waitHangUp();

            Log::debug("testing device %s shouldn't have forwarding enabled for target %s", $b_device_1_id, $target);
            $this->assertNull(self::$b_device_1->getDeviceParam("call_forward"));
            Log::debug("testing user %s forwards to C", self::$b_user->getId);
            $this->assertTrue(self::$b_user->getCfParam("enabled"));
            $this->assertEquals(self::$b_user->getCfParam("number"), "2002");
        }
        self::$b_user->resetCfParams();
    }

    //MKBUSY-36 Cf disabled by feature code
    //b_user should have no cf enabled after activating feature code
    public function testCfDisable(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $b_device_1_id = self::$b_device_1->getId();

        self::$b_user->resetCfParams(self::C_NUMBER);

        foreach (self::getSipTargets() as $target) {
            $target  = self::CALL_FWD_DISABLE .'@'. $target;
            Log::debug("trying target %s", $target);
            $uuid    = $channels->gatewayOriginate($b_device_1_id, $target);
            $b_channel_1 = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);
            $this->assertTrue($b_channel_1->waitHangUp());
            $this->assertFalse(self::$b_user->getCfParam("enabled"));
        }

        self::$b_user->resetCfParams();
    }

    //MAKEBUSY-34 Cf basic call
    // both devices on C should ring.
    public function testCfBasic(){
        Log::notice("%s", __METHOD__);
        $channels   = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        $c_device_1_username = self::$c_device_1->getSipUsername();
        $c_device_2_username = self::$c_device_2->getSipUsername();

        self::$b_user->resetCfParams(self::C_NUMBER);

        foreach (self::getSipTargets() as $sip_target) {
            $target = self::B_NUMBER .'@'. $sip_target;
            Log::debug("trying target %s", $target);

            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $c_channel_1 = $channels->waitForInbound($c_device_1_username);
            $c_channel_2 = $channels->waitForInbound($c_device_2_username);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $a_channel = $this->ensureAnswer($uuid, $c_channel_1);
            $this->ensureTwoWayAudio($a_channel, $c_channel_1);
            $this->hangupBridged($a_channel, $c_channel_1);
        }
        self::$b_user->resetCfParams();
    }

    //MKBUSY-36 Cf keypress
    //Call should not be answered until answered AND a key is pressed by c_device_1
    public function testCfKeyPress(){
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        $c_device_1_username  = self::$c_device_1->getSipUsername();
        $c_device_2_username  = self::$c_device_2->getSipUsername();

        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("require_keypress", TRUE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_NUMBER .'@' . $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid    = $channels->gatewayOriginate($a_device_1_id, $target);

            $c_channel_1 = $channels->waitForInbound($c_device_1_username);
            $c_channel_2 = $channels->waitForInbound($c_device_2_username);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $a_channel_1 = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel_1);

            $c_channel_1->answer();

            $this->assertFalse($a_channel_1->getAnswerState() == "answered");

            $c_channel_1->sendDtmf("1");

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $a_channel_1->waitAnswer());

            $this->assertEquals($a_channel_1->getAnswerState(), "answered");

            $this->ensureTalking($a_channel_1, $c_channel_1);
            $this->hangupBridged($a_channel_1, $c_channel_1);
        }
        self::$b_user->resetCfParams();
    }

    //MKBUSY-23 - CF substitute set to false
    // should result in all lines ringing
    public function testCfSubstituteFalse() {
        Log::notice("%s", __METHOD__);
        $channels  = self::getChannels();

        $a_device_1_id   = self::$a_device_1->getId();
        $b_device_1_name = self::$b_device_1->getSipUsername();
        $b_device_2_name = self::$b_device_2->getSipUsername();
        $c_device_1_name = self::$c_device_1->getSipUsername();
        $c_device_2_name = self::$c_device_2->getSipUsername();

        self::$b_user->enableUserCF(
            array(
                'enabled'=> TRUE,
                'substitute'=> FALSE,
                'number'=> self::C_NUMBER
            )
        );

        foreach (self::getSipTargets() as $sip_uri) {
            $target= self::B_NUMBER .'@'. $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $b_channel_1 = $channels->waitForInbound($b_device_1_name);
            $b_channel_2 = $channels->waitForInbound($b_device_2_name);

            $c_channel_1 = $channels->waitForInbound($c_device_1_name);
            $c_channel_2 = $channels->waitForInbound($c_device_2_name);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_2);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $b_channel_1->hangup();
            $b_channel_2->hangup();
            $c_channel_1->hangup();
            $c_channel_2->hangup();
        }
        self::$b_user->resetUserCF();
    }

    //MKBUSY-23 - CF substitute set to TRUE
    // should result in only FWD destination numbers ringing.
    public function testCfSubstituteTrue() {
        Log::notice("%s", __METHOD__);
        $channels  = self::getChannels();

        $a_device_1_id   = self::$a_device_1->getId();
        $b_device_1_name = self::$b_device_1->getSipUsername();
        $b_device_2_name = self::$b_device_2->getSipUsername();
        $c_device_1_name = self::$c_device_1->getSipUsername();
        $c_device_2_name = self::$c_device_2->getSipUsername();

        self::$b_user->enableUserCF(
            array(
                'enabled'=> TRUE,
                'substitute'=> TRUE,
                'number'=>self::C_NUMBER
            )
        );

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $b_channel_1 = $channels->waitForInbound($b_device_1_name);
            $b_channel_2 = $channels->waitForInbound($b_device_2_name);

            $c_channel_1 = $channels->waitForInbound($c_device_1_name);
            $c_channel_2 = $channels->waitForInbound($c_device_2_name);

            $this->assertNull($b_channel_1);
            $this->assertNull($b_channel_2);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $c_channel_1->hangup();
            $c_channel_2->hangup();
        }
        self::$b_user->resetUserCF();
    }

    //MKBUSY-36 - test keep caller id true
    //Caller Id presented to c_devices should be A_user internal CID
    public function testCfKeepCallerIdTrue(){
	$this->markTestIncomplete('Known issue, KAZOO-5116');
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        $c_device_1_username = self::$c_device_1->getSipUsername();
        $c_device_2_username = self::$c_device_2->getSipUsername();

        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("keep_caller_id", TRUE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $c_channel_1 = $channels->waitForInbound($c_device_1_username);
            $c_channel_2 = $channels->waitForInbound($c_device_2_username);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $c_channel_1->answer();

            $this->assertEquals(
                $c_channel_1->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_user->getCidParam("internal")->number
            );

            $a_channel_1 = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel_1);

            $c_channel_1->hangup();
            $a_channel_1->waitHangup();
        }
        self::$b_user->resetCfParams();
    }

    //MKBUSY-36 - test keep caller id false
    //Caller Id presented to c_devices should be B_user internal CID
    public function testCfKeepCallerIdFalse(){
	$this->markTestIncomplete('Known issue, KAZOO-5116');
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        $c_device_1_username  = self::$c_device_1->getSipUsername();
        $c_device_2_username  = self::$c_device_2->getSipUsername();

        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("keep_caller_id", FALSE);

        foreach (self::getSipTargets() as $sip_target) {
            $target  = self::B_NUMBER .'@'. $sip_target;
            Log::debug("trying target %s", $target);

            $uuid    = $channels->gatewayOriginate($a_device_1_id, $target);

            $c_channel_1 = $channels->waitForInbound($c_device_1_username);
            $c_channel_2 = $channels->waitForInbound($c_device_2_username);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $c_channel_1->answer();

            $this->assertEquals(
                $c_channel_1->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$b_user->getCidParam("internal")->number
            );

            $a_channel_1 = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel_1);

            $c_channel_1->hangup();
            $a_channel_1->waitHangup();
        }
        self::$b_user->resetCfParams();
    }

    //MKBUSY-36
    // WIth call fowarding disabled, and failover true, Calls to offline devices should be forwarded.
    public function testCfFailover(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        $c_device_1_username  = self::$c_device_1->getSipUsername();
        $c_device_2_username  = self::$c_device_2->getSipUsername();

        $offline_device_1_username  = self::$offline_device_1->getSipUsername();
        $offline_device_2_username  = self::$offline_device_2->getSipUsername();

        self::$offline_user->resetCfParams(self::C_NUMBER);
        self::$offline_user->setCfParam("failover", TRUE);
        self::$offline_user->setCfParam("enabled", FALSE);

        foreach (self::getSipTargets() as $sip_target) {
            $target  = self::OFFLINE_NUMBER .'@'. $sip_target;
            Log::debug("trying target %s", $target);

            $uuid    = $channels->gatewayOriginate($a_device_1_id, $target);

            $c_channel_1 = $channels->waitForInbound($c_device_1_username);
            $c_channel_2 = $channels->waitForInbound($c_device_2_username);

            $offline_channel_1 = $channels->waitForInbound($c_device_1_username);
            $offline_channel_2 = $channels->waitForInbound($c_device_2_username);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $c_channel_1->answer();

            $a_channel_1 = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel_1);

            $c_channel_1->hangup();
            $a_channel_1->waitHangup();
        }
        self::$offline_user->resetCFParams();
    }

    //MKBUSY-36
    // Ensurue direct calls get forwarded and group calls do not
    public function testCfDirectCallsOnly(){
        Log::notice("%s", __METHOD__);
        $channels     = self::getChannels();

        $a_device_1_id  = self::$a_device_1->getId();

        $b_device_1_username   = self::$b_device_1->getSipUsername();
        $b_device_2_username   = self::$b_device_2->getSipUsername();

        $c_device_1_username   = self::$c_device_1->getSipUsername();
        $c_device_2_username   = self::$c_device_2->getSipUsername();

        self::$b_user->resetCfParams(self::C_NUMBER);
        self::$b_user->setCfParam("direct_calls_only", TRUE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $c_channel_1 = $channels->waitForInbound($c_device_1_username);
            $c_channel_2 = $channels->waitForInbound($c_device_2_username);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel_2);

            $a_channel = $this->ensureAnswer($uuid, $c_channel_1);
            $this->ensureTwoWayAudio($a_channel, $c_channel_1);
            $this->hangupBridged($a_channel, $c_channel_1);
        }

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::RINGGROUP_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $b_channel_1 = $channels->waitForInbound($b_device_1_username);
            $b_channel_2 = $channels->waitForInbound($b_device_2_username);

            $c_channel_1 = $channels->waitForInbound($c_device_1_username);
            $c_channel_2 = $channels->waitForInbound($c_device_2_username);

            $this->assertNull($c_channel_1);
            $this->assertNull($c_channel_2);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_2);

            $a_channel = $this->ensureAnswer($uuid, $b_channel_1);
            $this->ensureTwoWayAudio($a_channel, $b_channel_1);
            $this->hangupBridged($a_channel, $b_channel_1);
        }
        self::$b_user->resetCfParams();
    }

    //MKBUSY-35
    //Ensure calls to offnet destinations use users external CID
    public function testCidOffnet(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $a_device_1_id  = self::$a_device_1->getId();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = "15553335678@" . $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid = $channels->gatewayOriginate($a_device_1_id, $target);

            $offnet_channel = $channels->waitForInbound("15553335678");

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $offnet_channel);
            $this->assertEquals(
                $offnet_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_user->getCidParam("external")->number
            );

            $offnet_channel->hangup();
        }
    }

    //MKBUSY-35
    //Ensure same account calls use users Internal caller ID
    public function testCidOnnet(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        $b_device_1_username  = self::$b_device_1->getSipUsername();
        $b_device_2_username  = self::$b_device_2->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::B_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid    = $channels->gatewayOriginate($a_device_1_id, $target);

            $b_channel_1 = $channels->waitForInbound($b_device_1_username);
            $b_channel_2 = $channels->waitForInbound($b_device_2_username);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_2);

            $this->assertEquals(
                $b_channel_1->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_user->getCidParam("internal")->number
            );

            $b_channel_1->hangup();
            $b_channel_2->hangup();
        }
    }

    //MKBUSY-24
    // If device has emergency CID set, use device level, not user
    public function testCidEmergencyDevice(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid    = $channels->gatewayOriginate($a_device_1_id, $target);

            $emergency_channel = $channels->waitForInbound("911");

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $emergency_channel);

            $this->assertEquals(
                self::$a_device_1->getCidParam("emergency")->number,
                urldecode($emergency_channel->getEvent()->getHeader("Caller-Caller-ID-Number"))
            );

            $emergency_channel->hangup();
        }
    }

    //MKBUSY-24
    // If device has no CID for emergency, use user CID
    public function testCidEmergencyUser(){
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();

        $a_device_1_id = self::$a_device_1->getId();

        self::$a_device_1->unsetCid("emergency");

        foreach (self::getSipTargets() as $sip_uri) {
            $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
            Log::debug("trying target %s", $target);

            $uuid    = $channels->gatewayOriginate($a_device_1_id, $target);

            $emergency_channel = $channels->waitForInbound(self::EMERGENCY_NUMBER);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $emergency_channel);

            $this->assertEquals(
                $emergency_channel->getEvent()->getHeader("Caller-Caller-ID-Number"),
                self::$a_user->getCidParam("emergency")->number
            );

            $emergency_channel->hangup();
        }
    }

    public function testUserBlindTransfer() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device_1->getId();
        $b_device_name = self::$b_device_1->getSipUsername();
        $c_device_name = self::$c_device_1->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER . '@' . $sip_uri;
            $target_2 = self::C_NUMBER . '@' . $sip_uri;
            Log::debug("trying target #1 %s and target #2 %s", $target, $target_2);

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
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
            $this->hangupBridged($a_channel, $c_channel);
        }
    }
     public function testUserAttendedTransfer() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device_1->getId();
        $b_device_id = self::$b_device_1->getId();
        $c_device_id = self::$c_device_1->getId();
        $b_device_name = self::$b_device_1->getSipUsername();
        $c_device_name = self::$c_device_1->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_NUMBER . '@' . $sip_uri;
            $referred_by = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $transferee = self::C_NUMBER . '@' . $sip_uri;
            Log::debug("trying target %s and referrer %s and transferee %s", $target, $referred_by, $transferee);

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
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

            $uuid_2 = $channels->gatewayOriginate($b_device_id, $transferee);
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
            $this->hangupBridged($a_channel, $c_channel);
        }
    }

}
