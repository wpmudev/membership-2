# Developers #

This plugin uses **[grunt](http://gruntjs.com/getting-started)** and is tested/developed in **[Vagrant](https://bitbucket.org/incsub/vvv-incsub)**.
So when you join in it's good to use these also.

## Setup grunt first time ##

Simply use a terminal and change to the plugins/protected-content directory.
Use command `npm install` and grunt will download all dependencies.

Windows users: `npm` requries an admin terminal (or starting vagrant from an admin terminal)


## Using grunt ##

* `grunt` .. compile all CSS/JS files
* `grunt test` .. validate JS and run unit tests
* `grunt build` .. validate, unit test, compile CSS/JS, create .zip archive
* `grunt watch` .. watch CSS/JS source files and validate/compile them on changes

**Scenarios**

* During development: Have `grunt watch` running
* Before commiting to bitbucket: Run `grunt test` and `grunt` if you made lots of changes
* Release plugin: Use `grunt build` to get the zip archive to upload on WPMU DEV website


## Some rules ##

Only edit `.scss` files, never `.css`! The .css files are compiled by grunt

Only edit `.js` files inside `js/src/` directory! Files inside `js/` are compiled by grunt. Files inside `js/vendor/` are third party and should be always official versions witout manual changes.