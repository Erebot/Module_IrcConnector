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

namespace Erebot\Module;

/**
 * A module which can send the proper sequence of commands
 * to an IRC server to proceed to the "registered" phase.
 *
 * This module also supports security upgrades (upgrading
 * from a plain-text connection to a TLS encrypted one)
 * using the STARTTLS extension.
 *
 * \see
 *      See http://wiki.inspircd.org/STARTTLS_Documentation
 *      for more information on the STARTTLS extension.
 */
class IrcConnector extends \Erebot\Module\Base implements \Erebot\Interfaces\HelpEnabled
{
    /// Password of the IRC server.
    protected $password;

    /// Nickname to use to connect to the IRC server.
    protected $nickname;

    /// Identity of the bot on IRC.
    protected $identity;

    /// Hostname the bot will pretend to be coming from (possibly a fake).
    protected $hostname;

    /// The bot's real name on IRC.
    protected $realname;

    /// Class used to parse Uniform Resource Identifiers.
    protected $uriFactory = '\\Erebot\\URI';

    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot::Module::Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     */
    public function reload($flags)
    {
        if ($flags & self::RELOAD_HANDLERS) {
            $handler = new \Erebot\EventHandler(
                new \Erebot\CallableWrapper(array($this, 'handleLogon')),
                new \Erebot\Event\Match\Type(
                    '\\Erebot\\Interfaces\\Event\\Logon'
                )
            );
            $this->connection->addEventHandler($handler);

            $handler = new \Erebot\EventHandler(
                new \Erebot\CallableWrapper(array($this, 'handleExit')),
                new \Erebot\Event\Match\Type(
                    '\\Erebot\\Interfaces\\Event\\ExitEvent'
                )
            );
            $this->connection->addEventHandler($handler);
        }
    }

    public function getHelp(
        \Erebot\Interfaces\Event\Base\TextMessage $event,
        \Erebot\Interfaces\TextWrapper $words
    ) {
        if ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage) {
            $target = $event->getSource();
            $chan   = null;
        } else {
            $target = $chan = $event->getChan();
        }

        $fmt        = $this->getFormatter($chan);
        $moduleName = strtolower(get_class());
        $nbArgs     = count($words);

