SPiD SDK for PHP [![Build Status](https://travis-ci.org/schibsted/sdk-php.svg?branch=master)](https://travis-ci.org/schibsted/sdk-php)
================

For information and the development guides see our [techdocs](http://techdocs.spid.no/).

## The environment

This environment uses 1 container.


### Requirements

- [Docker](https://docs.docker.com/engine/installation/)


#### Mac & Windows

If you are using Mac OS X or Windows as your host OS, It is recommended using [Docker Machine](https://docs.docker.com/machine/)
as a proxy VM to run Docker.

To run Docker Machine you need to follow the following steps:

1. Run `docker-machine create --driver virtualbox sdk-php` command (to create and start a VirtualBox VM with Docker), where `sdk-php` is the name of your choice.
or
Run `docker-machine start sdk-php` command.
2. Run `docker-machine env sdk-php` command.
3. Run `eval "$(docker-machine env sdk-php)"` command.

#### Start the environment

In the project root directory run the following commands:

```
docker build -f Dockerfile . -t sdk-dev
```

```
docker run -v $(pwd):/var/www/html -p 8080:80 -h sdk.dev -d --name sdk-dev sdk-dev
```

This command will build `php` Docker image and run its container.

To install backend dependencies:

```
docker exec sdk-dev php composer.phar install
```



#### Update your hosts

##### Mac OS X

1. Check Docker Machine IP address: `docker-machine ip sdk-php`.

2. Assuming its 192.168.99.100, add the following line to your `/etc/hosts` file:
    ```
    192.168.99.100 sdk.dev
    ```

##### Linux

TBA

##### Windows

1. Check Docker Machine IP address: `docker-machine ip sdk-php`.

2. Assuming its 192.168.99.100, add the following line to your `%SystemRoot%\System32\drivers\etc\hosts` file:
    ```
    192.168.99.100 sdk.dev
    ```

