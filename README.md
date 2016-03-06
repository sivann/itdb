# About
ITDB is a web based asset inventory management tool used to store information 
about assets found in office environments, with a focus -but not lmited to- 
IT assets. It is not or targets for ITIL/CMDB compliance (yet), but it has 
served me for years and hopefully it will do the same for you :-) It is aimed as 
an intranet tool.

ITDB comes with sources and is distributed under the GNU Public license. 

# Homepage 
http://www.sivann.gr/software/itdb/

# Contributing
Please consider that my free time is now extremely limited, and so even valid pull requests may not be addressed for a long time.

# Status
As I no longer have enough time to improve ITDB, I can only provide bug fixes for newer PHP or browser versions. Please do not ask for new features.
 
## Scope of pull requests
Thank you for your time to consider contributing. Please take into account ITDB is only an inventory software. It may offer some basic reporting by quering 
its own data because it may have access to invoices, users and equipment.
ITDB tries to adhere to the [do one thing](https://en.wikipedia.org/wiki/Unix_philosophy#Do_One_Thing_and_Do_It_Well) philisophy.
ITDB does not and should not aim to provide the functionality of other software e.g. network monitoring tools, finance software or network diagnostics software. 

## Extent of pull request 
Pull requests should fix 1 and only 1 thing. Otherwise it is extremely difficult to test and review.

### Bug fixes
Please take the time to consider the following when submitting a bug:
* how does your fix handle non-us characters? (E.g. Chinese, Greek, etc)
* how does your fix handle non-us locales ? (especially date manipulation fixes)
* does your fix use strtotime ? (don't use it)
* how does your fix handle older SQLite versions? 
* how does your fix handle older/newer PHP versions? 
* how does your fix work with Firefox/Chrome/IE ?
* how does your fix scale with lots of items?


### New UI fields pull requests:
Please take the time to consider the following when submitting a generic pull-request :
* Is your new  field universally useful? Can you think of cases where it doesn't make sense?
* Can your functionality be already addressed by the current fields?
* Does  your field have specific search needs?

if the answer is no to at least one of the above then probably you don't need that field. ITDB has a lot of fields on the "no" category, let's not add any more.

## Welcomed pull requests
Any pull requests fixing the following would be welcome. Please open a discussion before starting to code.

### Major contributions
* rewrite the DB requests using PDO
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
