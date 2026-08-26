# Mage-OS NetSuite Connector

`MageOS_NetSuiteConnector` synchronises Mage-OS and Magento Open Source with Oracle NetSuite over
the NetSuite SOAP API.

This repository consolidates seventeen related Rocket Web modules into one independently named
Mage-OS module:

- Connector core, configuration, and the processing monitor
- MySQL-backed job queue
- Products, bundle and configurable links, and product images
- Customers, customer groups, and customer import
- Orders, invoices, credit memos and refunds, and shipments
- Tax and discount mapping
- Inventory, with both single-location and multi-location strategies

The package has its own Composer name, PHP namespace, Magento module name, database tables,
configuration paths, admin routes, and CLI command names. It does not replace or mutate an
installed Rocket Web package.

## Status

This is a new module identity prepared for Mage-OS Lab. Existing Rocket Web installations are not
migrated automatically. There is no upgrade path from the `rocketweb/netsuite-connector-*`
packages, and the Composer manifest declares `replace` entries for all seventeen of them so a mixed
installation is refused rather than half-applied.

Configuration paths moved from `rocketweb_netsuite/*` to `mageos_netsuite/*` and tables are now
prefixed `mageos_netsuite_`, so settings and mappings must be entered again on a store that
previously ran the Rocket Web modules.

## Requirements

- A currently supported Mage-OS or Magento Open Source release, with `magento/framework` in the
  103.x or 104.x series
- PHP 8.1 through PHP 8.5, within the range the selected platform release supports
- A NetSuite account with SOAP Web Services enabled, and token-based authentication credentials
- Magento cron, because the connector is driven by scheduled jobs rather than by request

Mage-OS 3.4.0, based on Magento Open Source 2.4.9, is an explicit compatibility target. The suites
are verified against Magento Open Source 2.4.8-p4 on PHP 8.2, 8.3 and 8.4, and against Mage-OS 3.4.0
on PHP 8.3, 8.4 and 8.5.

## Installation

```bash
composer require mage-os/module-netsuite-connector
bin/magento module:enable MageOS_NetSuiteConnector
bin/magento setup:upgrade
bin/magento cache:clean
```

For a source checkout, place or symlink the repository at `app/code/MageOS/NetSuiteConnector`, then
run the Magento commands above without `composer require`.

## Use

Global settings are under **Stores > Configuration > Mage-OS > NetSuite Connector**.

The processing monitor is under **System > NetSuite > Monitor Dashboard**. It records every queued
message, its payload, and the outcome of each attempt.

### Synchronisation

```bash
# Run a synchronisation pass. Modes: all, import, export, stock, location
bin/magento netsuite:cron --mode=all
```

`location` applies only when the inventory mode is set to multiple locations.

### Operational commands

```bash
# Process a record, or a CSV list of records, by type
bin/magento netsuite:utils:processrecord

# Import NetSuite records changed within a period of time
bin/magento netsuite:utils:importdaterange

# Process a single item from the import or export queue
bin/magento netsuite:utils:processsingleitem

# Manually import a single product
bin/magento netsuite:utils:importsingleproduct

# Rebuild product links by SKU
bin/magento netsuite:utils:relinkbysku

# Manually update stock information
bin/magento netsuite:utils:updatestocks
```

### Maintenance commands

```bash
# Clean monitor records by status, the same logic as the cron job
bin/magento netsuite:maintenance:cleanmonitor

# Send warnings for over-threshold queues, the same logic as the cron job
bin/magento netsuite:maintenance:sendqueuewarning
```

Three cron jobs run in the `default` group: queue threshold warnings hourly, stuck message recovery
hourly, and monitor cleanup daily.

### Inventory mode

`mageos_netsuite/general/inventory_mode` selects how NetSuite stock maps onto Magento:

- `single`, the default, maps NetSuite stock onto the default Magento source
- `multi` maps each NetSuite location onto its own Magento source, and enables the `location`
  synchronisation mode

Both strategies ship in this package. Changing the setting changes the behaviour, with no package
swap and no conflicting installs.

## Development validation

Run the static checks from the repository root:

```bash
composer validate --strict --no-check-publish
vendor/bin/phpcs --standard=phpcs.xml.dist .
find . -path './.git' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
```

The unit suite needs no Magento bootstrap and completes in well under a second:

```bash
cd dev/tests/unit && php ../../../vendor/bin/phpunit -c phpunit.xml
```

The integration suite installs a sandbox database and exercises the import and export processors
against recorded NetSuite responses:

```bash
cd dev/tests/integration && php ../../../vendor/bin/phpunit -c phpunit.xml
```

No test contacts NetSuite. The SOAP client is replaced by a test double backed by recorded
responses, and two contract tests assert that the double still matches the SDK client signature and
that every recorded response uses only properties the SDK classes declare.

## Provenance and license

The source is contributed by Rocket Web Inc. Original copyright and author notices are retained in
every source file.

The package uses the [Open Software License 3.0](LICENSE.txt) and the Academic Free License 3.0,
matching the Composer metadata and the notices in source files.
