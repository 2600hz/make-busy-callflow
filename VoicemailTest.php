<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\User;
use \MakeBusy\Kazoo\Applications\Crossbar\Voicemail;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Log;

class VoicemailTest extends CallflowTestCase
{
    private static $a_device;
    private static $b_user;
    private static $b_device;
    private static $b_voicemail_box;
    const VM_CHECK_NUMBER   = '3001';
    const VM_ACCESS_NUMBER  = '3002';
    const VM_BOX_ID         = '3000';
    const VM_CHECK_CODE     = '*97';
    const VM_COMPOSE_B_CODE = '**3000';
    const B_USER_NUMBER     = '3000';

    const DEFAULT_PIN = '0000';
    const CHANGE_PIN  = '1111';

    public function setUp() {
        // NOTE: this hangs up all channels, we may not want
        //  to do this if we plan on executing multiple tests
        //  at once
        self::getEsl()->api("hupall");
        self::getEsl()->flushEvents();
    }

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        sleep(5);

        $test_account = self::getTestAccount();

        self::$b_voicemail_box = new Voicemail($test_account, self::VM_BOX_ID);
        self::$b_user          = new User($test_account);
        self::$a_device        = new Device($test_account);
        self::$b_device        = new Device($test_account, TRUE, array('owner_id' => self::$b_user->getId()));

        self::$b_voicemail_box->createCallflow(array(self::VM_ACCESS_NUMBER));
        self::$b_voicemail_box->createUserVmCallflow(array(self::B_USER_NUMBER), self::$b_user->getId());
        self::$b_voicemail_box->createCheckCallflow(array(self::VM_CHECK_NUMBER));

        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();

        Profiles::loadFromAccounts();
        Profiles::syncGateways();

