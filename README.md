# RedLog
RedLod allows you to track time on Redmine via command line.

## Installation
The following instructions assume you use a \*nix terminal.

Download the package or clone it, then run 

    composer install

After composer has finished downloading all the dependencies, give execution permissions to `redlog` file in root directory:

    chmod +x redlog

If you prefer, create a symlink to the `redlog` script in a shared path, for example

    sudo ln -s /path/to/redlog_project/redlog /usr/bin/redlog    

A script that runs after `composer install` should already create an `.env` file.
If it doesn't work, manually copy the `.env.example` file into `.env`.

Then, customize `.env` and `config.yml` files according to your Redmine settings.

**Note:** customize aliases of activities in `config.yml` according to your 
preferences and be sure the ativity names are the same of your Redmine's.

## Usage
From root directory, run the following command to track `2.5` hours for a development activity (d) on task `1234` done on 2018-03-14

    redlog log 2018-03-14 2.5 1234 d "Fixed nasty bug"

Alternatively you can use time start-end format:

    redlog log 2018-03-14 0900-1130 1234 d "Fixed nasty bug"

If the activity is not allowed in the issue's project, a message like the 
following will be shown:

    Activity 'Event' (evt) not allowed in Project 'bitbull-internal' (id: 141)
    https://tracker.bitbull.it/projects/bitbull-internal/settings/activities
    The list of allowed activities is:
    des     Design / UX
    dev     Development
    setcon  Setup / configuration
    suptra  Support / training
    a       Analysis
    pm      Project management
    sale    Sales/Pre-sales

Unfortunately the APIs of the version of Redmine I'm using doesn't provide the 
activities allowed in a project.

Thus, configure a `REDMINE_SESSION` in `.env` (taken from the `_redmine_session` 
cookie from a browser in which you are authenticated), so that redlog
gets that list via HTTP call.

## Contributing
Contributions are very welcome; refer to the [CONTRIBUTING.md](CONTRIBUTING.md) file for further details.

## Changelog
All notable changes to this project are documented in the [CHANGELOG](CHANGELOG.md) file.

## License
This software is licensed under the **Open Software License version 3.0**.
Refer to [LICENSE.txt](LICENSE.txt) file for further details.