        if ($nbArgs == 1 && $words[0] == $moduleName) {
            $msg = $fmt->_(
                "This module does not provide any command. It provides ".
                "the bot with the means to connect to IRC servers."
            );
            $this->sendMessage($target, $msg);
            return true;
        }
    }

    /**
     * Handles a connection to an IRC server.
     * This method is called during the logon phase,
     * right after a TCP connection has been established
     * but before the IRC server accepted us.
     *
     * \param string $cls
     *      Name of a class implementing Erebot::URIInterface
     *      to use to parse Uniform Resource Identifiers.
     */
    public function setURIFactory($cls)
    {
        $reflector = new \ReflectionClass($cls);
        if (!$reflector->isSubclassOf('\\Erebot\\URIInterface')) {
            throw new \Erebot\InvalidValueException(
                'A class that implements \\Erebot\\URIInterface was expected'
            );
        }
        $this->uriFactory = $cls;
    }

    /**
     * Handles a connection to an IRC server.
     * This method is called during the logon phase,
     * right after a TCP connection has been established
     * but before the IRC server accepted us.
     *
     * \retval string
     *      Name of the class used to parse
     *      Uniform Resource Identifiers.
     */
    public function getURIFactory()
    {
        return $this->uriFactory;
    }

    /**
     * This method sends IRC credentials over the connection.
     * It is called automatically by this module once the
     * connection has been established and the optional
     * security upgrade has been applied to it.
     *
     * \post
     *      This method sends the proper sequence of
     *      PASS (if a password has been configured),
     *      NICK and USER commands to the underlying
     *      connection.
     *      After that, the bot is marked as "registered"
     *      from a protocol-oriented point of view.
     *
     * \note
     *      Even though the underlying connection has been
     *      established when this method is called, you
     *      SHOULD NOT assume that the bot is connected until
     *      an Erebot::Event::Connect event is dispatched.
     */
    protected function sendCredentials()
    {
        $this->password = $this->parseString('password', '');
        $this->nickname = $this->parseString('nickname');
        $this->identity = $this->parseString('identity', 'Erebot');
        $this->hostname = $this->parseString('hostname', 'Erebot');
        $this->realname = $this->parseString('realname', 'Erebot');

        $config = $this->connection->getConfig(null);
        $uris   = $config->getConnectionURI();
        $uri    = new $this->uriFactory($uris[count($uris) - 1]);

        if ($this->password != '') {
            $this->sendCommand('PASS '.$this->password);
        }
        $this->sendCommand('NICK '.$this->nickname);
        $this->sendCommand(
            'USER '.$this->identity.' '.$this->hostname.' '.
            $uri->getHost().' :'.$this->realname
        );
    }

    /**
     * Handles a connection to an IRC server.
     * This method is called during the logon phase,
     * right after a TCP connection has been established
     * but before the IRC server accepted us.
     *
     * \param Erebot::Interfaces::EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot::Interfaces::Event::Logon $event
     *      Logon event.
     *
     * \return
     *      This method does not return anything.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handleLogon(
        \Erebot\Interfaces\EventHandler $handler,
        \Erebot\Interfaces\Event\Logon $event
    ) {
        $config = $this->connection->getConfig(null);
        $uris   = $config->getConnectionURI();
        $uri    = new $this->uriFactory($uris[count($uris) - 1]);

        // If no upgrade should be performed or
        // if the connection is already encrypted.
        if (!$this->parseBool('upgrade', false) || $uri->getScheme() == 'ircs') {
            $this->sendCredentials();
        } elseif (!in_array('sockets', get_loaded_extensions())) {
            // The socket extension is not enabled.
            // This should never happen actually as we are already connected!
            $this->connection->disconnect(null, true);
        } else {
            // Otherwise, start a TLS negociation.
            $handler = new \Erebot\NumericHandler(
                new \Erebot\CallableWrapper(array($this, 'handleSTARTTLSSuccess')),
                $this->getNumRef('RPL_STARTTLSOK')
            );
            $this->connection->addNumericHandler($handler);
            $handler = new \Erebot\NumericHandler(
                new \Erebot\CallableWrapper(array($this, 'handleSTARTTLSFailure')),
                $this->getNumRef('ERR_STARTTLSFAIL')
            );
            $this->connection->addNumericHandler($handler);
            $this->sendCommand('STARTTLS');
        }
    }

    /**
     * Handles an exit request (eg. when the bot receives
     * the SIGTERM signal).
     *
     * \param Erebot::Interfaces::EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot::Interfaces::Event::ExitEvent $event
     *      Exit event.
     *
     * \return
     *      This method does not return anything.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handleExit(
        \Erebot\Interfaces\EventHandler $handler,
        \Erebot\Interfaces\Event\ExitEvent $event
    ) {
        $this->connection->disconnect($this->parseString('quit_message', ''));
    }

    /**
     * Handles a successful TLS session.
     *
     * \param Erebot::Interfaces::EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot::Interfaces::Event::Numeric $numeric
     *      Numeric event indicating that the TLS session
     *      was successfully established.
     *
     * \return
     *      This method does not return anything.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function handleSTARTTLSSuccess(
        \Erebot\Interfaces\NumericHandler $handler,
        \Erebot\Interfaces\Event\Numeric $numeric
    ) {
        try {
            stream_socket_enable_crypto(
                $this->connection->getSocket(),
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );
        } catch (\Erebot\ErrorReportingException $e) {
            return $this->connection->disconnect(null, true);
        }

        $this->sendCredentials();
    }

    /**
     * Handles a failed TLS session.
     *
     * \param Erebot::Interfaces::EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot::Interfaces::Event::Numeric $numeric
     *      Numeric event indicating that the TLS session
     *      could not be established.
     *
     * \return
     *      This method does not return anything.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handleSTARTTLSFailure(
        \Erebot\Interfaces\NumericHandler $handler,
        \Erebot\Interfaces\Event\Numeric $numeric
    ) {
        $this->connection->disconnect(null, true);
    }

    /**
     * Returns the password used to connect to this IRC network.
     *
     * \retval string
     *      The password used to connect to this IRC network.
     *
     * \note
     *      This method will return an empty string if no password
     *      was used to contact this IRC network.
     */
    public function getNetPassword()
    {
        return $this->password;
    }

    /**
     * Returns the bot's nickname.
     *
     * \retval string
     *      The bot's nickname.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current nickname
     *      if it was changed at a later time.
     */
    public function getBotNickname()
    {
        return $this->nickname;
    }

    /**
     * Returns the bot's identity.
     *
     * \retval string
     *      The bot's ident.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current identity
     *      if it was changed at a later time.
     */
    public function getBotIdentity()
    {
        return $this->identity;
    }

    /**
     * Returns the bot's hostname.
     *
     * \retval string
     *      The bot's hostname.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current hostname
     *      if it was changed at a later time.
     *
     * \note
     *      Most IRC servers ignore the hostname announced
     *      by clients and do a reverse DNS query on their
     *      IP address instead (this is a security measure).
     *      The value returned by this method will generally
     *      be different from the value used by the server.
     */
    public function getBotHostname()
    {
        return $this->hostname;
    }

    /**
     * Returns the bot's real name (or GECOS information).
     *
     * \retval string
     *      The bot's real name.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current real name
     *      if it was changed at a later time.
     */
    public function getBotRealname()
    {
        return $this->realname;
    }
}
