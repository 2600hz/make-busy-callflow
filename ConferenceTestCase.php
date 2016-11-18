<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Common\Configuration;
use \MakeBusy\Kazoo\Applications\Crossbar\SystemConfigs;

class ConferenceTestCase extends TestCase {
    public static $devices = Array();
    public static $a_user;
    public static $a_conference;
    public static $a_media;

    const CONF_EXT = '2100';
    const CONF_SERVICE_EXT = '2102';
    const CONF_NUMBER1 = '3215';
    const CONF_NUMBER2 = '3214';
    const TONE_FREQ = '1600';
    const MEMBERPIN1 = '2851';
    const MEMBERPIN2 = '1543';
    const MODERATORPIN1 = '6665';
    const MODERATORPIN2 = '4576';
    const WRONGPIN = '1221';
    const ASTERISK1 = "*1";
    const ASTERISK2 = "*2";
    const ASTERISK3 = "*3";
    const ASTERISK4 = "*4";
    const ASTERISK5 = "*5";
    const ASTERISK6 = "*6";

    public static function setUpBeforeClass(){
        parent::setUpBeforeClass();
        $acc = new TestAccount("ConferenceTest");

        self::$a_conference = $acc->createConference();
        self::$a_conference->createCallflow([self::CONF_EXT]);
        self::$a_conference->CreateServiceCallflow([self::CONF_SERVICE_EXT]);
        self::$a_conference->setConferenceNumbers([self::CONF_NUMBER1, self::CONF_NUMBER2]);

        self::$a_media = $acc->createMedia();
        $media = Configuration::getSection("media");
        self::$a_media->setFile($media["welcome_prompt_path"], "audio/wav");

        SystemConfigs::setDefaultConfParam($acc, "enter-sound",   "tone_stream://%(1000,0,2600)");
        SystemConfigs::setDefaultConfParam($acc, "exit-sound",    "tone_stream://%(1000,0,3000)");
        SystemConfigs::setDefaultConfParam($acc, "deaf-sound",    "tone_stream://%(1000,0,1000)");
        SystemConfigs::setDefaultConfParam($acc, "undeaf-sound",  "tone_stream://%(1000,0,1550)");
        SystemConfigs::setDefaultConfParam($acc, "muted-sound",   "tone_stream://%(1000,0,1250)");
        SystemConfigs::setDefaultConfParam($acc, "unmuted-sound", "tone_stream://%(1000,0,1600)");

        foreach (range('a', 'f') as $letter) {
            self::$devices[$letter] = $acc->createDevice("auth", true);
        }
        self::sync_sofia_profile("auth", self::$devices["a"]->isLoaded(), 4);
    }

    public function loginWithPin($device, $target, $pin) {
        $ch_a = self::login($device, $target);
        self::expectPrompt($ch_a, "CONF-ENTER_CONF_PIN");
        $ch_a->sendDtmf($pin . '#');
        return $ch_a;
    }

    public function login($device, $target) {
        $ch_a = self::ensureChannel( $device->originate($target) );
        self::ensureEvent( $ch_a->waitPark() );
        self::expectPrompt($ch_a, "CONF-WELCOME");
        return $ch_a;
    }

}