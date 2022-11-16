Consentform Module
================

This file is part of the mod_consentform plugin for Moodle - <http://moodle.org/>

*Author:*   Thomas Niedermaier

*Copyright:* 2022 [Academic Moodle Cooperation](http://www.academic-moodle-cooperation.org)

*License:*   [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)


Description
-----------

The Consentform module allows trainers to hide/reveal course elements in dependence of a consentform agreement/refusal.


Example
-------

With consentform a trainer is able to hide selected elements of the course for participants as long as they have not
agreed to a declaration of consent.
To achieve this a trainer adds one or more consent form instances to a course, providing a declaration of agreement for
each instance.
Now, if trainers click at such an instance a list of all the modules of the course are presented to them and they can
set the availability of each one of them as dependent on the consentform.
Later on, if participants click at this consent form instance the declaration of consent is shown to them and they are asked to
confirm or (optionally) to refuse, or revoke their confirmation. If the participant agrees, the elements of the course dependent on this
consent form module will be accessible.


Requirements
------------

The plugin is available for Moodle 3.11+.

The admin config setting "enablecompletion" as well as the course setting "enablecompletion" must be set to 1.
Otherwise consentform will not work!


Installation
------------

* Copy the module code directly to the mod/consentform directory.

* Log into Moodle as administrator.

* Open the administration area (http://your-moodle-site/admin) to start the installation
  automatically.


Admin Settings
--------------

As an administrator you can set the default values instance-wide on the settings page for
administrators in the consentform module:

* default instance setting "Allow refusals" (checkbox)
* default instance setting "Allow revocations" (checkbox)
* default instance setting title agreement button (textfield)
* default instance setting title refusal button (textfield)
* default instance setting title revocation button (textfield)
* default instance setting "Agreement in course overview" (checkbox)
* default instance setting "No course module list" (checkbox)

Documentation
-------------

You can find a cheat sheet for the plugin here: 
English - https://m3e.meduniwien.ac.at/m3e/Consentform_311-EN.pdf
German - https://www.academic-moodle-cooperation.org/fileadmin/user_upload/p_aec/Cheat_Sheets/Einverstaendniserklaerung_einholen-DE.pdf


Bug Reports / Support
---------------------

We try our best to deliver bug-free plugins, but we can not test the plugin for every platform,
database, PHP and Moodle version. If you find any bug please report it on
[GitHub](https://github.com/academic-moodle-cooperation/moodle-mod_consentform/issues). Please
provide a detailed bug description, including the plugin and Moodle version and, if applicable, a
screenshot.

You may also file a request for enhancement on GitHub. If we consider the request generally useful
and if it can be implemented with reasonable effort we might implement it in a future version.

You may also post general questions on the plugin on GitHub, but note that we do not have the
resources to provide detailed support.


License
-------

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU
General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

The plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License with Moodle. If not, see
<http://www.gnu.org/licenses/>.


Good luck and have fun!
