<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class DeviceTest extends CallflowTestCase {

    public function testMain() {
        $gateways = $this->getGateways("auth");
        $register_device_id = self::$register_device->getId();
        $this->assertTrue($gateways->findByName($register_device_id)->register());
    }

}