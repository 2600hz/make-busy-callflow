<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Kazoo\Applications\Crossbar\Webhook;
use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Common\Log;

class WebhookTest extends DeviceTestCase
{

    private static $a_device;
    private static $b_device;

    const B_EXT = '7002';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $test_account = self::getTestAccount();

        self::$a_device = new Device($test_account);

        self::$b_device = new Device($test_account);
        self::$b_device->createCallflow(array(self::B_EXT));

        $uri = Configuration::getWebhooksUri();

        $webhook_create  = new Webhook($test_account, array('uri' => "$uri",
                                                           'hook' => 'channel_create'));

        $webhook_answer  = new Webhook($test_account, array('uri' => "$uri",
                                                           'hook' => 'channel_answer'));

        $webhook_destory = new Webhook($test_account, array('uri' => "$uri",
                                                           'hook' => 'channel_destroy'));

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

    public function testWebhookBasic() {
        Log::notice("%s", __METHOD__);
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_sipuser   = self::$b_device->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT. '@' . $sip_uri;
            $uuid = $channels->gatewayOriginate($a_device_id, $target);
            $b_channel = $channels->waitForInbound($b_sipuser, 10);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $a_channel = $this->ensureAnswer($uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
	    $a_leg = $a_channel->getUuid();
	    $b_leg = $b_channel->getUuid();
            $this->hangupBridged($a_channel, $b_channel);
        }

        sleep(2); // allow time for destroy to come in.
        $a_leg_create  = "/tmp/$a_leg" . "_inbound_create.log";
        $a_leg_answer  = "/tmp/$a_leg" . "_inbound_answer.log";
        $a_leg_destroy = "/tmp/$a_leg" . "_inbound_destroy.log";

        $this->assertTrue(file_exists($a_leg_create));
        $this->assertTrue(file_exists($a_leg_answer));
        $this->assertTrue(file_exists($a_leg_destroy));

        $b_leg_create  = "/tmp/$b_leg" . "_outbound_create.log";
        $b_leg_answer  = "/tmp/$b_leg" . "_outbound_answer.log";
        $b_leg_destroy = "/tmp/$b_leg" . "_outbound_destroy.log";

        $this->assertTrue(file_exists($b_leg_create));
        $this->assertTrue(file_exists($b_leg_answer));
        $this->assertTrue(file_exists($b_leg_destroy));

        unlink(realpath($a_leg_create));
        unlink(realpath($a_leg_answer));
        unlink(realpath($a_leg_destroy));

        unlink(realpath($b_leg_create));
        unlink(realpath($b_leg_answer));
        unlink(realpath($b_leg_destroy));
    }

}

