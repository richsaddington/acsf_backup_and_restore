# About/What does it do?

A simple script that does the following:
- Initiate an ACSF backup request with callback
- Check backup has completed
- Check for available backups
- Initate a restore using most recent backup with callback
- Check restore has completed

# Install/setup

Install dependencies with composer
composer init; composer require guzzlehttp/guzzle

1. Populate the $config arrays
- callback_url should be a publically accessible endpoint - use https://webhook.site/ for testing

2. Set the ACSF site ID

# Usage

Execute with ./acsf_backup_and_restore.php
 
# Support/Help

This script is designed to provide an example of the ACSF API calls and should be used to inform an API implementation.
