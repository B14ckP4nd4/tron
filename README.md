###Tron API Wrapper
> **Important Note** This one has a little privates and I have to encrypt them.

####Regiments
- PHP ^7.2
- Laravel ^6

####installation

**use Composer**
```
composer require blackpanda/tron
```

 **edit config/app.php**
```
        /*
         * Package Service Providers...
         */
          blackpanda\tron\TronServiceProvider::class,
```
**run publish command**

```
php artisan vendor:publish --provider=blackpanda\tron\TronServiceProvider --force
```

**and run migrations**


#### how to Use?

> sorry, I'll write a complete documentation
