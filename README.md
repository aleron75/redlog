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

Then, customize `.env` file according to your Redmine settings.

**Note:** customize letters of activities according to your preferences and bind them to your Reddmine's ids.

## Usage
From root directory, run the following command to track `2.5` hours for a development activity (d) on task `1234` done on 2018-03-14

    redlog log 2018-03-14 2.5 1234 d "Fixed nasty bug"

## Contributing
Contributions are very welcome; refer to the [CONTRIBUTING.md](CONTRIBUTING.md) file for further details.

## Licence
This softwre is licensed under the **Open Software License version 3.0**.
Refer to [LICENSE.txt](LICENSE.txt) file for further details.