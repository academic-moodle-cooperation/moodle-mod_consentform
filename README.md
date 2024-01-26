Consentform 
================

This file is part of the mod_consentform plugin for Moodle - <http://moodle.org/>

*Author:*   Thomas Niedermaier

*Copyright:* [Academic Moodle Cooperation](http://www.academic-moodle-cooperation.org)

*License:*   [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)


Description
-----------

The activity Consentform enables teachers to display or hide course content for participants depending on their consent/refusal.


Usage
-------

Students should take part in an online exam in Moodle. Before doing so, however, they must read the information on study law, otherwise they will not be allowed to start the quiz. To obtain consent, the teacher will create a consent form activity with a corresponding text. The quiz is initially hidden from students in the course. Only after a student has called up the consent form and actively agreed to it the quiz will become accessible to them.


Requirements
------------

The admin config setting *enablecompletion* as well as the course setting *enablecompletion* must be activated,
otherwise consentform will not work!


Installation
------------

* Copy the code directly to the mod/consentform directory.

* Log into Moodle as administrator.

* Open the administration area (http://your-moodle-site/admin) to start the installation
  automatically.


Privacy API
--------------

The plugin fully implements the Moodle Privacy API.


Documentation
-------------

You can find a documentation for the plugin on the [AMC website](https://academic-moodle-cooperation.org/mod_consentform/).


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
