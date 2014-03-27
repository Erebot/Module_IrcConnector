<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

abstract class  TestURIFactory
implements      \Erebot\URIInterface
{
    public function getHost($raw = FALSE)
    {
        return '0.0.0.0';
    }

    public static function fromAbsPath($abspath, $strict = TRUE)
    {
    }
}

class   IrcConnectorTest
extends Erebot_Testenv_Module_TestCase
{
    protected function _setConnectionExpectations()
    {
        parent::_setConnectionExpectations();
    }

    public function _getMock()
    {
        $event = $this->getMock(
            '\\Erebot\\Interfaces\\Event\\Logon',
            array(), array(), '', FALSE, FALSE
        );

        $event
            ->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->_connection));
        return $event;
    }

    public function setUp()
    {
        class_exists('\\Erebot\\Module\\callable');
        $this->_module = new \Erebot\Module\IrcConnector(NULL);
        parent::setUp();

        $this->_serverConfig
            ->expects($this->any())
            ->method('getConnectionURI')
            ->will($this->returnValue(array('ircs://0.0.0.0/')));

        $uriMock = $this->getMockForAbstractClass(
            'TestURIFactory',
            array(),
            '',
            FALSE,
            FALSE
        );
        $this->_module->setURIFactory(get_class($uriMock));
        $this->_module->reloadModule($this->_connection, 0);
    }

    public function tearDown()
    {
        unset($this->_module);
        parent::tearDown();
    }

    public function testPasswordlessRegistration()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->onConsecutiveCalls(
                '',         // no password
                'Erebot',   // nickname
                'identity', // identity
                'hostname', // hostname
                'realname'  // realname
            ));

        $this->_module->handleLogon($this->_eventHandler, $this->_getMock());
        $this->assertEquals(2, count($this->_outputBuffer));
        $this->assertEquals(
            'NICK Erebot',
            $this->_outputBuffer[0]
        );
        $this->assertEquals(
            'USER identity hostname 0.0.0.0 :realname',
            $this->_outputBuffer[1]
        );
    }

    public function testPasswordedRegistration()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->onConsecutiveCalls(
                'password', // password
                'Erebot',   // nickname
                'identity', // identity
                'hostname', // hostname
                'realname'  // realname
            ));

        $this->_module->handleLogon($this->_eventHandler, $this->_getMock());
        $this->assertEquals(3, count($this->_outputBuffer));
        $this->assertEquals(
            'PASS password',
            $this->_outputBuffer[0]
        );
        $this->assertEquals(
            'NICK Erebot',
            $this->_outputBuffer[1]
        );
        $this->assertEquals(
            'USER identity hostname 0.0.0.0 :realname',
            $this->_outputBuffer[2]
        );
    }
}

