<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class TransferAttendedTest extends CallflowTestCase {

    public function testMain() {
        $channels    = self::getChannels("auth");
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $b_device_name = self::$b_device->getSipUsername();
        $c_device_name = self::$c_device->getSipUsername();

        $uuid_base = "testDeviceAttendedTransfer-";

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT . '@' . $sip_uri;
            Log::debug("trying target %s", $target);
            $referred_by = '<sip:' . $b_device_name . '@' . Configuration::getSipGateway('auth') . ':5060;transport=udp>';
            $transferee = self::C_EXT . '@' . $sip_uri;

            $options = array("origination_uuid" => $uuid_base . "aleg-" . Utils::randomString(8));
            $uuid = $channels->gatewayOriginate($a_device_id, $target, $options);
            $b_channel_1= $channels->waitForInbound($b_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel_1);

            $b_channel_1->answer();
            $a_channel = $channels->waitForOriginate($uuid);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $a_channel);

            $this->ensureTwoWayAudio($a_channel, $b_channel_1);

            $this->assertEquals($b_channel_1->getChannelCallState(), "ACTIVE");
            $b_channel_1->onHold();
            $this->assertEquals($b_channel_1->getChannelCallState(), "HELD");

            $options = array("origination_uuid" => $uuid_base . "transferee-" . Utils::randomString(8));
            $uuid_2 = $channels->gatewayOriginate($b_device_id, $transferee, $options);
            $c_channel = $channels->waitForInbound($c_device_name);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $c_channel);

            $c_channel->answer();
            $b_channel_2 = $channels->waitForOriginate($uuid_2);
            $this->assertInstanceOf('\\MakeBusy\\FreeSWITCH\\Channels\\Channel', $b_channel_2);
            $event = $b_channel_2->waitAnswer();

            $this->ensureTwoWayAudio($b_channel_2, $c_channel);

            $to_tag = $event->getHeader('variable_sip_to_tag');
            $from_tag = $event->getHeader('variable_sip_from_tag');
            $sip_uri = urldecode($event->getHeader('variable_sip_req_uri'));
            $call_uuid = $event->getHeader('variable_call_uuid');

            $refer_to =     '<sip:' . $sip_uri
                     . '?Replaces=' . $call_uuid
                   . '%3Bto-tag%3D' . $to_tag
                 . '%3Bfrom-tag%3D' . $from_tag
                 . '>';

            $b_channel_1->setVariables('sip_h_refer-to', $refer_to);
            $b_channel_1->setVariables('sip_h_referred-by', $referred_by);
            $b_channel_1->deflect($refer_to);
            $b_channel_1->waitDestroy();

            $this->ensureTwoWayAudio($a_channel, $c_channel);
            $this->hangupBridged($a_channel, $c_channel);
        }
    }

}