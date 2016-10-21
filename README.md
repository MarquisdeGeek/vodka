# vodka
A test and development platform for Tropo

## Background

As part of TADHack 2016, I built this to expedite work on Tropo. It may be useful to others.

## Usage

Ensure the db folder is given write access by the process that will use it. This is especially
important when you use it as a Tropo endpoint, since that will be via the user www-data,
apache, or similar.

## Example

If you type

```
php vphone.php
```

You can use the basic example present in the app/ folder. By default the user name is '123456'
and the password is '1234'. You can see this in db/users/123456

## Using with Tropo

Set-up an account at tropo.com, and point the endpoint to tropo_in.php. The rest is up to you!

