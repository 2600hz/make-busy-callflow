<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;
use \MakeBusy\Kazoo\SDK;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\User;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

use Exception;

class QuickCallTest extends CallflowTestCase
{

    private static $test_account;
    private static $realm;

    private static $admin_user;
    private static $admin_device;

    private static $anon_user;
    private static $anon_device;

    private static $a_user;
    private static $a_device;

    private static $b_user;
    private static $b_dev_1;
    private static $b_dev_2;
    private static $b_dev_3;


    const A_EXT     = '5001';
    const B_EXT     = '5002';
    const PASSWORD  = 'passwerd';
    const CNAM      = 'Administrator';
    const CNUM      = '6288888888';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $test_account = self::getTestAccount();
        self::$realm = self::getTestAccount()->getAccountRealm();

        self::$admin_user = new User($test_account, array('password' => self::PASSWORD,
                                                          'allow_anonymous_quickcall' => FALSE));
        $admin_user_id = self::$admin_user->getId();
        self::$admin_device = new Device($test_account, TRUE, array('owner_id' => $admin_user_id,
                                                                    'allow_anonymous_quickcalls' => FALSE));

        self::$anon_user = new User($test_account, array('priv_level' => 'user',
                                                         'password' => self::PASSWORD,
                                                         'allow_anonymous_quickcalls' => TRUE));
        $anon_user_id = self::$anon_user->getId();
        self::$anon_device = new Device($test_account, TRUE, array('owner_id' => $anon_user_id,
                                                                   'allow_anonymous_quickcalls' => TRUE));

        self::$a_user = new User($test_account);

        self::$a_device = new Device($test_account);
        self::$a_device->createCallflow(array(self::A_EXT));

        self::$b_user = new User($test_account, array('password' => self::PASSWORD,
                                                      'allow_anoymous_quickcall' =>FALSE));
        self::$b_dev_1 = new Device($test_account, TRUE, array('owner_id' => self::$b_user->getId()));
        self::$b_dev_2 = new Device($test_account, TRUE, array('owner_id' => self::$b_user->getId()));
        self::$b_dev_3 = new Device($test_account, TRUE, array('owner_id' => self::$b_user->getId()));

        Profiles::loadFromAccounts();
        Profiles::syncGateways();
    }

    public function setUp() {
        self::getEsl()->flushEvents();
        self::getEsl()->api("hupall");
    }

    public function testDeviceCall() {
        Log::notice("%s", __METHOD__);

        self::$admin_device->getDevice()->quickcall(self::A_EXT);

	$a_channel = $this->waitForCall(self::$admin_device);
        $a_channel->answer();

	$b_channel = $this->waitForCall(self::$a_device);
        $b_channel->answer();

	$this->testAudioAndHangup($a_channel, $b_channel);
    }

    public function testDeviceAA() {
        Log::notice("%s", __METHOD__);

        self::$admin_device->getDevice()->quickcall(self::A_EXT, array('auto_answer' => 'true'));
	$a_channel = $this->waitForCall(self::$admin_device);

        $this->assertEquals('true', $a_channel->getAutoAnswerDetected());
        $a_channel->answer();

	$b_channel = $this->waitForCall(self::$a_device);
        $b_channel->answer();

	$this->testAudioAndHangup($a_channel, $b_channel);
    }

    public function testDeviceCID() {
	$this->markTestIncomplete('Known issue: KAZOO-5118');
        Log::notice("%s", __METHOD__);

        self::$admin_device->getDevice()->quickcall(self::A_EXT, array('cid-number' => self::CNUM, 'cid-name' => self::CNAM));

	$a_channel = $this->waitForCall(self::$admin_device);
        $a_channel->answer();

	$b_channel = $this->waitForCall(self::$a_device);
        $b_channel->answer();

        $this->assertEquals($b_channel->getCallerIdNumber(), self::CNUM);
        $this->assertEquals($b_channel->getCallerIdName(), self::CNAM);

	$this->testAudioAndHangup($a_channel, $b_channel);
    }

    public function testUserCall() {
        Log::notice("%s", __METHOD__);

        self::$admin_user->getUser()->quickcall(self::A_EXT);

	$a_channel = $this->waitForCall(self::$admin_device);
        $a_channel->answer();

	$b_channel = $this->waitForCall(self::$a_device);
        $b_channel->answer();

	$this->testAudioAndHangup($a_channel, $b_channel);
    }

    public function testUserAA() {
        Log::notice("%s", __METHOD__);

        self::$admin_user->getUser()->quickcall(self::A_EXT, array('auto-answer' => 'true'));

	$a_channel = $this->waitForCall(self::$admin_device);
        $this->assertEquals('true', $a_channel->getAutoAnswerDetected());
        $a_channel->answer();

	$b_channel = $this->waitForCall(self::$a_device);
        $b_channel->answer();

	$this->testAudioAndHangup($a_channel, $b_channel);
    }

    public function testAnonymous() {
        Log::notice("%s", __METHOD__);

	$url = self::$anon_device->getDevice()->getUri('/quickcall/' . self::A_EXT); 

	SDK::getInstance()->getHttpClient()->get($url, array(), array());	

	$a_channel = $this->waitForCall(self::$anon_device);
        $a_channel->answer();

	$b_channel = $this->waitForCall(self::$a_device);
        $b_channel->answer();

	$this->testAudioAndHangup($a_channel, $b_channel);
    }

    private function waitForCall($device) {
	$channel = self::getChannels()->waitForInbound($device->getSipUsername());
	$this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);
	return $channel;
    }

    private function testAudioAndHangup($a_channel, $b_channel) {
        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupBridged($a_channel, $b_channel);
    }

}
