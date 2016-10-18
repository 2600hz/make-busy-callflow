<?php

namespace KazooTests\Applications\Callflow;

use \Kazoo\SDK as KazooSDK;
use \Kazoo\AuthToken\User as AuthUser;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

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
        $this->ensureQuickCallAnswer(self::$admin_device->getSipUsername(), self::$a_device->getSipUsername());
    }

    public function testDeviceAA() {
        Log::notice("%s", __METHOD__);

        self::$admin_device->getDevice()->quickcall(self::A_EXT, array('auto_answer' => 'true'));

        $a_channel = self::getChannels()->waitForInbound(self::$admin_device->getSipUsername());
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $auto_answer = $a_channel->getAutoAnswerDetected();
        $this->assertEquals('true', $auto_answer);
        $a_channel->answer();

        $b_channel = self::getChannels()->waitForInbound(self::$a_device->getSipUsername());
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $b_channel->answer();

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testDeviceCID() {
	$this->markTestIncomplete('Known issue: KAZOO-5118');
        Log::notice("%s", __METHOD__);

        self::$admin_device->getDevice()->quickcall(self::A_EXT, array('cid-number' => self::CNUM, 'cid-name' => self::CNAM));

        $a_channel = self::getChannels()->waitForInbound(self::$admin_device->getSipUsername());
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $auto_answer = $a_channel->getAutoAnswerDetected();
        $a_channel->answer();

        $b_channel = self::getChannels()->waitForInbound(self::$a_device->getSipUsername());
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $b_channel->answer();

        $this->assertEquals($b_channel->getCallerIdNumber(), self::CNUM);
        $this->assertEquals($b_channel->getCallerIdName(), self::CNAM);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testUserCall() {
        Log::notice("%s", __METHOD__);

        self::$admin_user->getUser()->quickcall(self::A_EXT);
        $this->ensureQuickCallAnswer(self::$admin_device->getSipUsername(), self::$a_device->getSipUsername());
    }

    public function testUserAA() {
        Log::notice("%s", __METHOD__);

        self::$admin_user->getUser()->quickcall(self::A_EXT, array('auto-answer' => 'true'));

        $a_channel = self::getChannels()->waitForInbound(self::$admin_device->getSipUsername());
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $auto_answer = $a_channel->getAutoAnswerDetected();
        $this->assertEquals('true', $auto_answer);
        $a_channel->answer();

        $b_channel = self::getChannels()->waitForInbound(self::$a_device->getSipUsername());
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $b_channel->answer();

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);
    }


    public function testAnonymous() {
        Log::notice("%s", __METHOD__);
	$url = self::$anon_device->getDevice()->getUri('/quickcall/' . self::A_EXT); 
	$this->curl($url);	
        $this->ensureQuickCallAnswer(self::$anon_device->getSipUsername(), self::$a_device->getSipUsername());
    }

    private function curl($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        if(curl_errno($curl)) {
            throw new Exception('QuickCall Failure', curl_errno($curl));
        }
        curl_close($curl);
        $result = json_decode($result);
        return $result;
    }

    private function ensureQuickCallAnswer($a_user, $b_user) {
        $channels   = self::getChannels();

        $a_channel = $channels->waitForInbound($a_user);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($b_user);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $b_channel->answer();

        $this->ensureTwoWayAudio($a_channel, $b_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

}
