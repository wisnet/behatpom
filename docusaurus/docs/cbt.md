---
id: cbt
title: Cross Browser Testing
sidebar_label: CBT usage
---

Follow this guide to understand how to **QANoErr** helps you test with [Cross Browser Testing](https://crossbrowsertesting.com/)

## Directory and files

For running tests on **Cross Browser Testing**, **QANoErr** uses the files in the `features/cbt` directory.  The files look like the following:

![files](assets/cbt-directory.png)

Let's look at each file:

*  `.gitignore`: Ignore the generated `behat-cbt.yml` (not shown) and ignore the `cbt.env.json` auth file
*  `behat-cbt.hbs.yml`: Handlebars template for submitting a device/browser to CBT
*  `browser_devices.json`: Some of the device/browsers available for automated testing on CBT. 
   *  `cbt.env.json.example`:  Example `cbt.env.json` file.  Edit this file and provide your CBT credentials .  Rename it to `cbt.env.json`.  It will not be saved in Git as it is `ignored`.  You get these values from [https://app.crossbrowsertesting.com/account](https://app.crossbrowsertesting.com/account).  When updating this file, be sure to maintain the `%40` as the `@`.
*  `device.hbs.html`:  Handlebars template for producing the **Gherkin** report.
*  `devices.json`:  The actual device/browser selections to execute.  This is a subset of the `browser-devices.json` 
*  `getBrowsers.sh`: Script to retrieve all device/browsers available for testing on CBT.  Be sure to update the `userid` and `authkey` before running
*  `README.md`


## behat-cbt.hbs.yml

```yml
default:
  autoload:
    '': "%paths.base%/../bootstrap"  
  suites:
    mobile:
      paths: ["%paths.base%/../temp/loginForm.feature"]
      contexts:
        - ProjectContext:
            - {{build}}
            - {{os_api_name}}
            - {{browser_api_name}}

  extensions:
    Behat\MinkExtension:
      default_session: selenium2
      browser_name: chrome
      base_url: http://crossbrowsertesting.github.io
      selenium2:
        #wd_host: http://localhost:4444/wd/hub
        wd_host: https://{{username}}:{{authkey}}@crossbrowsertesting.com/wd/hub
        capabilities:
          extra_capabilities:
            os_api_name: {{os_api_name}}
            browser_api_name: {{browser_api_name}}
            build: 1.0.0
            record_video: true
            record_network: true
            max_duration: 1400
            new_command_timeout: 1400

    SensioLabs\Behat\PageObjectExtension:
      namespaces:
        page: [pages]
        element: [pages]


```
Let's review this file.   The `%paths.base%` is the current directory, namely `features/cbt`.  We will `autoload` all the files under `bootstrap` which is the `Context` and `pages`.

We will run one specific script defined in the `mobile/paths`, namely the `loginForm.feature`.  Update this value to run a different Gherkin.

The `ProjectContext` takes 3 parameters:
*  **build**The directory where the results shall be written.
*  **os_api_name**The OS that is being tested
*  **browser_api_name**The browser that is being tested.

Next we come to the `Behat\Extension` and we are running `selenium2` and the `base_url` is provided.  This is the URL where CBT will start your mobile app at.

In order to run our test on CBT, we have to provide the `wd_host` value.  If you've created the `cbt.env.json` with the `userid` and `authkey` from CBT, then the `wd_host` will be setup correctly.  You can get your credentials from here [https://app.crossbrowsertesting.com/account](https://app.crossbrowsertesting.com/account)

The `extra_capabilities` provides the information that CBT needs:

*  **os_api_name**: The `os_api_name` from the `devices.json` file
*  **browser_api_name**: The `browser_api_name` from the `devices.json` file
*  **build**:  See [https://help.crossbrowsertesting.com/selenium-testing/getting-started/crossbrowsertesting-automation-capabilities/](https://help.crossbrowsertesting.com/selenium-testing/getting-started/crossbrowsertesting-automation-capabilities/)
*  **record_video**:  See [https://help.crossbrowsertesting.com/selenium-testing/getting-started/crossbrowsertesting-automation-capabilities/](https://help.crossbrowsertesting.com/selenium-testing/getting-started/crossbrowsertesting-automation-capabilities/)
*  **record_network**: See [https://help.crossbrowsertesting.com/selenium-testing/getting-started/crossbrowsertesting-automation-capabilities/](https://help.crossbrowsertesting.com/selenium-testing/getting-started/crossbrowsertesting-automation-capabilities/)
*  **max_duration**:  See [https://help.crossbrowsertesting.com/selenium-testing/faq/default-duration-selenium-test-timeout-information/](https://help.crossbrowsertesting.com/selenium-testing/faq/default-duration-selenium-test-timeout-information/)
*  **new_command_timeout**


And finally, the `SensioLabs\Behat\PageObjectExtension` namespaces are provided.

## How to run the test against CBT?

The command `vendor/bin/buildCBTTests` performs the tests.  Let's look at what it actually does.

`buildCBTTests` starts of by processing the `cbt.env.json` file which contains the `username` and `authkey`.  It reads the `behat-cbt.hbs.jml` to determine what the `feature` name is and incorporates that into the directory name that will appear under the `results` file.  

It then creates the `results` directory name under the `results` file, something similar to `2018-10-01-08-00-loginForm`.  The name has the Year-Month-Day-Hour-Minute time plus the feature name.

Then `buildCBTTests` reads the `devices.json` files.  This will be the input data for the handlebars template `behat-cbt.hbs.yml`

For each `device/browser` combination in the `devices.json` file, we first check if there are any `CBT Tests` running on CBT.  Our license only allows us to run 1 Automated test at a time.  So `buildCBTTests` checks if there are any currently running tests and keeps checking until that test is done.  ONce there are no active tests, we proceed to start testing the first `device`.

Each `os\browser` combination results in numerous files being generated in the `results` directory.  After all the `os\browsers` have completed, then the `GridReport` is run.  It takes all the results from the `os\browser` tests and generates the `index.html` for the Device report and the `grid.html`.

The `Device` report looks like this:

![index](assets/indexOFDevices.png)

And if you select a specific device, it will look similar to this:

![device](assets/deviceReport.png)

If you select the `Grid` report, it will look similar to this:

![grid](assets/grid-report.png)

With the `Device` report, you see the **Gherkin** steps intermingled with the `snapshots` from running the tests.  This report is mainly for the Business to validate the test is correct.

With the `Grid` report, you have a matrix that shows for each `os\browser`, a row for each `snapshot` that was taken.  Each row is the same `page` enabling a quick review that all the responsive devices look correct.  If the test had failed for one of the `os\browser`, there will be a red "X" and by clicking on the "View Log", you can see exactly what failed.

