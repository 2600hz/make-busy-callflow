<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\Media;
use \MakeBusy\Kazoo\Applications\Crossbar\User;
use \MakeBusy\Kazoo\Applications\Crossbar\Conference;
use \MakeBusy\Kazoo\Applications\Crossbar\SystemConfigs;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Log;

class ConferenceTest extends CallflowTestCase
{

    private static $devices = Array();
    private static $a_user;
    private static $a_conference;
    private static $a_media;

    const CONF_EXT = '2100';
    const CONF_SERVICE_EXT = '2102';
    const CONF_NUMBER1 = 3215;
    const CONF_NUMBER2 = 3214;
    const TONE_FREQ = 1600;
    const MEMBERPIN1 = 2851;
    const MEMBERPIN2 = 1543;
    const MODERATORPIN1 = 6665;
    const MODERATORPIN2 = 4576;
    const WRONGPIN = 1221;
    const ASTERISK1 = "*1";
    const ASTERISK2 = "*2";
    const ASTERISK3 = "*3";
    const ASTERISK4 = "*4";
    const ASTERISK5 = "*5";
    const ASTERISK6 = "*6";

    public static function setUpBeforeClass(){
        parent::setUpBeforeClass();

        self::getEsl()->api("hupall");

        $test_account = self::getTestAccount();

        self::$a_conference = new Conference($test_account);
        self::$a_conference->createCallflow(array(self::CONF_EXT));
        self::$a_conference->CreateServiceCallflow(array(self::CONF_SERVICE_EXT));
        self::$a_conference->setConferenceNumbers(array((string)self::CONF_NUMBER1,(string)self::CONF_NUMBER2));

        self::$a_media = new Media($test_account);

        $media = Configuration::getSection("media");
        self::$a_media->setFile($media["welcome_prompt_path"], "audio/wav"); //need change file path in config.json

        $configs = SystemConfigs::get($test_account);
        if (! in_array("conferences", $configs)) {
            SystemConfigs::createSection($test_account, "conferences");
        }

        SystemConfigs::setSectionKey($test_account, "conferences", "entry-sound",   "tone_stream://%(3000,0,2600);loops=1");
        SystemConfigs::setSectionKey($test_account, "conferences", "exit-sound",    "tone_stream://%(3000,0,3000);loops=1");
        SystemConfigs::setSectionKey($test_account, "conferences", "deaf-sound",    "tone_stream://%(3000,0,1000);loops=1");
        SystemConfigs::setSectionKey($test_account, "conferences", "undeaf-sound",  "tone_stream://%(3000,0,1550);loops=1");
        SystemConfigs::setSectionKey($test_account, "conferences", "muted-sound",   "tone_stream://%(3000,0,1250);loops=1");
        SystemConfigs::setSectionKey($test_account, "conferences", "unmuted-sound", "tone_stream://%(3000,0,1600);loops=1");


        foreach (range('a', 'f') as $letter) {
            self::$devices[$letter] = new Device($test_account);
        }

        Profiles::loadFromAccounts();
        Profiles::syncGateways();
    }

    public function setUp(){
        // NOTE: this hangs up all channels, we may not want
        //  to do this if we plan on executing multiple tests
        //  at once
        self::getEsl()->flushEvents();
        // need function to hangup active channels
        // instead of hupall
        self::getEsl()->api("hupall");
        self::$a_conference->clearPins();
    }

    //MKBUSY-44
    public function testCallinConference(){
        Log::notice("%s", __METHOD__);
        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $this->hangupChannels($a_channel);
    }

    public function testTwoCallinConference(){
        Log::notice("%s", __METHOD__);
        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT);
        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testMassCallinConference(){
        Log::notice("%s", __METHOD__);
        $channels = Array();

        foreach (range('a', 'f') as $letter) {
            $channels[$letter] = $this->loginConference(self::$devices[$letter]->getId(),self::CONF_EXT);
        }

        foreach (range('a', 'f') as $tone_letter) {
            $this->ensureSpeaking($channels[$letter],$channels);
        }

        foreach (range('a', 'f') as $letter) {
            $channels[$letter]->hangup();
        }
    }

