# DemoUI

`src/DemoUI` is a reference/demo implementation of a platform integration built on top of this core
library. It exists so the core library's behavior (services, controllers, DTOs) can be exercised and
viewed in a browser without needing a real e-commerce platform (PrestaShop/WooCommerce/etc.) installed.
It is excluded from test coverage and is not shipped to platform modules.

- Entry points live in `src/DemoUI/src/Views/<BRAND>/index.php` (brands: `PRO`, `ACME`).
- `Packlink\DemoUI\Bootstrap` wires up the core library the same way a real platform module would.
- Selected brand is controlled by the `PL_PLATFORM` env var (`PRO` or `ACME`).

## Running it standalone (own machine)

```bash
cd src/DemoUI
sh ./run.sh          # defaults to PL_PLATFORM=PRO, opens http://localhost:7000/Views/PRO/index.php
sh ./run.sh ACME      # runs the ACME brand instead
```

This runs `composer install` then starts PHP's built-in server on `localhost:7000`, serving from
`src/DemoUI/src`. Ctrl+C to stop.

## How it's already running in this dev environment

In this workspace, a persistent dev environment is already up in a Docker container in WSL
(`logeecom_dev`, part of a compose stack under `/home/jovanbjegovic/logeecom`, started separately from
this repo). That container bind-mounts `/home/jovanbjegovic/logeecom/systems` (this repo lives under
`.../systems/public/ecommerce_module_core`) so edits made on the host filesystem are immediately visible
inside the container — no rebuild/restart needed.

Inside `logeecom_dev`, DemoUI is kept running as a background process:

```
PL_PLATFORM=PRO php7.4 -S 0.0.0.0:7000
```
serving from `/var/www/html/public/public/ecommerce_module_core/src/DemoUI/src` (the container's path to
this repo's `src/DemoUI/src`).

The container publishes port 7000 to the host (`0.0.0.0:7000->7000/tcp`), and an `ngrok` tunnel
(`ngrok http 7000`, run separately on the WSL host) exposes it publicly for sharing/testing, e.g.
`https://<random>.ngrok-free.dev/Views/PRO/index.php`.

### Checking / restarting it

```bash
# from Windows PowerShell (or any shell that can reach wsl.exe)
wsl.exe -d Ubuntu -- docker ps -a                         # confirm logeecom_dev is Up
wsl.exe -d Ubuntu -- docker exec logeecom_dev sh -c "ps aux | grep 'php7.4 -S 0.0.0.0:7000'"

# restart the PHP dev server for DemoUI if it died or you switched PL_PLATFORM
wsl.exe -d Ubuntu -- docker exec -d logeecom_dev bash -lc \
  "cd /var/www/html/public/public/ecommerce_module_core/src/DemoUI/src && PL_PLATFORM=PRO php7.4 -d xdebug.mode=off -S 0.0.0.0:7000 > /tmp/demoui-server.log 2>&1"
```

Since edits under `src/DemoUI` on the host are bind-mounted straight into the container, code changes
show up on the next request — only restart the PHP process if you change `PL_PLATFORM` or the server
itself becomes unresponsive.
