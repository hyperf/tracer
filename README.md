# Hyperf Tracer

## Getting started

### Installation

#### Override `hyperf/tracer` repository
```shell
composer config repositories.tracer vcs https://github.com/PicPay/hyperf-tracer
```

#### Usual Composer installation
```shell
composer require hyperf/tracer
```

#### Hyperf's vendor publishing
```shell
php bin/hyperf.php vendor:publish hyperf/tracer
```

## Contributing

### Development environment
```shell
docker-compose run --rm devenv
```

### Inside the container
````shell
composer install
````