    //MKBUSY-45
    public function testAuthCallinConference(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));
        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,self::MEMBERPIN1);
        $a_channel->hangup();
    }

    public function testAuthTwoCallinConference(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));

        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,self::MEMBERPIN1);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,TRUE,self::MEMBERPIN2);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);
    }

    public function testAuthMassCallinConference(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));
        $channels = Array();

        foreach (range('a', 'f') as $letter) {
            $channels[$letter] = $this->loginConference(self::$devices[$letter]->getId(),self::CONF_EXT,TRUE,self::MEMBERPIN1);
        }

        foreach (range('a', 'f') as $letter) {
            $this->ensureSpeaking($channels[$letter],$channels);
        }

        foreach (range('a', 'f') as $letter) {
            $channels[$letter]->hangup();
        }
    }

    //MKBUSY-46
    public function testSpecificConference(){
        Log::notice("%s", __METHOD__);
        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);
    }

    //MBUSY-47
    public function testConferenceWelcomePrompt(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setWelcomePrompt(self::$a_media->getId());
        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,FALSE,0,TRUE,FALSE,0,FALSE,FALSE,FALSE,TRUE, 'WELCOME'); // detect freq freq name must be equal filename
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,FALSE,0,TRUE,FALSE,0,FALSE,FALSE,FALSE,TRUE, 'WELCOME'); // need spandsp update

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);

        self::$a_conference->setWelcomePrompt();
    }

    //MKBUSY-48
    public function testDisablingConferenceWelcomePrompt(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setWelcomePrompt(self::$a_media->getId());
        self::$a_conference->enableWelcomePrompt(FALSE); //disable play welcome_prompt

        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,FALSE,0,TRUE,FALSE,0,FALSE,FALSE,FALSE,FALSE);

        self::$a_conference->enableWelcomePrompt(TRUE);

        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,FALSE,0,TRUE,FALSE,0,FALSE,FALSE,FALSE,TRUE, 'WELCOME');

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);

        self::$a_conference->setWelcomePrompt();
    }

    //MBUSY-49
    public function testLoginToSpecificConference(){
        Log::notice("%s", __METHOD__);
        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_SERVICE_EXT,FALSE,0,FALSE,TRUE,self::CONF_NUMBER1); // login via conference
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_SERVICE_EXT,FALSE,0,FALSE,TRUE,self::CONF_NUMBER2);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->hangupChannels($a_channel, $b_channel);
    }

    //MKBUSY-50
    public function testMemberPin(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));

        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN1);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN2);
        $c_channel=$this->loginConference(self::$devices['c']->getId(),self::CONF_EXT,TRUE,SELF::WRONGPIN,FALSE);

        $this->expectPrompt($c_channel, "CONF-BAD_PIN", 60);

        for ($i=0; $i<2; $i++) {
            $c_channel->sendDtmf(SELF::WRONGPIN); //enter wrong confernce number
            $c_channel->sendDtmf('#');
            $this->expectPrompt($c_channel, "CONF-BAD_PIN", 60);
        }

        $this->expectPrompt($c_channel, "CONF-TOO_MANY_ATTEMPTS", 60);
        $this->assertTrue($c_channel->waitHangup());

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->ensureNotTalking($a_channel,$c_channel,600,30);
        $this->ensureNotTalking($b_channel,$c_channel,600,30);

        $this->hangupChannels($a_channel, $b_channel);
    }

    //MKBUSY-51
    public function testModeratorPin(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setModeratorPin(array((string)self::MODERATORPIN1,(string)self::MODERATORPIN2));

        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,SELF::MODERATORPIN1,TRUE,FALSE,0,TRUE);
        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,TRUE,SELF::MODERATORPIN2,TRUE,FALSE,0,TRUE);
        $c_channel = $this->loginConference(self::$devices['c']->getId(),self::CONF_EXT,TRUE,SELF::WRONGPIN,FALSE);

        $this->expectPrompt($c_channel, "CONF-BAD_PIN", 60);

        for ($i=0; $i<2; $i++) {
            $c_channel->sendDtmf(SELF::WRONGPIN); //enter wrong confernce number
            $c_channel->sendDtmf('#');
            $this->expectPrompt($c_channel, "CONF-BAD_PIN", 60);
        }

        $this->expectPrompt($c_channel, "CONF-TOO_MANY_ATTEMPTS", 60);
        $this->assertTrue($c_channel->waitHangup());

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->ensureNotTalking($a_channel,$c_channel,600,30);
        $this->ensureNotTalking($b_channel,$c_channel,600,30);

        $this->hangupChannels($a_channel, $b_channel);

        self::$a_conference->setModeratorPin(array());
    }

    //MKBUSY-52
    public function testMaxParticipants(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMaxUsers(2);

        $a_device_id = self::$devices['a']->getId();
        $b_device_id = self::$devices['b']->getId();
        $c_device_id = self::$devices['c']->getId();

        $a_channel = $this->loginConference($a_device_id,self::CONF_EXT);
        sleep(10); // need until fix KAZOO-3768
        $b_channel = $this->loginConference($b_device_id,self::CONF_EXT);
        sleep(10); // need until fix KAZOO-3768
        $c_channel = $this->loginConference($c_device_id,self::CONF_EXT,FALSE,0,FALSE);

        $this->expectPrompt($c_channel, "CONF-MAX_PARTICIPANTS", 60);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->ensureNotTalking($a_channel,$c_channel,600,30);
        $this->ensureNotTalking($b_channel,$c_channel,600,30);

        $this->hangupChannels($a_channel, $b_channel);

        self::$a_conference->setMaxUsers(NULL);
    }

    //MKBUSY-53
    public function testMultiplieJoin(){
        Log::notice("%s", __METHOD__);
        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT); //call at same time

        $this->ensureTwoWayAudio($a_channel, $b_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    //MKBUSY-54
    public function testMemberJoinMuted(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));
        self::$a_conference->setModeratorPin(array((string)self::MODERATORPIN1,(string)self::MODERATORPIN2));

        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN1);

        self::$a_conference->setMemberOption("join_muted",TRUE);

        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN2,TRUE,FALSE,0,FALSE,TRUE);
        $c_channel = $this->loginConference(self::$devices['c']->getId(),self::CONF_EXT,TRUE,SELF::MODERATORPIN1,TRUE,FALSE,0,TRUE);

        $this->ensureTwoWayAudio($a_channel, $c_channel);

        $this->ensureNotTalking($b_channel,$a_channel,600,30);
        $this->ensureNotTalking($b_channel,$c_channel,600,30);

        $this->hangupChannels($a_channel, $b_channel, $c_channel);

        self::$a_conference->setMemberOption("join_muted",FALSE);
    }

    //MKBUSY-55
    public function testMemberJoinDeaf(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));
        self::$a_conference->setModeratorPin(array((string)self::MODERATORPIN1,(string)self::MODERATORPIN2));

        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN1);
        self::$a_conference->setMemberOption("join_deaf",TRUE);
        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN2,TRUE,FALSE,0,FALSE,FALSE,TRUE);

        $c_channel=$this->loginConference(self::$devices['c']->getId(),self::CONF_EXT,TRUE,SELF::MODERATORPIN1,TRUE,FALSE,0,TRUE);

        $this->ensureTwoWayAudio($a_channel, $c_channel);

        $this->ensureNotTalking($a_channel,$b_channel,600,30);
        $this->ensureNotTalking($c_channel,$b_channel,600,30);

        $this->hangupChannels($a_channel, $b_channel, $c_channel);

        self::$a_conference->setMemberOption("join_deaf",FALSE);
    }

    //MKBUSY-56
    public function testModeratorJoinMuted(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));
        self::$a_conference->setModeratorPin(array((string)self::MODERATORPIN1,(string)self::MODERATORPIN2));

        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN1);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN2);
        self::$a_conference->setModeratorOption("join_muted",TRUE);
        $c_channel=$this->loginConference(self::$devices['c']->getId(),self::CONF_EXT,TRUE,SELF::MODERATORPIN1,TRUE,FALSE,0,TRUE,TRUE);

        $this->ensureTwoWayAudio($a_channel, $b_channel);

        $this->ensureNotTalking($c_channel,$a_channel,600,30);
        $this->ensureNotTalking($c_channel,$b_channel,600,30);

        $this->hangupChannels($a_channel, $b_channel, $c_channel);

        self::$a_conference->setModeratorOption("join_muted",FALSE);
    }

    // MKBUSY-57
    public function testModeratorJoinDeaf(){
        Log::notice("%s", __METHOD__);
        self::$a_conference->setMemberPin(array((string)self::MEMBERPIN1,(string)self::MEMBERPIN2));
        self::$a_conference->setModeratorPin(array((string)self::MODERATORPIN1,(string)self::MODERATORPIN2));

        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN1);
        self::$a_conference->setModeratorOption("join_deaf",TRUE);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT,TRUE,SELF::MEMBERPIN2);

        $c_channel=$this->loginConference(self::$devices['c']->getId(),self::CONF_EXT,TRUE,SELF::MODERATORPIN1,TRUE,FALSE,0,TRUE,FALSE,TRUE);

        $this->ensureTwoWayAudio($a_channel, $b_channel);

        $this->ensureNotTalking($a_channel,$c_channel,600,30);
        $this->ensureNotTalking($b_channel,$c_channel,600,30);

        $this->hangupChannels($a_channel, $b_channel, $c_channel);

        self::$a_conference->setModeratorOption("join_deaf",FALSE);
    }

    //MKBUSY-58
    public function testEntryTone(){
        Log::notice("%s", __METHOD__);
        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT);

        $this->expectPrompt($b_channel, "2600", 30);
        $this->ensureTwoWayAudio($a_channel, $b_channel);

        $this->hangupChannels($a_channel, $b_channel);
    }

    //MKBUSY-59
    public function testExitTone(){
        Log::notice("%s", __METHOD__);
        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $a_channel->sendDtmf("#");

        $this->expectPrompt($b_channel, "3000", 30);
        $this->hangupChannels($a_channel, $b_channel);
    }

    //MKBUSY-60
    public function testConferenceMuteFeatureCode(){
        Log::notice("%s", __METHOD__);
        $a_channel=$this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel=$this->loginConference(self::$devices['b']->getId(),self::CONF_EXT);
        $c_channel=$this->loginConference(self::$devices['c']->getId(),self::CONF_EXT);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->ensureTwoWayAudio($a_channel, $c_channel);
        $this->ensureTwoWayAudio($b_channel, $c_channel);

        $b_channel->sendDtmf(SELF::ASTERISK2);
        $this->expectPrompt($b_channel, "CONF-MUTED", 10);

        $this->ensureNotTalking($b_channel, $a_channel, 600, 10);
        $this->ensureNotTalking($b_channel, $c_channel, 600, 10);

        $b_channel->sendDtmf(SELF::ASTERISK3);
        $this->expectPrompt($b_channel, "CONF-UNMUTED", 10);
        $this->ensureTalking($b_channel, $a_channel, 600);
        $this->ensureTalking($b_channel, $c_channel, 600);

        $b_channel->sendDtmf(SELF::ASTERISK1);
        $this->expectPrompt($b_channel, "CONF-MUTED", 10);
        $this->ensureNotTalking($b_channel, $a_channel, 600, 10);
        $this->ensureNotTalking($b_channel, $c_channel, 600, 10);

        $b_channel->sendDtmf(SELF::ASTERISK1);
        $this->expectPrompt($b_channel, "CONF-UNMUTED", 10);
        $this->ensureTalking($b_channel, $a_channel, 600);
        $this->ensureTalking($b_channel, $c_channel, 600);

        $this->hangupChannels($a_channel, $b_channel, $c_channel);
    }

        //MKBUSY-61
    public function testConferenceDeafFeatureCode(){
        Log::notice("%s", __METHOD__);
        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT);
        $c_channel = $this->loginConference(self::$devices['c']->getId(),self::CONF_EXT);

        $this->ensureTwoWayAudio($a_channel, $b_channel);
        $this->ensureTwoWayAudio($a_channel, $c_channel);
        $this->ensureTwoWayAudio($b_channel, $c_channel);

        $b_channel->sendDtmf(SELF::ASTERISK5);
        $this->expectPrompt($b_channel, "CONF-DEAF", 60);
        $this->ensureNotTalking($a_channel,$b_channel,600,30);
        $this->ensureNotTalking($c_channel,$b_channel,600,30);

        $b_channel->sendDtmf(SELF::ASTERISK6);
        $this->expectPrompt($b_channel, "CONF-UNDEAF", 60);
        $this->ensureTalking($a_channel,$b_channel,600);
        $this->ensureTalking($c_channel,$b_channel,600);

        $b_channel->sendDtmf(SELF::ASTERISK4);
        $this->expectPrompt($b_channel, "CONF-DEAF", 60);
        $this->ensureNotTalking($a_channel,$b_channel,600,30);
        $this->ensureNotTalking($c_channel,$b_channel,600,30);

        $b_channel->sendDtmf(SELF::ASTERISK4);
        $this->expectPrompt($b_channel, "CONF-UNDEAF", 60);
        $this->ensureTalking($a_channel,$b_channel,600);
        $this->ensureTalking($c_channel,$b_channel,600);

        $this->hangupChannels($a_channel, $b_channel, $c_channel);
    }

    private function ensureSpeaking($speak_channel, $hear_channels){ //Speak only one other hear. First parametr speak channel. Second array of hear channels
        $speak_channel->playTone(self::TONE_FREQ, 3000, 0, 5);
        foreach ($hear_channels as $hear_channel) {
            if ($hear_channel == $speak_channel)  continue;

            $tone = $hear_channel->detectTone(self::TONE_FREQ, 2000);
            $this->assertEquals(self::TONE_FREQ, $tone);
        }
        $speak_channel->breakout();
    }

    private function loginConference($device_id,
                                     $conference_ext,
                                     $auth = FALSE,         // Login into confrence with auth or no auth. If successful, then channel can login into conference
                                     $pin=0,
                                     $successful = TRUE,
                                     $service = FALSE,      // If service TRUE, then login via ConferenceServer with parameter "conference number"
                                     $conference_number=0,
                                     $moderator=FALSE,
                                     $join_muted=FALSE,     // Mute a user when joining a conference
                                     $join_deaf=FALSE,
                                     $play_prompt=TRUE,     // If true, then play welcome prompt (CONF_WELCOME or another)
                                     $welcome_prompt=""     // Set a Welcome_Prompt or use CONF_WELCOME)
   ){
        Log::notice("%s", __METHOD__);
        $channels = self::getChannels();
        $target = $conference_ext.'@'.self::getRandomSipTarget();
        Log::debug("trying target %s", $target);
        $uuid = $channels->gatewayOriginate($device_id, $target);
        $channel = $channels->waitForOriginate($uuid);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $channel);

        if ($play_prompt) { // check if need try detect welcome_prompt
            if ($welcome_prompt) {
                $this->expectPrompt($channel, $welcome_prompt, 10); // If welcome prompt set
            } else {
                $this->expectPrompt($channel, "CONF-WELCOME", 10); // try to detect CONF-WELCOME
            }
        }

        if ($service) { //login via conference server
            $this->expectPrompt($channel, "CONF-ENTER_CONF_NUMBER", 10);
            $channel->sendDtmf($conference_number); //enter confernce number
            $channel->sendDtmf('#');
        }

        if ($auth) {
            $this->expectPrompt($channel, "CONF-ENTER_CONF_PIN", 10);
            $channel->sendDtmf($pin);
        }

        return $channel;
    }
}
