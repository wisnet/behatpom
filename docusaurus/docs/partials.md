---
id: partials
title: What are partials and how to they work?
sidebar_label: Partials
---

`Partials` are huge help in providing programmatic ways of managing complex Features


## What are Partials and how are they different from snippets?

`Partials` are very similar to `snippets`.  Please review the `snippets` documenation before proceeding further.  The basic concept of how `snippet` works, is very similar to `partials`.  The big difference is that `partials` have `parameters` and are referenced differently.  But the idea that we take some `steps` from a `<Feature>.hbs.feature` and process the **Handlebars template** is the same.


## Let's look at a very simple Partial

From our `loginForm.hbs.feature`, we introduced the following `partial` and used it twice, once for the *bad credentials* and once for the *good credentials*

The `partial` looks like this:

```gherkin
{{>loginForm name="badusername@crossbrowsertesting.com" password="badpassword"}}
```

Given that `partial` reference, we should expect to find a `partial` feature named `loginForm.partial.feature`.  We would also expect that `partial` to have two parameters.  As shown below, there are two parameters, `name` and `password`:

```gherkin
    Given I go to the "Login form" page
    And I fill the "username" with "{{name}}"
    And I fill the "password" with "{{password}}"
    And I click the "submit" button
```
The parameters within the `partial` feature are placeholders for the values that are passed in.  In the referencing `hbs` feature, we pass in the `name` and `password`.  Those values get substitued within the `partial`.

That's a pretty simple example of what a `partial` is and how it differs from a `snippet`.

If you're interested, see [https://zordius.github.io/HandlebarsCookbook/0011-partial.html](https://zordius.github.io/HandlebarsCookbook/0011-partial.html) for more explanations of a `partial`.

## What more can a Partial do?  What's a `helper`?

It might be helpful, if you're interested, to review this page concerning `helpers` with the `LightnCandy` **handlebars**: [https://zordius.github.io/HandlebarsCookbook/0021-customhelper.html](https://zordius.github.io/HandlebarsCookbook/0021-customhelper.html).  Be sure to click on the `Source Code Show/Hide` button.

Besides parameter replacement, **QANoErr** `partials` also have two `helpers` currently:

*  `math`: support simple operators such as `+`, `-`, `*` (add, minus, multiply)
*  `substring`: perform `substr` operation on a `string` using the `start` and `length`.

## Let's look at problem that was made more manageable with the use of the `math` helper.

We had to upload many different audio files for each day of a lesson.  There were 7 audio files for each day.  During testing, it wasn't important what the audio files contained, but that the Administration had the audio files required for each day.  The only difference between adding files on one day verus another was the XPath selector for the "Upload" button.

For example the XPath selector like this:

```php
(//div[@data-name='mm_audio_files']//a[text() = 'Add File'])[<position>]");
```
Each day's files would have a different `<position>`.  So either we had really large and unwieldly features, or we could utilize the `partial` and `helper` **math**

Here's how we did it.  What we are going to do is:

*  Create a `partial` that uses the `math` helper.  Each series of files for the day will have `{{math startindex '+' X}} where X represents the file for the specific day.  Because we have multiple files for each, we will provide a different `startIndex` for each day, increasing the value by 7 since we need 7 files for each day.
*  Generate our `temp` **Gherkin** and validate that the `position` is correct for each file/day.
*  Implement the `<POM>Extend.php` function `initXPath` to dynamically add `XPath Selectors` at run time.

### Step one - Update the hbs template

The `adminProgrammeFiles.hbs.feature` will have a reference to a `partial`.  Note that we're looking at 3 days here.  Each day will have 7 audio files.  So the `startIndex` increase by 7.
```
{{>adminProgrammeFiles startIndex="0"}}  //day 1
{{>adminProgrammeFiles startIndex="7"}}  //day 2
{{>adminProgrammeFiles startIndex="14"}} //day 3
```

### Step two - Create the `partial` to use the `math` helper
Here's the implementation from within the `adminProgrammeFiles.partial.featue`.  The parameter `startIndex` is passed in from the `hbs` template.

```gherkin
    And I click the "File {{math startIndex '+' 1}}" link //file 1
    And I click the "File {{math startIndex '+' 2}}" link //file 2
    And I click the "File {{math startIndex '+' 3}}" link ..	
    And I click the "File {{math startIndex '+' 4}}" link ..	
    And I click the "File {{math startIndex '+' 5}}" link ..	
    And I click the "File {{math startIndex '+' 6}}" link ..	
	And I click the "File {{math startIndex '+' 7}}" link //file 7	
