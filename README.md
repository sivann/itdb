# About
ITDB is a web based asset inventory management tool used to store information 
about assets found in office environments, with a focus -but not lmited to- 
IT assets. It is not or targets for ITIL/CMDB compliance (yet), but it has 
served me for years and hopefully it will do the same for you :-) It is aimed as 
an intranet tool.

ITDB comes with sources and is distributed under the GNU Public license.

# Original Homepage
http://www.sivann.gr/software/itdb/


# Contributing
Please consider that my free time is now extremely limited, and so even valid pull requests may not be addressed for a long time.


# First Update & Renewal 20/03/2021 with future :
  1. Remove Flash elements.
  2. Update TCPDF to version 6.3.5.
  3. Minor update Datatables format.
  4. Update UI format.
 

# Security
Do *NOT* expose ITDB to the public internet. It is not secure, it is aimed for intranets. If you need to do so, please configure an HTTP auth password on your web server so it will be hidden behind a password.

### Bug fixes
Please take the time to consider the following when submitting a bug:
* how does your fix handle non-us characters? (E.g. Chinese, Greek, etc)
* how does your fix handle non-us locales ? (especially date manipulation fixes)
* does your fix use strtotime ? (don't use it)
* how does your fix handle older SQLite versions? 
* how does your fix handle older/newer PHP versions? 
* how does your fix work with Firefox/Chrome/IE ?
* how does your fix scale with lots of items?


### Major contributions
* rewrite the DB requests using PDO (and prepared statements)
* rewrite the item associations tables using datatables with server-side AJAX
* update datatables to the most recent version
* rewrite the front controller and auth using a framework (e.g. slim)
* very simple ticketing
 
### Minor contributions
#### UI
* item user selection and possibly others: instead of pull-down select, use jqueryui's autocomplete combobox
* inplace edit/add itemtypes, agents, users. Configurable to allow edit/add for specific user and select for others.
* design PC/server layout in Locations. Assign Items to x/y over imagemap
* edit previous/next item functionality. E.g. from an item list of a search result. 
* replace file uploader with a recent one also supporting drag&drop 
* unify tab association code

### Schema
* add history (renewals) & events in software, like in items.
* list of services and relations to items
* virtual/non virtual item (e.g. VM). Parent (physical) item. Virtual may show as tooltip of rack position of parent. Also
* add knowledge area, with connections to items & software (text)
* software classes (types). E.g. O/S
* add a cron notification sample script in contrib/ for contract/warranty expiration
* license models: on inventory data:per installation, OEM and machine licensing. On external data sources: qualified desktop, CPU, user, named user, server, client access license (CAL), site, enterprise and user-defined models. TBD.
* port connectivity management (TBD if needed)
* power cable management (TBD if needed)


Thank you!
