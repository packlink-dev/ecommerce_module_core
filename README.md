# ecommerce_module_core
[![CircleCI](https://circleci.com/gh/packlink-dev/ecommerce_module_core.svg?style=svg&circle-token=5a8c6ee766cec62056be80c0c910a05475649ac0)](https://circleci.com/gh/packlink-dev/ecommerce_module_core)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/6666d16039094921ac58a5dba588ac60)](https://www.codacy.com/gh/packlink-dev/ecommerce_module_core?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=packlink-dev/ecommerce_module_core&amp;utm_campaign=Badge_Grade)
[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/6666d16039094921ac58a5dba588ac60)](https://www.codacy.com/gh/packlink-dev/ecommerce_module_core?utm_source=github.com&utm_medium=referral&utm_content=packlink-dev/ecommerce_module_core&utm_campaign=Badge_Coverage)

Core library for e-commerces (PS, Woo, Magento, etc) modules

## Commit Procedure
Before any commit, you **MUST** run all tests and they all MUST pass.
To run tests on different versions of PHP, go to the root directory and in terminal
run command:
```bash
sh run-tests.sh
```

You **MUST** run code inspection so standards could be followed. 
Assuming you are using PHPStorm ([you should](https://www.google.com/search?q=why+should+I+use+phpstorm)), 
Select `src` and `tests` folders in project view and choose "Inspect code..." from right click menu. 
Select "Selected files" and click OK.
When inspection finishes, no errors should be reported except spelling errors (you should review them 
as well just in case).

Also, in commit dialog you must choose at least these options:
-   Reformat code
-   Perform code analysis
-   Check TODO

This will also run analysis on commit but only on changed files.

### Resource compilation
To compile resources in the root directory execute the following command:
`php cssCompile.php`
