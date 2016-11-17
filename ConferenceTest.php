<?php


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

    //MKBUSY-53
    public function testMultiplieJoin(){
        Log::notice("%s", __METHOD__);
        $a_channel = $this->loginConference(self::$devices['a']->getId(),self::CONF_EXT);
        $b_channel = $this->loginConference(self::$devices['b']->getId(),self::CONF_EXT); //call at same time

        $this->ensureTwoWayAudio($a_channel, $b_channel);

        $this->hangupChannels($a_channel, $b_channel);
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


}
