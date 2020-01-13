# Environment Variables

Appwrite environment variables allow you to edit your server setup configuration and customize it. You can easily change the environment variables by changing them when running Appwrite using Docker CLI or Docker-Compose.

## General Options

### _APP_ENV

Set your server running environment. By default, the var is set to 'development'. When deploying to production, change it to: 'production'.

### _APP_OPTIONS_ABUSE

Allows you to disable abuse checks and API rate limiting. By default, set to 'enabled'. To cancel the abuse checking, set to 'disabled'. It is not recommended to disable this check-in a production environment.

### _APP_OPENSSL_KEY_V1

This is your server private secret key that is used to encrypt all sensitive data on your server. Appwrite server encrypts all secret data on your server like webhooks, HTTP passwords, user sessions, and storage files. The var is not set by default, if you wish to take advantage of Appwrite encryption capabilities you should change it and make sure to keep it a secret.

### _APP_CONSOLE_WHITELIST_EMAILS

This option is very useful for small teams or sole developers. To enable it, pass a list of allowed email addresses separated by a comma.

### _APP_CONSOLE_WHITELIST_DOMAINS

This option allows you to restrict access to Appwrite console for users sharing the same email domains. This option is very useful for team working with company emails domain.

To enable this option, pass a list of allowed email domains separated by a comma.

### _APP_CONSOLE_WHITELIST_IPS

This last option allows you to restrict access to Appwrite console for users sharing the same set of IP addresses. This option is very useful for team working with a VPN service or a company IP.

To enable/activate this option, pass a list of allowed IP addresses separated by a comma.

## Redis Server

Appwrite uses a Redis server for managing cache, queues and scheduled tasks. The Redis env vars are used to allow Appwrite server to connect to the Redis container.

### _APP_REDIS_HOST

Redis server hostname address. Default value is: 'redis'

### _APP_REDIS_PORT

Redis server TCP port. Default value is: '6379'

## MariaDB Server

Appwrite is using a MariaDB server for managing persistent database data. The MariaDB env vars are used to allow Appwrite server to connect to the MariaDB container.

### _APP_DB_HOST

MariaDB server host name address. Default value is: 'mariadb'

### _APP_DB_PORT

MariaDB server TCP port. Default value is: '3306'

### _APP_DB_USER

MariaDB server user name. Default value is: 'root'

### _APP_DB_PASS

MariaDB server user password. Default value is: 'password'

### _APP_DB_SCHEMA

MariaDB server database schema. Default value is: 'appwrite'

## InfluxDB

Appwrite uses an InfluxDB server for managing time-series data and server stats. The InfluxDB env vars are used to allow Appwrite server to connect to the InfluxDB container.

### _APP_INFLUXDB_HOST

InfluxDB server host name address. Default value is: 'influxdb'

### _APP_INFLUXDB_PORT

InfluxDB server TCP port. Default value is: '8086'

## StatsD

Appwrite uses a StatsD server for aggregating and sending stats data over a fast UDP connection. The StatsD env vars are used to allow Appwrite server to connect to the StatsD container.

### _APP_INFLUXDB_HOST

StatsD server host name address. Default value is: 'telegraf'

### _APP_INFLUXDB_PORT

StatsD server TCP port. Default value is: '8125'

## SMTP

Appwrite is using an SMTP server for emailing your projects users and server admins. The SMTP env vars are used to allow Appwrite server to connect to the SMTP container.

If running in production, it might be easier to use a 3rd party SMTP server as it might be a little more difficult to set up a production SMTP server that will not send all your emails into your user's SPAM folder.

### _APP_SMTP_HOST

SMTP server host name address. Default value is: 'smtp'

### _APP_SMTP_PORT

SMTP server TCP port. Default value is: '25'

### _APP_SMTP_SECURE

SMTP secure connection protocol. Empty by default, change to 'tls' if running on a secure connection.

### _APP_SMTP_USERNAME

SMTP server user name. Empty by default.

### _APP_SMTP_PASSWORD

SMTP server user password. Empty by default.