```

### How does this `math` helper work?

Here is code that provides the functionality for `math`:

```php
        $phpStr = LightnCandy::compile($template,
                                       array(
                                           "flags" => LightnCandy::FLAG_RUNTIMEPARTIAL | LightnCandy::FLAG_NAMEDARG | LightnCandy::FLAG_HANDLEBARS,
                                           "partials" => $partials,
                                           "helpers" => array(
                                               "math" => function($lvalue, $operator, $rvalue, $options) {
                                                   if ($operator == '+') {
                                                       return $lvalue + $rvalue;
                                                   } else if ($operator == '-') {
                                                       return $lvalue - $rvalue;
                                                   } else if ($operator == '*') {
                                                       return $lvalue * $rvalue;
                                                   }
                                               },
                                               "substring" => function ($string, $start, $length) {
                                                   return substr($string, $start, $length);
                                               }
                                           )
                                       ));
```
Then fragment `{{math startIndex '+' 1}}` is processed, the `math` helper is recognized and passed the remaining arguments to this `math` helper.

The arguments are such:

*  $lvalue: `startIndex` **which will be either 0, 7, or 14**
*  $operator: `+`
*  $rvalue: `1`
*  return value: the value of `adding` $lvalue with $rvalue

### What does the generated `temp` feature look like?

Here's a `step` in the Gherkin that represents the **2nd** day with the **3rd** file:

```gherkin
  And I click the "File 10" link nn
```
The first file of the second day would startIndex of 7, so `7 + 1`: `8`.  So this generated `step` is looking for the **10th** link.

But we don't have defined in our `ProgrammesNew` **Page Object Model** these XPath locators.  Obvisouly we could define all these XPath locators but since the test could do more then 3 days, we want to support unlimited days.  With our use of the `partial` along w/ the `initXPath` function, we can support unlimited days.

### The helper function allows us to dynamically generate final `step`

We now want to look at the `<PageObjectModel>Extend.php` class and specifically the `function initXPath($parent, $linkName)`.

Remember that the `initXPath` is invoked when there is a `clickLink` or `clickButton`.  In our case, we needed to click the correct `link`.

Continueing with our example, lets assume we are at `startIndex` of 7 and we're looking at the **3rd** file for that day. 

The `partial` step would look like this: 
```gherkin
And I click the "File {{math startIndex '+' 3}}" link 
```

The generated `temp` feature will have this step:
```gherkin
    And I click the "File 10" link 
