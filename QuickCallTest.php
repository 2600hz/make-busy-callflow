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
        sleep(5);
        $test_account = self::getTestAccount();
        self::$realm = self::getTestAccount()->getAccountRealm();

        self::$admin_user = new User($test_account, array('password' => self::PASSWORD,
                                                          'allow_anoymous_quickcall' => FALSE));
        $admin_user_id = self::$admin_user->getId();
        self::$admin_device = new Device($test_account, TRUE, array('owner_id' => $admin_user_id,
                                                                    'allow_anoymous_quickcalls' => FALSE));

        self::$anon_user = new User($test_account, array('priv_level' => 'user',
                                                         'password' => self::PASSWORD,
                                                         'allow_anoymous_quickcalls' => TRUE));
        $anon_user_id = self::$anon_user->getId();
        self::$anon_device = new Device($test_account, TRUE, array('owner_id' => $anon_user_id,
                                                                   'allow_anoymous_quickcalls' => TRUE));

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

    public function testDeviceQuickCall() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam('username');
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->Device($admin_device_id)->quickcall($target);
        $this->ensureAnswer($admin_device_name, $a_sipuser);
    }

    public function testDeviceQuickCallAutoAnswer() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam("username");
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->Device($admin_device_id)->quickcall($target, array('auto_answer' => 'true'));

        $a_channel = $channels->waitForInbound($admin_device_name);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $auto_answer = $a_channel->getAutoAnswerDetected();
        $this->assertEquals($auto_answer, 'true');
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($a_sipuser);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $b_channel->answer();

        $this->ensureTalking($a_channel, $b_channel);
        $this->ensureTalking($b_channel, $a_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testDeviceQuickCallCidName() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam("username");
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->Device($admin_device_id)->quickcall($target, array('cid-name' => self::CNAM));

        $a_channel = $channels->waitForInbound($admin_device_name);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($a_sipuser);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $cid_name = $b_channel->getCallerIdName();
        $this->assertEquals($cid_name, self::CNAM);
        $b_channel->answer();

        $this->ensureTalking($a_channel, $b_channel);
        $this->ensureTalking($b_channel, $a_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testDeviceQuickCallCidNumber() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam("username");
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->Device($admin_device_id)->quickcall($target, array('cid-number' => self::CNUM));

        $a_channel = $channels->waitForInbound($admin_device_name);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($a_sipuser);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $cid_number = $b_channel->getCallerIdNumber();
        $this->assertEquals($cid_number, self::CNUM);
        $b_channel->answer();

        $this->ensureTalking($a_channel, $b_channel);
        $this->ensureTalking($b_channel, $a_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testDeviceQuickCallAllowAnon() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id = self::$admin_device->getId();
        $admin_device_name = self::$admin_device->getSipUsername();
        $admin_username = self::$admin_user->getUserParam("username");

        $anon_device_id    = self::$anon_device->getId();
        $anon_device_name  = self::$anon_device->getSipUsername();
        $anon_username     = self::$anon_user->getUserParam("username");
        $a_sipuser          = self::$a_device->getSipUsername();

        // allow_anoymous = false; no auth_token
        $quickcall_1 = $this->quickCall("devices", $admin_device_id, $target);
        $this->assertEquals($quickcall_1->data, "invalid credentials");

        // allow_anoymous = false; valid auth_token
        $admin_auth_token = $this->generateAuthToken($admin_username, self::PASSWORD, self::$realm);
        $quickcall_2 = $this->quickCall("devices", $admin_device_id, $target, $admin_auth_token);
        $this->assertEquals($quickcall_2->status, "success");
        $this->ensureAnswer($admin_device_name, $a_sipuser);

        // allow_anoymous = false; invalid auth_token
        $quickcall_5 = $this->quickCall("devices", $admin_device_id, $target, $anon_device_id);
        $this->assertEquals($quickcall_5->data, "invalid credentials");

        // allow_anoymous = true; no auth_token
        $this->quickCall("devices", $anon_device_id, $target, NULL);
        $this->ensureAnswer($anon_device_name, $a_sipuser);

        // allow_anoymous = true; anon_auth_token
        $anon_auth_token = $this->generateAuthToken($anon_username, self::PASSWORD, self::$realm);
        $quickcall_3 = $this->quickCall("devices", $anon_device_id, $target, $anon_auth_token);
        $this->ensureAnswer($anon_device_name, $a_sipuser);

        // allow_anoymous = true; admin_auth_token
        $quickcall_4 = $this->quickCall("devices", $anon_device_id, $target, $admin_auth_token);
        $this->ensureAnswer($anon_device_name, $a_sipuser);

    }

    public function testUserQuickCall() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam('username');
        $admin_user_id      = self::$admin_user->getId();
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->User($admin_user_id)->quickcall($target);
        $this->ensureAnswer($admin_device_name, $a_sipuser);
    }

    public function testUserQuickCallAutoAnswer() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam("username");
        $admin_user_id      = self::$admin_user->getId();
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->User($admin_user_id)->quickcall($target, array('auto_answer' => 'true'));

        $a_channel = $channels->waitForInbound($admin_device_name);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $auto_answer = $a_channel->getAutoAnswerDetected();
        $this->assertEquals($auto_answer, 'true');
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($a_sipuser);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $b_channel->answer();

        $this->ensureTalking($a_channel, $b_channel);
        $this->ensureTalking($b_channel, $a_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testUserQuickCallCidName() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam("username");
        $admin_user_id      = self::$admin_user->getId();
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->User($admin_user_id)->quickcall($target, array('cid-name' => self::CNAM));

        $a_channel = $channels->waitForInbound($admin_device_name);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($a_sipuser);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $cid_name = $b_channel->getCallerIdName();
        $this->assertEquals($cid_name, self::CNAM);
        $b_channel->answer();

        $this->ensureTalking($a_channel, $b_channel);
        $this->ensureTalking($b_channel, $a_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testUserQuickCallCidNumber() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id    = self::$admin_device->getId();
        $admin_device_name  = self::$admin_device->getSipUsername();
        $admin_username     = self::$admin_user->getUserParam("username");
        $admin_user_id      = self::$admin_user->getId();
        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($admin_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->User($admin_user_id)->quickcall($target, array('cid-number' => self::CNUM));

        $a_channel = $channels->waitForInbound($admin_device_name);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($a_sipuser);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $cid_number = $b_channel->getCallerIdNumber();
        $this->assertEquals($cid_number, self::CNUM);
        $b_channel->answer();

        $this->ensureTalking($a_channel, $b_channel);
        $this->ensureTalking($b_channel, $a_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }
/*

    Expecting to ring all assigned device simultaneously, like a ring group, but only 1 of 3 devices are ringing.
    bug report: KAZOO-3875

    public function testUserQuickCallMultiDev() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $b_user_username = self::$b_user->getUserParam("username");
        $b_user_id  = self::$b_user->getId();
        $b_dev_1_sipname = self::$b_dev_1->getSipUsername();
        $b_dev_2_sipname = self::$b_dev_2->getSipUsername();
        $b_dev_3_sipname = self::$b_dev_3->getSipUsername();

        $a_sipuser          = self::$a_device->getSipUsername();

        $param      = Configuration::getSection('sdk');
        $options    = array('base_url' => $param['base_url']);
        $auth_user  = new AuthUser($b_user_username, self::PASSWORD, self::$realm);
        $sdk        = new KazooSDK($auth_user, $options);

        $sdk->Account()->User($b_user_id)->quickcall($target, array('auto_answer' => FALSE));

        var_dump($b_dev_1_sipname);
        var_dump($b_dev_2_sipname);
        var_dump($b_dev_3_sipname);

        $a_channel = $channels->waitForInbound($b_dev_1_sipname);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $b_channel = $channels->waitForInbound($b_dev_2_sipname);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $c_channel = $channels->waitForInbound($b_dev_3_sipname);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);
        // The target device doesn't seem to ring until 1 of the device is picked up.
        // We can not assert this unless we answer.
        //$d_channel = $channels->waitForInbound($a_sipuser);
        //$this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $d_channel);
    }

    We are giving away free calls, bug report: KAZOO-3873.

    public function testUserQuickCallAllowAnon() {
        $channels   = self::getChannels();
        $target     = self::A_EXT;

        $admin_device_id = self::$admin_device->getId();
        $admin_device_name = self::$admin_device->getSipUsername();
        $admin_username = self::$admin_user->getUserParam("username");
        $admin_user_id = self::$admin_user->getId();

        $anon_device_id    = self::$anon_device->getId();
        $anon_device_name  = self::$anon_device->getSipUsername();
        $anon_username     = self::$anon_user->getUserParam("username");
        $anon_user_id       = self::$anon_user->getId();
        $a_sipuser          = self::$a_device->getSipUsername();

        $b_user_id = self::$b_user->getId();
        $b_dev_1_sipuser = self::$b_dev_1->getSipUsername();

        // allow_anoymous = false; no auth_token
        $quickcall_1 = $this->quickCall("users", $b_user_id, $target, NULL);
        $this->assertEquals($quickcall_1->data, "invalid credentials");

        // allow_anoymous = false; valid auth_token
        $admin_auth_token = $this->generateAuthToken($admin_username, self::PASSWORD, self::$realm);
        $quickcall_2 = $this->quickCall("users", $admin_user_id, $target, $admin_auth_token);
        $this->assertEquals($quickcall_2->status, "success");
        $this->ensureAnswer($admin_device_name, $a_sipuser);

        // allow_anoymous = false; invalid auth_token
        $quickcall_5 = $this->quickCall("users", $admin_user_id, $target, $anon_device_id);
        $this->assertEquals($quickcall_5->data, "invalid credentials");

        // allow_anoymous = true; no auth_token
        $this->quickCall("users", $anon_user_id, $target, NULL);
        $this->ensureAnswer($anon_device_name, $a_sipuser);

        // allow_anoymous = true; anon_auth_token
        $anon_auth_token = $this->generateAuthToken($anon_username, self::PASSWORD, self::$realm);
        $quickcall_3 = $this->quickCall("users", $anon_user_id, $target, $anon_auth_token);
        $this->ensureAnswer($anon_device_name, $a_sipuser);

        // allow_anoymous = true; admin_auth_token
        $quickcall_4 = $this->quickCall("users", $anon_user_id, $target, $admin_auth_token);
        $this->ensureAnswer($anon_device_name, $a_sipuser);
    }

 */

    private function quickCall($user_or_dev, $user_dev_id, $target, $auth_token = NULL) {
        $account_id  = self::getTestAccount()->getAccountId();

        $url = 'http://192.168.56.101:8000/v1';
        $url .= '/accounts/' . $account_id;
        $url .= "/$user_or_dev/" . $user_dev_id;
        $url .= '/quickcall/' . $target;
        if(isset($auth_token)) {
            $url .= '?auth_token=' . $auth_token;
        }

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

    private function generateAuthToken($username, $password, $realm) {
        $url = 'http://192.168.56.101:8000/v1/user_auth';

        $string = "$username:$password";
        $string = rtrim($string, "\n");
        $credentials = md5($string);

        $data = '{ "data" : { "credentials" : "' . $credentials . '", "realm" : "'. $realm . '"},"verb":"PUT"}';

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS,$data);

        $result = curl_exec($curl);
        if(curl_errno($curl)) {
            throw new Exception('Auth Token Generation Failed', curl_errno($curl));
        }
        curl_close($curl);
        $result = json_decode($result);
        return $result->auth_token;
    }

    private function ensureAnswer($a_user, $b_user) {
        $channels   = self::getChannels();

        $a_channel = $channels->waitForInbound($a_user);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $a_channel->answer();

        $b_channel = $channels->waitForInbound($b_user);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
        $b_channel->answer();

        $this->ensureTalking($a_channel, $b_channel);
        $this->ensureTalking($b_channel, $a_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    private function ensureTalking($first_channel, $second_channel, $freq = 600){
        $first_channel->playTone($freq, 3000, 0, 5);
        $tone = $second_channel->detectTone($freq, 20);
        $first_channel->breakout();
        $this->assertEquals($freq, $tone);
    }

    private function hangupChannels($hangup_channel, $other_channels){
        $hangup_channel->hangup();
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $hangup_channel->waitDestroy(30));

        if (is_array($other_channels)){
            foreach ($other_channels as $channel){
                $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $channel->waitDestroy(30));
            }
        } else {
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $other_channels->waitDestroy(60));
        }
    }
}