        //set defaults, box should be setup when entering a test, we can force setup by setting is_setup=FALSE later.
        self::$b_voicemail_box->setVoicemailboxParam('owner_id', self::$b_user->getId());
        self::$b_voicemail_box->setVoicemailboxParam('is_setup', TRUE);
    }

    //MKBUSY-25
    public function testSetupOwnUser() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $b_device_id = self::$b_device->getId();

        foreach (self::getSipTargets() as $sip_uri){
            self::$b_voicemail_box->setVoicemailboxParam("is_setup", FALSE);
            $target  = self::B_USER_NUMBER . '@'. $sip_uri;
            $uuid    = $channels->gatewayOriginate($b_device_id, $target);
            $channel = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);

            $this->expectPrompt($channel, "VM-ENTER_PASS", 10);

            $channel->sendDtmf(self::DEFAULT_PIN);

            $this->expectPrompt($channel, "VM-SETUP_INTRO", 20);

            $this->expectPrompt($channel, "VM-ENTER_NEW_PIN", 10);

            $channel->sendDtmf(self::CHANGE_PIN);

            $this->expectPrompt($channel, "VM-ENTER_NEW_PIN_CONFIRM", 10);

            $channel->sendDtmf(self::CHANGE_PIN);

            $this->expectPrompt($channel, "VM-PIN_SET", 10);

            $this->expectPrompt($channel, "VM-SETUP_REC_GREETING", 10);
            $this->expectPrompt($channel, "VM-RECORD_GREETING", 10);

            $channel->playTone( "600", 2000);
            $channel->sendDtmf("1");

            $this->expectPrompt($channel, "VM-REVIEW_RECORDING", 10);

            $channel->sendDtmf("2");

            $tone = $channel->detectTone("600", 10);
            $this->assertEquals("600", $tone);

            $this->expectPrompt($channel, "VM-REVIEW_RECORDING", 10);

            $channel->sendDtmf("1");

            $this->expectPrompt($channel, "VM-SAVED", 10);
            $this->expectPrompt($channel, "VM-SETUP_COMPLETE", 10);
            $this->expectPrompt($channel, "VM-NO_MESSAGES", 10);
            $this->expectPrompt($channel, "VM-MAIN_MENU", 10);

            $channel->hangup();
            $channel->waitHangup();

            $this->assertEquals(self::$b_voicemail_box->getVoicemailboxParam("pin"), self::CHANGE_PIN);
            $this->assertTrue(self::$b_voicemail_box->getVoicemailboxParam("is_setup"));
            $this->assertNotEmpty(self::$b_voicemail_box->getVoicemailboxParam("media")->unavailable);

            self::$b_voicemail_box->resetVoicemailBox();
        }
    }

    //MKBUSY-25
    public function testSetupOtherUser() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();

        foreach (self::getSipTargets() as $sip_uri){
            self::$b_voicemail_box->setVoicemailboxParam("is_setup", FALSE);

            $target  = self::VM_CHECK_NUMBER .'@'. $sip_uri;
            $uuid    = $channels->gatewayOriginate($a_device_id, $target);
            $channel = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);

            $this->expectPrompt($channel, "VM-ENTER_ID", 10);

            $channel->sendDtmf(self::VM_BOX_ID);

            $this->expectPrompt($channel, "VM-ENTER_PASS", 10);

            $channel->sendDtmf(self::DEFAULT_PIN);

            $this->expectPrompt($channel, "VM-SETUP_INTRO", 10);
            $this->expectPrompt($channel, "VM-ENTER_NEW_PIN", 10);

            $channel->sendDtmf(self::CHANGE_PIN);

            $this->expectPrompt($channel, "VM-ENTER_NEW_PIN_CONFIRM", 10);

            $channel->sendDtmf(self::CHANGE_PIN);

            $this->expectPrompt($channel, "VM-PIN_SET", 10);
            $this->expectPrompt($channel, "VM-SETUP_REC_GREETING", 10);
            $this->expectPrompt($channel, "VM-RECORD_GREETING", 10);

            $channel->playTone( "600", 2000);
            $channel->sendDtmf("1");

            $this->expectPrompt($channel, "VM-REVIEW_RECORDING", 10);

            $channel->sendDtmf("2");

            $this->expectPrompt($channel, "600", 10);
            $this->expectPrompt($channel, "VM-REVIEW_RECORDING", 10);

            $channel->sendDtmf("1");

            $this->expectPrompt($channel, "VM-SAVED", 10);
            $this->expectPrompt($channel, "VM-SETUP_COMPLETE", 10);
            $this->expectPrompt($channel, "VM-NO_MESSAGES", 10);
            $this->expectPrompt($channel, "VM-MAIN_MENU", 10);

            $channel->hangup();
            $channel->waitHangup();

            $this->assertEquals(self::$b_voicemail_box->getVoicemailboxParam("pin"), self::CHANGE_PIN);
            $this->assertTrue(self::$b_voicemail_box->getVoicemailboxParam("is_setup"));
            $this->assertNotEmpty(self::$b_voicemail_box->getVoicemailboxParam("media")->unavailable);

            self::$b_voicemail_box->resetVoicemailBox();
        }

    }

    // MKBUSY-26
    public function testUserChangePin(){
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();

        $b_device_id = self::$b_device->getId();

        foreach (self::getSipTargets() as $sip_uri){
            $target  = self::B_USER_NUMBER . '@' . $sip_uri;
            $uuid    = $channels->gatewayOriginate($b_device_id, $target);
            $channel = $channels->waitForOriginate($uuid);

            $this->expectPrompt($channel, "VM-ENTER_PASS", 10);

            $channel->sendDtmf(self::DEFAULT_PIN);

            $this->expectPrompt($channel, "VM-NO_MESSAGES", 10);
            $this->expectPrompt($channel, "VM-MAIN_MENU", 10);

            $channel->sendDtmf("5");

            $this->expectPrompt($channel, "VM-SETTINGS_MENU", 10);

            $channel->sendDtmf("3");

            $this->expectPrompt($channel, "VM-ENTER_NEW_PIN", 10);

            $channel->sendDtmf(self::CHANGE_PIN);

            $this->expectPrompt($channel, "VM-ENTER_NEW_PIN_CONFIRM", 10);

            $channel->sendDtmf(self::CHANGE_PIN);

            $this->expectPrompt($channel, "VM-PIN_SET", 10);

            $channel->hangup();
            $channel->waitHangup();

            $this->assertEquals(self::$b_voicemail_box->getVoicemailboxParam("pin"), self::CHANGE_PIN);

            self::$b_voicemail_box->resetVoicemailBox();
       }
    }

    //MKBUSY-28
    public function testCheckOtherMailboxNoMessages(){
        Log::notice("%s", __METHOD__);

        $channels = self::getChannels();

        $a_device_id = self::$a_device->getId();

        foreach (self::getSipTargets() as $sip_uri){
            $target  = self::B_USER_NUMBER . '@' . $sip_uri;
            $uuid    = $channels->gatewayOriginate($a_device_id, $target);
            $channel = $channels->waitForOriginate($uuid, 60);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Esl\Event", $channel->waitAnswer(60));

            $this->expectPrompt($channel, "VM-PERSON");

            $channel->sendDtmf("*");

            $this->expectPrompt($channel, "VM-ENTER_PASS");

            $channel->sendDtmf(self::DEFAULT_PIN);

            $this->expectPrompt($channel, "VM-NO_MESSAGES");

            $channel->hangup();
            $channel->waitHangup();

            self::$b_voicemail_box->resetVoicemailBox("pin", "0000");
       }
    }

    //MKBUSY-27
    public function testLeaveMessage() {
        Log::notice("%s", __METHOD__);
        $channels  = self::getChannels();

        $b_device_id = self::$b_device->getId();
        $a_device_id = self::$a_device->getId();

        foreach (self::getSipTargets() as $sip_uri){
            $target = self::B_USER_NUMBER . '@' . $sip_uri;

            $this->leaveMessage($a_device_id, $target, "600");

            $messages = self::$b_voicemail_box->getVoicemailboxParam("messages");
            $this->assertNotNull($messages[0]->media_id);

            $uuid    = $channels->gatewayOriginate($b_device_id, $target);
            $b_channel = $channels->waitForOriginate($uuid);

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);

            $this->expectPrompt($b_channel, "VM-ENTER_PASS", 10);
            $b_channel->sendDtmf(self::DEFAULT_PIN);


            $this->expectPrompt($b_channel, "VM-YOU_HAVE", 10);
            $this->expectPrompt($b_channel, "VM-NEW_MESSAGE", 10);
            $this->expectPrompt($b_channel, "VM-MAIN_MENU", 10);

            $b_channel->sendDtmf('1');

            $this->expectPrompt($b_channel, "VM-MESSAGE_NUMBER", 10);

            $this->expectPrompt($b_channel, "600", 60);

            $this->expectPrompt($b_channel, "VM-RECEIVED", 10);
            $this->expectPrompt($b_channel, "VM-MESSAGE_MENU", 10);

            $b_channel->sendDtmf('7');

            $this->expectPrompt($b_channel, "VM-DELETED", 10);

            $b_channel->hangup();
            $b_channel->waitHangup();

            self::$b_voicemail_box->resetVoicemailBox();
            self::$b_voicemail_box->resetVoicemailBoxParam("media");
        }
    }

    //MKBUSY 27
    public function testLeaveMessageRerecord() {
        Log::notice("%s", __METHOD__);
        $channels  = self::getChannels();

        $b_device_id  = self::$b_device->getId();
        $a_device_id = self::$a_device->getId();

        self::$b_user->setUserParam("vm_to_email_enabled",FALSE);

        self::$b_voicemail_box->getVoicemailbox();
        $count  = count(self::$b_voicemail_box->getVoicemailboxParam("messages"));

        foreach (self::getSipTargets() as $sip_uri){
            $target = self::B_USER_NUMBER . '@' . $sip_uri;

            self::$b_user->setUserParam("vm_to_email_enabled",TRUE);

            $this->leaveMessage($a_device_id, $target, "600", "1600");

            $messages = self::$b_voicemail_box->getVoicemailboxParam("messages");

            $this->assertNotNull($messages[$count]->media_id);

            $messages = self::$b_voicemail_box->getVoicemailboxParam("messages");

            $this->assertNotNull($messages[0]->media_id);

            $uuid      = $channels->gatewayOriginate($b_device_id, $target);
            $b_channel = $channels->waitForOriginate($uuid, 10);
            $b_channel->waitAnswer();

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);

            $this->expectPrompt($b_channel, "VM-ENTER_PASS", 10);
            $b_channel->sendDtmf(self::DEFAULT_PIN);

            $this->expectPrompt($b_channel, "VM-YOU_HAVE", 10);
            $this->expectPrompt($b_channel, "VM-NEW_MESSAGE", 10);
            $this->expectPrompt($b_channel, "VM-MAIN_MENU", 10);

            $b_channel->sendDtmf('1');

            $this->expectPrompt($b_channel, "VM-MESSAGE_NUMBER", 10);
            $this->expectPrompt($b_channel, "1600", 10);
            $this->expectPrompt($b_channel, "VM-RECEIVED", 10);
            $this->expectPrompt($b_channel, "VM-MESSAGE_MENU", 10);

            $b_channel->sendDtmf('7');
            $this->expectPrompt($b_channel, "VM-DELETED", 10);

            $b_channel->hangup();
            $b_channel->waitHangup();

            self::$b_voicemail_box->resetVoicemailBox();
            self::$b_voicemail_box->resetVoicemailBoxParam("media");
        }
    }

    //MKBUSY-29
    public function testDeleteAfterNotify() {
        Log::notice("%s", __METHOD__);
        $channels  = self::getChannels();

        $a_device_id = self::$a_device->getId();
        $b_user_id   = self::$b_user->getId();
        $source = self::$b_voicemail_box->getVoicemailboxParam("messages");

        self::$b_voicemail_box->setVoicemailBoxParam('owner_id',$b_user_id);
        self::$b_voicemail_box->setVoicemailBoxParam('delete_after_notify',TRUE);

        foreach (self::getSipTargets() as $sip_uri){
            $target = self::B_USER_NUMBER . '@' . $sip_uri;

            $this->leaveMessage($a_device_id, $target, "600");

            $this->assertEquals(self::$b_voicemail_box->getVoicemailboxParam("messages"), $source);

            self::$b_voicemail_box->resetVoicemailBox();
            self::$b_voicemail_box->resetVoicemailBoxParam("media");
            self::$b_voicemail_box->setVoicemailBoxParam('delete_after_notify',FALSE);
        }
    }

    //MKBUSY-34
    public function testSaveMessage() {
        Log::notice("%s", __METHOD__);
        $channels  = self::getChannels();

        $b_device_id  = self::$b_device->getId();
        $a_device_id = self::$a_device->getId();

        self::$b_user->setUserParam("vm_to_email_enabled",FALSE);

        self::$b_voicemail_box->getVoicemailbox();
        $count  = count(self::$b_voicemail_box->getVoicemailboxParam("messages"));

        foreach (self::getSipTargets() as $sip_uri){
            $target = self::B_USER_NUMBER . '@' . $sip_uri;


            $this->leaveMessage($a_device_id, $target, "600");

            $messages = self::$b_voicemail_box->getVoicemailboxParam("messages");

            $this->assertNotNull($messages[$count]->media_id);

            $messages = self::$b_voicemail_box->getVoicemailboxParam("messages");

            $this->assertNotNull($messages[0]->media_id);

            $uuid      = $channels->gatewayOriginate($b_device_id, $target);
            $b_channel = $channels->waitForOriginate($uuid, 10);
            $b_channel->waitAnswer();

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);

            $this->expectPrompt($b_channel, "VM-ENTER_PASS", 10);
            $b_channel->sendDtmf(self::DEFAULT_PIN);

            $this->expectPrompt($b_channel, "VM-YOU_HAVE", 10);
            $this->expectPrompt($b_channel, "VM-NEW_MESSAGE", 10);
            $this->expectPrompt($b_channel, "VM-MAIN_MENU", 10);

            $b_channel->sendDtmf('1');

            $this->expectPrompt($b_channel, "VM-MESSAGE_NUMBER", 10);
            $this->expectPrompt($b_channel, "600", 10);
            $this->expectPrompt($b_channel, "VM-RECEIVED", 10);
            $this->expectPrompt($b_channel, "VM-MESSAGE_MENU", 10);

            $b_channel->sendDtmf('1');
            $this->expectPrompt($b_channel, "VM-SAVED", 10);

            $b_channel->hangup();
            $b_channel->waitHangup();

            //Call Back and make sure saved vm was "saved" and can be retrieved
            $uuid      = $channels->gatewayOriginate($b_device_id, $target);
            $b_channel = $channels->waitForOriginate($uuid, 10);
            $b_channel->waitAnswer();

            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);

            $this->expectPrompt($b_channel, "VM-ENTER_PASS", 10);
            $b_channel->sendDtmf(self::DEFAULT_PIN);

            $this->expectPrompt($b_channel, "VM-YOU_HAVE", 10);
            $this->expectPrompt($b_channel, "VM-SAVED_MESSAGE", 10);
            $this->expectPrompt($b_channel, "VM-MAIN_MENU", 10);

            $b_channel->sendDtmf('2');

            $this->expectPrompt($b_channel, "VM-MESSAGE_NUMBER", 10);
            $this->expectPrompt($b_channel, "600", 10);
            $this->expectPrompt($b_channel, "VM-RECEIVED", 10);
            $this->expectPrompt($b_channel, "VM-MESSAGE_MENU", 10);

            $b_channel->sendDtmf('7');
            $this->expectPrompt($b_channel, "VM-DELETED", 10);

            $b_channel->hangup();
            $b_channel->waitHangup();

            self::$b_voicemail_box->resetVoicemailBox();
            self::$b_user->setUserParam("vm_to_email_enabled", TRUE);
            self::$b_voicemail_box->resetVoicemailBoxParam("media");
        }
    }

}
