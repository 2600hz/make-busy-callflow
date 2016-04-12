<?php

namespace KazooTests\Applications\Callflow;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\RingGroup;
use \MakeBusy\Kazoo\Applications\Crossbar\Resource;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;

class ParkingTest extends CallflowTestCase
{

    private static $a_device;
    private static $b_device;
    private static $c_device;
    private static $realm;

    const A_EXT             = '4001';
    const B_EXT             = '4002';
    const C_EXT             = '4003';
    const PARKING_SPOT_1    = '*3101';
    const VALET             = '*4';
    const RETRIEVE          = '*5';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        sleep(10);

        $test_account = self::getTestAccount();

        self::$a_device = new Device($test_account);
        self::$a_device->createCallflow(array(self::A_EXT));

        self::$b_device = new Device($test_account);
        self::$b_device->createCallflow(array(self::B_EXT));

        self::$c_device = new Device($test_account);
        self::$c_device->createCallflow(array(self::C_EXT));

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

    //MKBUSY-74: Attended transfer, using both park (*4) and retrieve (*5)
    public function testAttendedValetRetrieve() {
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $b_device_name = self::$b_device->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $referred_by = '<sip:' . $b_device_name
                            . '@' . Configuration::getSipGateway('auth')
                            . ':5060;transport=udp>';

            $valet = self::VALET . '@' . $sip_uri;
            //TODO: Get valet spot from prompt. We are hard coding valet spots in sequence.
            $retrieve = self::RETRIEVE . '101@' . $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $this->assertEquals($b_channel->getChannelCallState(), "ACTIVE");
            $b_channel->onHold();
            $this->assertEquals($b_channel->getChannelCallState(), "HELD");

            $uuid_2 = $channels->gatewayOriginate($b_device_id, $valet);
            $b_channel_2 = $channels->waitForOriginate($uuid_2);
            $event = $b_channel_2->waitAnswer();

            $to_tag = $event->getHeader('variable_sip_to_tag');
            $from_tag = $event->getHeader('variable_sip_from_tag');
            $sip_uri = urldecode($event->getHeader('variable_sip_req_uri'));
            $call_uuid = $event->getHeader('variable_call_uuid');

            $refer_to =     '<sip:' . $sip_uri . '101'
                     . '?Replaces=' . $call_uuid
                   . '%3Bto-tag%3D' . $to_tag
                 . '%3Bfrom-tag%3D' . $from_tag
                 . '>';

            $b_channel->setVariables('sip_h_refer-to', $refer_to);
            $b_channel->setVariables('sip_h_referred-by', $referred_by);
            $b_channel->deflect($refer_to);
            $b_channel->waitDestroy();

            $uuid_3 = $channels->gatewayOriginate($c_device_id, $retrieve);
            $c_channel = $channels->waitForOriginate($uuid_3);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $this->ensureTalking($a_channel, $c_channel);
            $this->hangupChannels($a_channel, $c_channel);

        }
    }

    //MKBUSY-72: Attended transfer, auto (park)
    public function testAttendedPark() {
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();

        $b_device_name = self::$b_device->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $referred_by = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $this->assertEquals($b_channel->getChannelCallState(), "ACTIVE");
            $b_channel->onHold();
            $this->assertEquals($b_channel->getChannelCallState(), "HELD");

            $uuid_2 = $channels->gatewayOriginate($b_device_id, $parking_spot);
            $b_channel_2 = $channels->waitForOriginate($uuid_2);
            $event = $b_channel_2->waitAnswer();

            $to_tag = $event->getHeader('variable_sip_to_tag');
            $from_tag = $event->getHeader('variable_sip_from_tag');
            $sip_uri = urldecode($event->getHeader('variable_sip_req_uri'));
            $call_uuid = $event->getHeader('variable_call_uuid');

            $refer_to =     '<sip:' . $sip_uri
                     . '?Replaces=' . $call_uuid
                   . '%3Bto-tag%3D' . $to_tag
                 . '%3Bfrom-tag%3D' . $from_tag
                 . '>';

            $b_channel->setVariables('sip_h_refer-to', $refer_to);
            $b_channel->setVariables('sip_h_referred-by', $referred_by);
            $b_channel->deflect($refer_to);
            $b_channel->waitDestroy();

            $uuid_3 = $channels->gatewayOriginate($c_device_id, $parking_spot);
            $c_channel = $channels->waitForOriginate($uuid_3);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $this->ensureTalking($a_channel, $c_channel);
            $this->hangupChannels($a_channel, $c_channel);
        }
    }

    //MKBUSY-73: Blind transfer, auto (park)
    public function testBlindPark() {
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $c_device_id = self::$c_device->getId();

        $b_device_name = self::$b_device->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $b_channel->deflect($parking_spot);

            $uuid_2 = $channels->gatewayOriginate($c_device_id, $parking_spot);
            $c_channel = $channels->waitForOriginate($uuid_2);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $this->ensureTalking($a_channel, $c_channel);
            $this->hangupChannels($a_channel, $c_channel);
        }
    }


    //MKBUSY-77: Blind transfer, park, occupied slot.
    public function testBlindParkToOccupied() {
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $c_device_id = self::$c_device->getId();

        $a_device_name = self::$a_device->getSipUsername();
        $b_device_name = self::$b_device->getSipUsername();

        $realm = self::$realm;

        foreach (self::getSipTargets() as $sip_uri) {
            $b_ext= self::B_EXT . '@' . $sip_uri;
            $b_uri = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

            $uuid_1 = $channels->gatewayOriginate($a_device_id, $b_ext);
            $b_channel_1 = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_1);

            $b_channel_1->answer();
            $a_channel = $channels->waitForOriginate($uuid_1);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);

            $b_channel_1->deflect($parking_spot);
            $b_channel_1->waitDestroy();

            $uuid_2 = $channels->gatewayOriginate($c_device_id, $b_ext);
            $b_channel_2 = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_2);

            $b_channel_2->answer();
            $c_channel = $channels->waitForOriginate($uuid_2);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $b_channel_2->setVariables('sip_h_referred-by', "$b_uri");
            $b_channel_2->deflect($parking_spot);
            $b_channel_2->waitDestroy();

            $b_channel_3 = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel_3);
            $b_channel_3->answer();

            $this->ensureTalking($b_channel_3, $c_channel);
            $this->hangupChannels($b_channel_3, $c_channel);

            $a_channel->hangup();
            $a_channel->waitDestroy();
        }
    }

    //MKBUSY-78
    //Test answering parked call ring back via attended transfer.
    public function testAttendedParkRingback() {
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();

        $b_device_name = self::$b_device->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $referred_by = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $this->assertEquals($b_channel->getChannelCallState(), "ACTIVE");
            $b_channel->onHold();
            $this->assertEquals($b_channel->getChannelCallState(), "HELD");

            $uuid_2 = $channels->gatewayOriginate($b_device_id, $parking_spot);
            $b_channel_2 = $channels->waitForOriginate($uuid_2);
            $event = $b_channel_2->waitAnswer();

            $to_tag = $event->getHeader('variable_sip_to_tag');
            $from_tag = $event->getHeader('variable_sip_to_tag');
            $call_uuid = $event->getHeader('variable_call_uuid');

            $refer_to =     '<sip:' . $sip_uri
                     . '?Replaces=' . $call_uuid
                   . '%3Bto-tag%3D' . $to_tag
                 . '%3Bfrom-tag%3D' . $from_tag
                 . '>';

            $b_channel->setVariables('sip_h_refer-to', $refer_to);
            $b_channel->setVariables('sip_h_referred-by', $referred_by);
            $b_channel->deflect($refer_to);
            $b_channel->waitDestroy();

            $ringback = $channels->waitForInbound($b_device_name, 13);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $ringback);

            $ringback->answer();
            $this->ensureTalking($ringback, $a_channel);
            $this->hangupChannels($ringback, $a_channel);
        }
    }

    //MKBUSY-79: Test attended transfer, ignore ringback.
    public function testAttendedParkIgnoreRingback() {
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();

        $b_device_name = self::$b_device->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $referred_by = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $parking_spot = self::PARKING_SPOT_1 . '@' . $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $this->assertEquals($b_channel->getChannelCallState(), "ACTIVE");
            $b_channel->onHold();
            $this->assertEquals($b_channel->getChannelCallState(), "HELD");

            $uuid_2 = $channels->gatewayOriginate($b_device_id, $parking_spot);
            $b_channel_2 = $channels->waitForOriginate($uuid_2);
            $event = $b_channel_2->waitAnswer();

            $to_tag = $event->getHeader('variable_sip_to_tag');
            $from_tag = $event->getHeader('variable_sip_to_tag');
            $call_uuid = $event->getHeader('variable_call_uuid');

            $refer_to =     '<sip:' . $sip_uri
                     . '?Replaces=' . $call_uuid
                   . '%3Bto-tag%3D' . $to_tag
                 . '%3Bfrom-tag%3D' . $from_tag
                 . '>';

            $b_channel->setVariables('sip_h_refer-to', $refer_to);
            $b_channel->setVariables('sip_h_referred-by', $referred_by);
            $b_channel->deflect($refer_to);
            $b_channel->waitDestroy();

            $ringback = $channels->waitForInbound($b_device_name, 13);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $ringback);

            sleep(20); // let it finish ringing...

            $uuid_2 = $channels->gatewayOriginate($c_device_id, $parking_spot);
            $c_channel = $channels->waitForOriginate($uuid_2);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $this->ensureTalking($a_channel, $c_channel);
            $this->hangupChannels($a_channel, $c_channel);
        }
    }

    //MKBUSY-75: Blind transfer, using
    //I don't think is a valid test since you can't hear what parking spot they valet'd it in.
    // We are assuming there are no other parked calls so the valet must be in the 2nd spot, 102.

