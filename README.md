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

**Note:** customize letters of activities according to your preferences and bind them to your Reddmine's ids.

## Usage
From root directory, run the following command to track `2.5` hours for a development activity (d) on task `1234` done on 2018-03-14

    redlog log 2018-03-14 2.5 1234 d "Fixed nasty bug"

Alternatively you can use time start-end format:

    redlog log 2018-03-14 0900-1130 1234 d "Fixed nasty bug"

If the activity is not allowed in the project the given issue belongs to,
an error message like the following will be shown:

    Activity 'a' not allowed in Project 'team-2' (id: 325)

Unfortunately the APIs of the version of Redmine I'm using doesn't provide the 
activities allowed in a project.

If you configure a `REDMINE_SESSION` in `.env` (taken from the 
`_redmine_session` cookie from a browser in which you are authenticated), redlog
tries to get that list via HTTP call. 

If this doesn't work, it falls back to a list of allowed activities for given 
project configured in the `config.yml` file.     

## Contributing
Contributions are very welcome; refer to the [CONTRIBUTING.md](CONTRIBUTING.md) file for further details.

## Changelog
All notable changes to this project are documented in the [CHANGELOG](CHANGELOG.md) file.

## Licence
This software is licensed under the **Open Software License version 3.0**.
Refer to [LICENSE.txt](LICENSE.txt) file for further details.