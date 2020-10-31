APNs Notifier
=================

Provides APNs integration for Symfony Notifier.

DSN examples
-----------

Send notifications over the production APNs server:

```
// .env file
APNS_DSN=apns://default
```

or the development APNs server:

```
// .env file
APNS_DSN=apns://development
```

Simple config to token based authentication:

```
// .env file
APNS_DSN=apns://default?key_id=KEYID12345&team_id=TEAMID1234&private_key=path/to/private/key.p8&passphrase=abc
```

or certificate based authentcation:

```
// .env file
APNS_DSN=apns://default?cert=path/to/cert/file.crt&phasspase=abc
```

- authentication=dsn (default): In this case you configure the authenticate over the DSN string.
- authentication=provider: In this case you configure the authentication with.


Resources
---------

  * [Contributing](https://symfony.com/doc/current/contributing/index.html)
  * [Report issues](https://github.com/symfony/symfony/issues) and
    [send Pull Requests](https://github.com/symfony/symfony/pulls)
    in the [main Symfony repository](https://github.com/symfony/symfony)
