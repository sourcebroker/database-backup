Database backup
===============

.. contents:: :local:


What does it do?
----------------

This package allows to do database backup. Strong emphasis is put to sane defaults
but with ability to fine tune each possible settings.


Features
--------

* Support for multiple configurations sets.

* Cron like way to set when database backup program should run.

* Detects what application uses database and ignores tables which are not important (like
  caching tables). Whitelist / blacklists for detected application database.

* Detects what application uses database and ignores tables which are not important (like
  caching tables). Whitelist / blacklists for detected application database. Whitelist
  / blacklists presets for detected application database.

* Databases whitelist / blacklists.

* Prepared to support different storages (so far only "local" storage is supported).


Installation
------------

 ::

  composer require sourcebroker/database-backup


Usage
-----

Simplest usage:
 ::

  php bin/backup config.yaml

Simplest config.yaml. The config below will do backup at 1 am with 2 day rotation.

 ::

    configs:
      dayilyAt1am:
        cron:
          howMany: 2
          cron: 0 1 * * *



You can add more configs into one file. The config below will do backup at 1am with 7 days rotation
and at every 15 min of hour with rotation last 5 hours.

 ::

   configs:
     dayily:
       cron:
         howMany: 7
         cron: 0 1 * * *
     hourly:
       cron:
         howMany: 5
         cron: 15 * * * *

