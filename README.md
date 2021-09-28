# Hyperf Tracer

Drop-in replacement of `hyperf/tracer` suited for PicPay's microservices needs.

## Getting started

### Installation

1. First override `hyperf/tracer` repository
    ```shell
    composer config repositories.tracer vcs https://github.com/PicPay/hyperf-tracer
    ```
   
2. Then the usual Composer installation
    ```shell
    composer require hyperf/tracer
    ```

3. And Hyperf's vendor publishing
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
