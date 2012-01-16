Usage
=====

This module does not provide any command. Just add this module to your
`configuration`_ and you're done.

This module makes Erebot send credentials to IRC servers,
ie. the following sequence of commands:

    PASS password
    NICK nickname
    USER identity hostname server :Real name

..  note::
    The PASS command is only sent if a password was set in the configuration
    for that IRC server.

This module also supports forced "security upgrades" through the
`STARTTLS extension`_:
This feature can be enabled by adding an ``upgrade`` parameter in the
connection URL and setting it to a boolean truth value,
eg. ``irc://irc.example.com?upgrade=1``.

If you configure the bot to do a security upgrade, it will refuse to proceed
with the connection if the IRC server rejects the upgrade (to protect itself
against downgrade attacks).


..  _`configuration`:
    Configuration.html
..  _`STARTTLS extension`:
    http://wiki.inspircd.org/STARTTLS_Documentation.

.. vim: ts=4 et
