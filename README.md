# MageOS_NetSuiteConnector

NetSuite integration for Mage-OS and Magento 2 Open Source.

The module synchronises products, product images, customers, customer groups, orders, invoices,
credit memos, shipments, tax, discounts, and inventory between Magento and NetSuite over the
NetSuite SOAP API. Work runs through a MySQL-backed queue and the connector CLI commands, driven
by cron.

## Requirements

- PHP 8.1 or later
- Magento Open Source 2.4.6 or later, or Mage-OS
- A NetSuite account with SOAP Web Services enabled

## Installation

```
composer require mage-os/module-netsuite-connector
bin/magento module:enable MageOS_NetSuiteConnector
bin/magento setup:upgrade
```

## Configuration

Stores > Configuration > Mage-OS > NetSuite Connector.

## Structure

The module is organised by domain. Each folder maps to one area of the integration.

| Folder | Area |
|---|---|
| `Core/` | SOAP client, config, queue processing, CLI commands, admin UI |
| `Queue/` | MySQL queue and processing monitor |
| `Product/` | product export and import |
| `ProductImages/` | product image import |
| `Customer/` | customer export and customer group mapping |
| `CustomerImport/` | customer import |
| `Order/` | order export and order change import |
| `Tax/` | tax mapping for orders and invoices |
| `Discount/` | discount mapping for orders and invoices |
| `Invoice/` | invoice export and cash sale import |
| `Refund/` | credit memo and refund sync |
| `Shipment/` | item fulfilment import |
| `Inventory/` | stock sync, with single-location and multi-location modes |

## Licence

OSL-3.0 and AFL-3.0. See `LICENSE.txt`.
