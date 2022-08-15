# php-reverse-proxy
> ### forward happily


A reverse proxy written in pure PHP with the help of the Bramus PHP router.

This can work perfectly as a nginx alternative in case you're working on a shared hosting service or facing any difficulties in configuring nginx

## Features
- According to https://github.com/bramus/router#features, the currently supported HTTP request methods are `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `PATCH` and `HEAD`
- Easily configurable with multiple options
- Simply written, with easily understandable code
- Forwards requests as they are
- The capability of forwarding the real client IP to the destination server

## Installation
- Install the required dependencies using
```sh
composer install
```
- Configure it to your liking by editing the `config.php` file
- Make sure the `.htaccess` is in place.