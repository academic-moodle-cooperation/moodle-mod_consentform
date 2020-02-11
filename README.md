Consentform Module
================

This file is part of the mod_consentform plugin for Moodle - <http://moodle.org/>

*Author:*   Thomas Niedermaier

*Copyright:* 2020 [Medizinische Universität Wien](https://meduniwien.ac.at)

*License:*   [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)


Description
-----------

The Consentform module allows trainers to hide/reveal course elements in dependance of a consentform.


Example
-------

A trainer wants to hide some elements of a moodle course as long as the course participants have not agreed with a
consentform. First you insert an instance of this activity module to your course and you insert the text, which
the participants have to agree/disagree with. Then you click at this consentform instance in course overview and
see a list of all visible moodle elements of this course. You can select here all the modules which
shall be hidden as long as the participant has not agreed to the consentform. If - on the other hand - a course 
participant clicks on this module in course overview, he/she will see the consentform text and a button
"agree" or "disagree". He/she can now agree or disagree to this text by pressing the appropriate button.
If he/she agrees, the hidden elements of the course will be revealed.

Requirements
------------

The plugin is available for Moodle 3.7+. This version is for Moodle 3.7.


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

* allow instance setting "disagreements possible" (checkbox)

Documentation
-------------

You can find a cheat sheet for the plugin on the [AMC
website](https://www.academic-moodle-cooperation.org/en/module/consentform/) and a video tutorial in
german only in the [AMC YouTube Channel](https://www.youtube.com/c/AMCAcademicMoodleCooperation).


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
