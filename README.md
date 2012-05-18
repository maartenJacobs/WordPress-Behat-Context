# WordPress Behat Context

If you're anything like me and you enjoy using Behat, as a PHP alternative to Cucumber, **and** develop applications with WordPress for a living, then this context could really help you!

## What's this all about?!

The simple idea behind this simple repo is to **provide a base Context** for your tests with Behat, so tasks like logging in users, creating posts and users don't have to be rewritten every time.

## Use it!  

### 1. Extend from the base context!
When starting tests, extend your context with the WordPress_Context class, as so:

```php
// Include some files
// require_once 'my/super/nice/libs';

// Include the WordPressContext file
require_once 'loc/of/custom/contexts/WordPressContext.php';

// Normal Namespace 
// use Behat\Behat\Context\ClosuredContextInterface,
//     Behat\Behat\Context\TranslatedContextInterface,
//     Behat\Behat\Context\BehatContext,
//     Behat\Behat\Exception\PendingException;
// use Behat\Gherkin\Node\PyStringNode,
//     Behat\Gherkin\Node\TableNode;
use \WordPress\Mink\Context as WP_Context;

/**
 * Features context.
 */
class FeatureContext extends WP_Context\WordPress_Context {

	// Your feature implementations here

}
```

### 2. Set your behat.yml for super easy setup!

The base Context uses the behat configuration files to find a map of user names to their respective roles. For instance, when creating a user called 'Toni', you can pass this in the configuration file for reuse!

For example:

```yaml
default:
  context:
    parameters:
      base_url: http://dev.notifications.local/
      role_map:
        ender: subscriber
        starter: editor
```

### 3. Use the functions provided!

```php
// Previous code here...
/**
 * Features context.
 */
class FeatureContext extends WP_Context\WordPress_Context {

  /**
   * @Given /^I am logged in as "([^"]*)" with "([^"]*)"$/
   */
  public function iAmLoggedInAsWith($username, $password) {
	// Works out of the box (with a base_url of course)
	// And makes sure the current user is logged out first!
    $this->login($username, $password);
  }

}
```