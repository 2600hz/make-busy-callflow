<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class RealmChangeRegistrationTest extends DeviceTestCase {

    public function testMain() {
        self::assertTrue( $a_device->getGateway()->register() );
        self::assertTrue( $a_device->getGateway()->unregister() );

        self::$test_account->setAccountRealm('blah.com');

        $this->assertFalse( $a_device->getGateway()->register() );

        $a_device->getGateway()->setParam('realm', 'blah.com');
        $a_device->getGateway()->setParam('from-domain', 'blah.com');

        // it will not work: gateways parameters are loaded from kazoo by gateway.php
        Profiles::syncGateways();

        $this->assertTrue( $a_device->getGateway()->register() );

        $test_account->setAccountRealm(self::$realm);
        $a_device->getGateway()->setParam('realm', self::$realm);
        $a_device->getGateway()->setParam('from-domain', self::$realm);

        Profiles::syncGateways();
    }

}