/*  //TODO: need to get parking spot number from freeswitch instead of hard coding in a guess.

    public function testBlindValetRetrieve() {
        $channels    = self::getChannels();
        $a_device_id = self::$a_device->getId();
        $c_device_id = self::$c_device->getId();

        $b_device_name = self::$b_device->getSipUsername();

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            $valet = self::VALET . '@' . $sip_uri;
            $retrieve = self::RETRIEVE . '102@' . $sip_uri;

            $uuid = $channels->gatewayOriginate($a_device_id, $target);
            $b_channel = $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel);

            $b_channel->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $b_channel->deflect($valet);

            $uuid_2 = $channels->gatewayOriginate($c_device_id, $retrieve);
            $c_channel = $channels->waitForOriginate($uuid_2);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $c_channel);

            $this->ensureTalking($a_channel, $c_channel);
            $this->hangupChannels($a_channel, $c_channel);
        }
    }

*/

    private function ensureAnswer($bg_uuid, $b_channel){
        $channels = self::getChannels();

        $b_channel->answer();

        $a_channel = $channels->waitForOriginate($bg_uuid, 30);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $a_channel);
        $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $a_channel->waitAnswer(60));

        $a_channel->log("we are connected!");

        $this->ensureTalking($a_channel, $b_channel, 1600);
        $this->ensureTalking($b_channel, $a_channel, 600);
        $this->hangupChannels($b_channel, $a_channel);
    }

    private function ensureTalking($first_channel, $second_channel, $freq = 600){
        $first_channel->playTone($freq, 10000, 0, 5);
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

