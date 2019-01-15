# ecommerce_module_core
Core library for e-commerces (PS, Woo, Magento, etc) modules

## Commit Procedure
Before any commit, you **MUST** run all tests and they all MUST pass.

You **MUST** run code inspection so standards could be followed. 
Assuming you are using PHPStorm ([you should](https://www.google.com/search?q=why+should+I+use+phpstorm)), 
Select `src` and `tests` folders in project view and choose "Inspect code..." from right click menu. 
Select "Selected files" and click OK.
When inspection finishes, no errors should be reported except spelling errors (you should review them 
as well just in case).

Also, in commit dialog you must choose at least these options:
 - Reformat code
 - Perform code analysis
 - Check TODO

This will also run analysis on commit but only on changed files.
