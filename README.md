# TerraINTRA

*This repository is provided for historical purposes, and is not usable as is.*

TerraINTRA is/was custom/NIH BI/CRM/invoicing system, written to supplement old NT4 invoicing system, written in ASP and which ran on Jet (Access) Database, and everything had to work with it. It was third rewrite, and has been now replaced also.

It's originally written for PHP 5.1 and Drupal 4.7, and later been updated to run on PHP 5.2 and Drupal 6. Last modification was done in spring 2011.

## Features

Features included were:
* Microformats for client side metadata parsing
* Drag 'n Drop, in-browser and also in and out browser. Utilized previous Microformat implementation.
* Custom chainable ORM (see class api/lib/Intra/Object.php).
* Some unicorn magic, as it was important that it could read user minds.
* Limited email integration.
* Thunderbird-client integration.
* Aggregated data from multiple external sources, like European Vat registry.

## Structure

### Drupal modules

* `activity/` –  Module for displaying customer related activities.
* `agreement/` – Maintenance agreement. Very special case for software license maintenance.
* `api/` – Base package.
 * `lib/` - Contains custom ORM, API and model/view stuff.
* `company/` - Handles companies
* `contact/` - Handles persons.
* `invoice/` - For invoice display.
* `menu/` - Menu connector for old ASP interface.
* `search/` – Search modules.

### Other files

* `terraintra manual.pdf` – User manual.
* `terraintra-contact-0.1.3.1/` – Thunderbird extension.
* `webfrontend/` – Compatibility and other files for public website.
 * `drupal.inc.php` – Compatibility functions from Drupal.
 * `intra.functions.inc.php` – Functions for distributor PO/invoicing system.
 * `invoicefactory.inc.php` – Drupal 4.7 module for invoice creation.
 * `price_calculator.class.inc.ph` – Functions for creating software bundles.
 * `fop/` – Apache FOP stylesheets for invoice creation.
