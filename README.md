# Wikia Archeology Project

## Quick notetaking on installation process

> NOTE: Can use git diff against the orignial repo to see the changes I have made, though not all are needed.

### Notes and env

* I am using XAMPP 7.1.30-5

### Basic steps so far

* Set the proper variables for the dc/env and remove secrets.php from `LocalSettings.php`
* Edit variables file to use the simple load balancer
* Create the wikicities and other databases manually (in `/maintanance/wikia/sql`)
  * You need to insert data into some of them, see errors for details
* Create the wiki's database manually from `tables.sql`

## Technical 

When FANDOM needs to load a wiki, it takes the current subdomain and uses it to find the "city" (what indivdual wikis were originally called, since FANDOM/Wikia used to be called WikiCities). The city data is used to find the url, db and other information about this wiki, for example its founder and creation date.

The specific mechanism for this is WikiFactory: 
