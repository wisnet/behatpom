---
id: snippets
title: What are snippets and how to they work
sidebar_label: Snippets
---

**Snippets** help reduce duplicate Steps within Features


## What are Snippets

**Snippets** contain **Gherkin** steps that can be included within any feature with in the project.

Our original **Gherkin** Features are written with the file pattern `<Feature>.hbs.feature` and are contained in the "features/gherkin" directory.

The `hbs` identifies the file as a **Handlebars** template.  When we run one of the `composer` commands having to do w/ `Behat`, we run this command `composer behat-prep` which actually then runs `vendor/bin/preprocessGherkin`. 

## What does preprocessGherkin with Snippets?

`preprocessGherkin` reads all the files in the `features/gherkin` directory looking for files w/ the pattern `*.snippet.feature`.  It also reads all the `*.hbs.feature` files.

It then uses a `PHP` tool called `LightnCandy`. You can see it here: [https://github.com/zordius/lightncandy](https://github.com/zordius/lightncandy).  This project is a **PHP implementation of Handlebars**.  

We treat our `<Feature>.hbs.feature` as our `template`, our `handlebars template`.  The `template` is compiled and then our `data` is provided to the processed template.

Our `<Feature>.hbs.feature` might include a line like this,

```gherkin
{{{spinner}}}
```
As the example from our `starter` shows in inclusion of the `{{{spinner}}}`

```
Feature: Login to page 
  A login attempt with valid credentials
  Should be accepted
  A login attempt with bad credentials
  Should be rejected
    
  @javascript @snapshot
  Scenario: Login with bad credentials
{{>loginForm name="badusername@crossbrowsertesting.com" password="badpassword"}}
{{{spinner}}}
    Then the "Username or password is incorrect" element exists
    And I take a snapshot called 'failed_login'
```

and  our `spinner.snippet.feature` look like this:

```gherkin
    And I wait for the "Verifying credentials" spinner
```	
Notice how the `gherkin` uses the `spinner` name and the `snippet.feature` is named the same, namely `spinner`.

Now, within any **Gherkin** `hbs` template, we can use the `spinner` snippet.  

Note that a `snippet` can be any length necessary.

Once the `preprocessGherkin` finishes, we should see our fully realized **Gherkin** in the `features/temp` directory.  Continuing w/ our example, this would be our `features/temp/*.feature`:

```gherkin
Feature: Login to page 
  A login attempt with valid credentials
  Should be accepted
  A login attempt with bad credentials
  Should be rejected
    
  @javascript @snapshot
  Scenario: Login with bad credentials

    Given I go to the "Login form" page
    And I fill the "username" with "badusername@crossbrowsertesting.com"
    And I fill the "password" with "badpassword"
    And I click the "submit" button
    And I wait for the "Verifying credentials" spinner
```
