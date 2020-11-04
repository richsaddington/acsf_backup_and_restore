# About/What does it do?

A simple script that does the following:
- Initiate an ACSF backup request with callback
- Check backup has completed
- Check for available backups
- Initate a restore using most recent backup with callback
- Check restore has completed

# Install/setup

1. Clone the repo locally

```git clone git@github.com:richsaddington/acsf_backup_and_restore.git```

2. Install dependencies with composer

```cd acsf_backup_and_restore```

```composer init; composer require guzzlehttp/guzzle```

3. Populate the $config arrays
- callback_url should be a publically accessible endpoint - use https://webhook.site/ for testing

4. Set the ACSF site ID

5. Ensure you have permission to execute acsf_backup_and_restore.php

```chmod 755 acsf_backup_and_restore.php```

# Usage

Execute with ./acsf_backup_and_restore.php
 
# Support/Help

This script is designed to provide an example of the ACSF API calls and should be used to inform an API implementation.
