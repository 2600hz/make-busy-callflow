<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;
use \MakeBusy\Kazoo\AbstractTestAccount;

class WelcomePrompt extends ConferenceTestCase {

    public static function setUpBeforeClass(){
        AbstractTestAccount::nukeTestAccounts("ConferenceTest");
        parent::setUpBeforeClass();
    }

    public function setUp() {
        // TODO: need a way to load callflow to set prompt
        self::$a_conference->setWelcomePrompt(self::$a_media->getId());
        self::$a_conference->enableWelcomePrompt(false);
    }

    public function tearDown() {
        self::$a_conference->reset();
    }

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$devices["a"]->originate($target) );
        self::ensureEvent( $ch_a->waitPark() );
        self::assertEmpty( $ch_a->detectTone("CONF-WELCOME"));
        self::hangupChannels($ch_a);
    }

}