```	

### The `initXPath` allows us to dynamically create the new element

When we are handling the `link` request, we will add code to the `initXPath` function. The 2nd argument is a String with 2 words separated by a space: `File 10`.  Why is it important to have the first parameter, `File` word included?  Because every `link` that might be on this page will also processed through the `initXPath` and the only `link` that we want to handle dynamically at this point are the `File` links.

Now, in the `initXPath` function we can detect that we are working on the `File` link issue.  The **2nd** value, the number `10` in our example, tells us we need to create an XPath locator for the 10th file.

So then within the `initXPath` function, we could `addToElements` as shown:

```
$xpath = array("xpath" => "(//div[@data-name='mm_files']/input)[**10**]");
$parent->_addToElements("File **10**", $xpath);
```

Remember from the `FeatureContext`, the implementation of `iClickTheLink` function:

```gherkin
    /**
     * @Given I click the :arg1 link
     */
    public function iClickTheLink($arg1)  {
        try {
            $this->current->setupXPath($arg1);
	         ....
            $this->current->clickLink($arg1);
```
Note that first we `setupXPath` and then `clickLink` is called. The `initXPath` function within our `Extend` class is called and we create a new `element` dynamically that is identified by the same parameter as the generated `temp` feature `step`, namely, in this case:
```gherkin
And I click the "File 10" link 
```

So when the `$this->current->clickLink($arg1)` step is executed, we have already added to our collection of elements (XPath locators w/ names) the `File 10` link.


## Example of using substring helper

On one of our projects we had a Questionaire that had 9 questions.  Each question had 4 options and the user was asked to rank them, from 4 to 1.  Each option was associated w/ either a "A", "L", "V", or "K" category. So, each question required all options were ranked.  When  After all the 9 questions were asked, we would sum the responses.  The category would increase by the ranking.  For example, if the "A" option on question 1 was ranked as 3, then that was the value of "A" for that question.  At the end, the category with the lowest sum was the answer, or preference.

As the tester I had the key to all the questions/options.  I could write my test such that with specific rankings I could confirm that the preference.

But rather then have a very long complex `hbs` template I implemented a `partial` utilizing the `substring helper.

### Create the `hbs` template to include the `partial`

Here is the `step` from the `hbs` template:

```gherkin
{{>sensoryQuiz question0="A1 L3 V2 K4" question1="A1 L3 V2 K4" question2="A1 L3 V2 K4" question3="A1 L3 V2 K4"  question4="A1 L3 V2 K4" question5="A2 L4 V1 K3" question6="A2 L4 V1 K3" question7="A2 L4 V1 K3" question8="A2 L4 V1 K3" result="A"}}
```

Each `question 0-8` has a string of `Ax Lx Vx Kx` where X is the `rank` for the `Category`.  In this case I wanted the `A` category to be ranked the lowest and you'll note it was ranked lowest when you see `A1` - that means rank `A` as 1.  There is also `A2` which means rank `A` as 2.

### Create the `partial` feature

Here's a fragment from the `partial` feature.  

```gherkin
    And I click the "Question 0 for {{substring question0 0 1}} with {{substring question0 1 1}}" button
	And I click the "Question 0 for {{substring question0 3 1}} with {{substring question0 4 1}}" button
    And I click the "Question 0 for {{substring question0 6 1}} with {{substring question0 7 1}}" button
    And I click the "Question 0 for {{substring question0 9 1}} with {{substring question0 10 1}}" button
```

It shows for `question0` to `substring` the value of `question0` beginning at the `0` position for a length of `1`. And then another `substring` with position `1` and length of `1`.

In our example, we provided this for `question0`:

` question0="A1 L3 V2 K4"`

So processing our first `partial` **step**, 

`And I click the "Question 0 for {{substring question0 0 1}} with {{substring question0 1 1}}" button`

will generate to 

`And I click the "Question 0 for A with 1" button`

Which means, for the options on **question 0**, make the option `A` rank as 1.

### Implement the `initXPath`

Here's a portion of the `initXPath` function:

```php
 /**
     * Setup the XPath for elements that are similar but w/ different index
     */
    function initXPath($parent, $linkName) {
        $parts = explode(' ', $linkName);
        
        if ($parts[0] == 'Question') {
            $question = $parts[1];
            $category = $parts[3];
            $rank = $parts[5];
            
            $xpath = array("xpath" => "//label[@for='$category']");
            $index = "Question $question for $category with $rank";
            $parent->_addToElements($index, $xpath);
		}
	}
```	
Here we are getting the `parts` of the `$linkName`.  We have three parts:

*  `$question`: something like `question0`
*  `$category`: one of `A, K, L, V`
*  `$rank`: one of `1, 2, 3, 4`

Using these values
*  `$question` as `question0`
*  `$category` of `A` 
*  `$rank` of `1`

Then `$index` will be `"Question question0 for A with 1`";

With these values we call `$parent->_addToElements($index, $xpath);`

### Full `hbs` example

```gherkin
Feature: Quiz
  As a registered user
  I can take the quiz 
  I can view the results
  

  Background:
{{{clientLogsIn}}}  

  @javascript 
  Scenario: I can the quiz with majority of Answers are A
#first question  
{{>quiz question0="A1 L3 V2 K4" question1="A1 L3 V2 K4" question2="A1 L3 V2 K4" question3="A1 L3 V2 K4"  question4="A1 L3 V2 K4" question5="A2 L4 V1 K3" question6="A2 L4 V1 K3" question7="A2 L4 V1 K3" question8="A2 L4 V1 K3" result="A"}}

  @javascript 
  Scenario: I can the quiz with majority are V
{{>quiz question0="A2 L3 V1 K4" question1="A2 L3 V1 K4" question2="A2 L3 V1 K4" question3="A2 L3 V1 K4" question4="A2 L3 V1 K4" question5="A1 L3 V2 K4" question6="A1 L3 V2 K4"  question7="A1 L3 V2 K4"  question8="A1 L3 V2 K4" result="V"}}


  @javascript 
  Scenario: I can the quiz with majority is L
{{>quiz question0="A3 L1 V2 K4" question1="A3 L1 V2 K4" question2="A3 L1 V2 K4" question3="A3 L1 V2 K4" question4="A3 L1 V2 K4" question5="A1 L3 V2 K4" question6="A1 L3 V2 K4"  question7="A1 L3 V2 K4"  question8="A1 L3 V2 K4" result="L"}}


  @javascript 
  Scenario: I can the quiz with majority as K
{{>quiz question0="A3 L4 V2 K1" question1="A3 L4 V2 K1" question2="A3 L4 V2 K1" question3="A3 L4 V2 K1" question4="A3 L4 V2 K1" question5="A1 L4 V2 K3" question6="A1 L4 V2 K3"  question7="A1 L4 V2 K3"  question8="A1 L4 V2 K3" result="K"}}
```

### The generated `temp` feature

The generated `temp` feature has `steps` that now look contains `steps` like this:

```
  @javascript 
  Scenario: I can take the quiz with majority of Answers are Audio

    Given I go to the "quiz" page
#first question
    And I click the "Question 0 for A with 1" button
    And I click the "Question 0 for L with 3" button
    And I click the "Question 0 for V with 2" button
    And I click the "Question 0 for K with 4" button
    And I take a snapshot called 'question_1'
    And I click the "Next 0" button
    And I wait "1" second
```

### Size comparison of `hbs`, `partial`, and the generated `temp` feature

The entire `hbs` template, as shown above, is only **27** lines long.

The entire `partial` template is **94** lines long

The file size of the final generated `feature` is **408 steps**!

That's a great demonstration of how `partials` can help create dynamic tests and make the management of those tests much simpler.  

And now, if we wanted to make additional tests for boundary cases, we only will edit the `hbs` file.





