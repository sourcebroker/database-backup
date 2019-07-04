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
  
* Databases whitelist / blacklists.  
  
* Prepared to support different storages (so far only "local" storage is supported).

* On demand mode - execute only if needed.

Installation
------------

.. code-block:: bash

  composer require sourcebroker/database-backup

Usage
-----

Sample configuration creation command:

.. code-block:: bash

  php bin/backup db:default-configuration

Description:

.. code-block:: bash

  --dry-run  
    Perform action without saving any data. This option is for testing purpose.
  
Backup database command:

.. code-block:: bash

  php bin/backup db:dump [--dry-run] [--] <yaml config>

Description:
  
.. code-block:: bash

  --dry-run  
    Perform action without saving any data. This option is for testing purpose.  
  
  < yaml config >  
    Configuration file containing backup tasks.
  
Simplest usage example:

.. code-block:: bash

  php bin/backup db:dump config.yaml

Simplest config.yaml. The config below will do backup at 1 am with 2 day rotation.  

.. code-block:: yaml

  configs:  
    dayilyAt1am:  
      cron:  
        howMany: 2  
        pattern: "0 1 * * *"
  
You can add more configs into one file. The config below will do backup at 1am with 7 days rotation  
and at every 15 min of hour with rotation last 5 hours.  

.. code-block:: yaml

  configs:  
    dayily:  
      cron:  
        howMany: 7  
        pattern: "0 1 * * *"  
    hourly:  
      cron:  
        howMany: 5  
        pattern: "15 * * * *"


Configuration
-------------
  
More information about configuration below.
Sample configuration for Magento and TYPO3 available in ./sample directory.


Default configuration (built-in)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
  
.. code-block:: yaml

  defaults:  
    tmpDir: ".tmp"              # temporary files directory  
    flagDir: ".flag"            # flag files directory  
    defaultsFile: "~/.my.cnf"   # path to file with authentication data  
  
    binaryDbCommand: ""         # mysql binary path (replaced with `which mysql` if empty)  
    binaryDbExport: ""          # mysqldump binary path (replaced with `which mysqldump` if empty)  
    binaryPacker: ""            # zip binary path (replaced with `which zip` if empty)
  
    databaseAccess:             # database access branch  
      type: "default"             # authentication type (described below)  
      path: ""                    # path to file with db authentication data  
      data:                       # authentication data (direct)  
        user: ""                    # username  
        password: ""                # password  
        port: ""                    # database port  
        host: ""                    # database port  
  
    storage:                    # storage description branch  
      local:                      # local storage  
        path: ".dump"               # path to local directory where backuper will store packs  
  
    application:                # application autodetection branch  
      typo3:                      # app name  
        tables:                     # tables description  
          detection:                  # detect application depending on existance of tables  
            - "tt_content"  
          whitelist:                  # include those tables in backup  
            - ".*"  
          blacklist:                  # exclude those tables from backup  
            - "cf_.*"  
          whitelistPresets: []        # not implemented yet  
          blacklistPresets: []        # not implemented yet  
  
      magento:  
        tables:  
          detection:  
            - "core_config_data"  
          whitelist:  
            - ".*"  
          blacklist:  
            - "/^cache.*$/"  
            - "/^log_.*$/"  
          whitelistPresets: [],  
          blacklistPresets: []  
  
    tables: {}                  # tables branch (check "tables configuration" section below)
  
    databases:                  # databases branch
      whitelist:                  # include those tables in backup  
        - ".*"  
      blacklist:                  # exclude those tables from backup  
        - "information_schema"  
      whitelistPresets: []        # not implemented yet  
      blacklistPresets: []        # not implemented yet  
      presets: []                 # not implemented yet
  
User configuration (yaml file)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
  
.. code-block:: yaml

  # Defaults branch  
  # Here you can specify values  
  defaults:  
    <any branch>                # any branch from default configuration may be overriden here  
  
  # Tasks configuration branch  
  # You can define multiple backup tasks. Each one may be executed on command run,  
  # depending on cron pattern and 'onDemand' flag  
  configs:  
  
    catalogOnDemand:            # task name  
      cron:                       # CRON description  
        howMany: 10                 # how many backups should be stored  
        pattern: "* * * * *"        # CRON time pattern  
        onDemand: true              # set if this mode should be executed only on demand (optional)  
  
      <any branch>              # any branch from defaults may be overriden here

Database authentication
^^^^^^^^^^^^^^^^^^^^^^^
  
There are 4 available modes of authentication:  
  
1. `'default'` - using configuration file (MySQL configuration format) given as `'path'` on databaseAccess level (default  ~/.my.cnf)  
2. `'env'` - reads authentication data from environment (.env file may be used)
3. `'php'` - .. from PHP file  
4. `'xml'` - .. from XML file  

Tables configuration
^^^^^^^^^^^^^^^^^^^^

.. code-block:: yaml

  tables:
    _default_:                  # default tables configuration (for all databases)
      whitelist:                  # include those tables
        - ".*"                      # regular expression
      blacklist:                  # exclude those tables
        - "cache_.*"

    <database name>:            # database level branch (override _default_ configuration)
      whitelist:                  # include those tables (from given database)
        - "important_.*"
      blacklist:                  # exclude those tables (from given database)
        - "cache_.*"
        - "log_.*"

If task has `onDemand` mode set it will be executed only conditionally.  
That task will be executed only if flag file is created in flag directory (defined in `'flagDir'`)  
File name is task name in lowercase - so for task `catalogOnDemand` it will be `catalogondemand`.

On demand mode
^^^^^^^^^^^^^^

If task has `onDemand` mode set it will be executed only conditionally.
That task will be executed only if CRON time pattern is fulfilled and if flag file is created in flag directory (defined in `'flagDir'`)
File name is task name in lowercase - so for task `catalogOnDemand` it will be `catalogondemand`.

Usage cases:
Backup orders tables immediately after new order is placed.
Modify your application in a way that it will create flag file in defined directory.
