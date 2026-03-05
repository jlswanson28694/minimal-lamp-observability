# Minimal LAMP Observability Stack

![grafana-screenshot](https://github.com/user-attachments/assets/1145a62f-fd8b-4e27-8761-e9be9782e0d9)

This project contains a minimal observability stack for LAMP servers I put together during my research on the subject. It acts as a convenient and secure way to observe a LAMP application using Prometheus and Grafana visualization.

In addition to the minimal observability stack, `docker-compose.demo.yml` defines a Docker Compose configuration to demo this project's features. More details on running this project as a demo [below](#project-demo).

## Observability Stack
### Prometheus
This project uses [Prometheus](https://prometheus.io/docs/introduction/overview/) to store time series data from various LAMP processes, as well as the host machine. Prometheus receives metrics from the following targets:
- [OpenTelemetry Collector](https://github.com/open-telemetry/opentelemetry-collector-contrib)
- [hipages/php-fpm_exporter](https://github.com/hipages/php-fpm_exporter)
- [prometheus/node_exporter](https://github.com/prometheus/node_exporter)
- [ncabatoff/process-exporter](https://github.com/ncabatoff/process-exporter)

Prometheus provides a web UI on port `9090` to query metrics directly.

### OpenTelemetry Collector
To reduce the complexity of the observability stack, OpenTelemetry Collector handles several responsibilities at once. It scrapes metrics from Apache, MySQL, and Redis using various receivers, and exports them to Prometheus. Documentation for all components used in this project can be found in the [OpenTelemetry Collector repository](https://github.com/open-telemetry/opentelemetry-collector-contrib).

### Grafana
A Grafana dashboard has also been provided as part of this project. The dashboard visualizes both an overview of server health and detailed metrics for process resource usage, active workers, and more. The Grafana UI is available on port `3000`.

## Getting Started
In order for this project to properly scrape metrics, some setup is required to expose metric endpoints and ensure the project is secure. 

If you only wish to demo the project, no setup is required; just skip to the demo instructions [below](#project-demo).

### Environment file
After cloning this repository, first create a `.env` file by copying `.env.example`. 
```
# Copy .env.example and set permissions so others can't view any sensitive credentials
cp .env.example .env
chmod 600 .env
```
The `.env.example` file contains the keys you will most likely need to set for your own system. I'll touch on the available environment variables in the following sections.

### Apache status page
In order for the OpenTelemetry Collector to scrape metrics from Apache, the [Apache server status page](https://httpd.apache.org/docs/2.4/mod/mod_status.html) must be available. By default, OpenTelemetry Collector will attempt to scrape metrics from `http://localhost:8080/server-status?auto`. You can customize the port using the `APACHE_STATUS_PORT` variable and URL using `APACHE_STATUS_URL`. Take a look at [demo/apache/vhosts/status.conf](./demo/apache/vhosts/status.conf) for an example of serving the status page. Note that the example vhost uses a separate access/error log, so scrapes don't affect metrics.

### Apache access log
The OpenTelemetry Collector tails the Apache access log to produce metrics on status codes and latency. The File Log receiver expects the Apache access logs to be in the following format, which includes the time taken to complete the request, `%D`:
```
"%h %l %u %t \"%r\" %>s %b %D \"%{Referer}i\" \"%{User-agent}i\""
```
Make sure to edit your Apache vhost file to use this format. Look at [demo/apache/vhosts/000-default.conf](./demo/apache/vhosts/000-default.conf) for an example.

If you want to use a different log format, you will need to adjust the OpenTelemetry Collector File Log receiver configuration in [config/otelcol.yml](./config/otelcol.yml). Keep in mind that `%D` is required for the latency histogram metric to work.

By default, `/var/log/apache2/access.log` is mounted into the container. To tail a different access log, provide the path to the `APACHE_ACCESS_LOG` environment variable.

To give the OpenTelemetry Collector permission to read the access log, the container is given the `adm` group (GID 4 by default). If `adm` has a different GID on your system, you can adjust the value using the `ADM_GID` environment variable.

#### Latency histogram resolution
The latencies recorded in the Apache access log are aggregated by OpenTelemetry Collector into an exponential histogram. The resolution of this histogram is determined by the maximum number of buckets, which can be adjusted using the `HISTOGRAM_MAX_BUCKETS` environment variable (default of `320`).

### PHP-FPM status page
The php-fpm_exporter scrapes metrics from the PHP-FPM status page. Make sure your PHP-FPM pool config has `pm.status_path` set. By default, the container will attempt to scrape from `/status`. If you are serving the status page under a different URL, you can set a custom value with the `PHP_FPM_STATUS_ENDPOINT` environment variable.

The container's default behavior also expects `pm.status_listen` to be set to `/run/php/php-fpm-status.sock`. If you wish to use a different socket address, you can use the `PHP_FPM_STATUS_SOCKET` environment variable.

To give the container permission to access the Unix domain socket to scrape the status page, it must be running as the `www-data` user; UID 33 by default. If `www-data` has a different UID on your machine, you can set a custom value for the `WWW_DATA_USER` environment variable.

### MySQL
MySQL is expected to be on port `3306` by default. To adjust this value, set the `MYSQL_PORT` environment variable. Also ensure that MySQL is listening on all interfaces so it will accept requests from the OpenTelemetry Collector container. If you don't want port `3306` exposed to the internet, use a firewall to protect the port.

For the OpenTelemetry Collector MySQL receiver to scrape metrics, you must provide credentials for a MySQL user with the `MYSQL_USER` and `MYSQL_PASSWORD` environment variables. It's recommended to define a MySQL user specifically for scraping metrics. Grant the necessary privileges to this user with the following queries:
```
GRANT PROCESS, SLAVE MONITOR ON *.* TO '<your-user>'@'%';
GRANT SELECT ON performance_schema.* TO '<your-user>'@'%';
FLUSH PRIVILEGES;
```

### Redis
Redis is expected to be on port `6379` by default. To adjust this value, set the `REDIS_PORT` environment variable. Also ensure that Redis is listening on all interfaces so it will accept requests from the OpenTelemetry Collector container. If you don't want port `6379` exposed to the internet, use a firewall to protect the port.

The `REDIS_USER` and `REDIS_PASSWORD` environment variables can be used to authenticate the OpenTelemetry Collector Redis receiver with your Redis server. These variables aren't required to be set if you don't use authentication for Redis on your machine. If you forgo authentication, make sure `protected-mode` is set to `no` in the Redis configuration.

### Prometheus and Grafana credentials
To protect the Prometheus and Grafana UI, set the following environment variables:
- `PROMETHEUS_USERNAME` and `PROMETHEUS_PASSWORD` (required)
- `GRAFANA_ADMIN_USERNAME` and `GRAFANA_ADMIN_PASSWORD` (default `admin` for both, you will be prompted to change this password on initial login)

### Config file permissions
Several containers in this project bind mount various configuration files that are kept in the `config` directory. These files should be kept private from non-privileged users, but also must be accessible to the containers. The recommended approach is to create a new group on your machine and give the containers membership, then set all config files to be accessible by this group. Set file permissions on the `config` folder with the following command:
```
chmod -R u=rwX,g=rX,o= ./config
```

Once you create a new group, give it group ownership of the contents of the `config` directory. Then, provide the GID to the `DOCKER_CONFIG_GID` environment variable to grant the containers membership to the group.

### Starting the containers
Now that setup is complete, you can launch the containers with `docker compose up -d`. After the containers are up and running, you can access the Prometheus UI on port `9090`, and the Grafana UI on port `3000`.

## Security
The containers in this project run on an internal Docker network named `observability`. This decreases the overall attack surface, as none of the ports used by the containers to communicate with each other are made available to the host machine's network. Only two ports are exposed to the host: the Prometheus UI, which is guarded by an Nginx reverse proxy that enforces basic auth, and the Grafana UI, which also requires credentials to access. Grafana metrics are disabled in this project, as requests to the metrics endpoint require no authentication by default, and I didn't deem the metrics as being useful.

### Apache server status
Metrics shared within the internal network are hidden to the host, and MySQL, Redis, and PHP-FPM metrics can be guarded with authentication or socket file permissions, but this project unfortunately requires the Apache server status page to be exposed on the host network. Implementing authentication for this endpoint does not appear to be supported by the OpenTelemetry Collector Apache Web Server receiver. While internal requests to the Apache server status page are not blocked, I recommend using your system's firewall to block external traffic to the server status port to prevent exposing metrics to the internet.

## Project Demo
This project includes a Docker Compose configuration for demoing the available features using a containerized LAMP stack; no setup is required. To start the demo, run `docker compose -f docker-compose.demo.yml up -d`. Several demo containers will need to build on first startup, which may take several minutes. After starting the containers, the Prometheus UI will be available on port `9090`, and Grafana will be available on port `3000`. When prompted for credentials, just use a username and password of "admin". The demo doesn't produce any traffic data on its own, so load up the demo site on port `80` and make some requests. You should see the requests showing up in the Grafana dashboard shortly after.

Stop the demo by running `docker compose -f docker-compose.demo.yml down`, optionally with the `-v` flag to remove the containers and their volumes.

### A note on node_exporter
The Prometheus node_exporter has some limitations if running the demo on WSL. I had to disable the filesystem collector to prevent it from crashing, which will prevent the "Disk Space Used" Grafana panel from functioning. For Linux users, you can uncomment the filesystem collector command argument in `docker-compose.demo.yml` to enable it in the demo.
