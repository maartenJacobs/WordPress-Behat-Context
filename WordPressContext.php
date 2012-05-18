<?php

namespace WordPress\Mink\Context;
use \Behat\Mink\Behat\Context as BehatContext; 

class WordPress_Context extends BehatContext\MinkContext {

  protected $base_url;
  protected $email_url;
  protected $driver;
  protected $session;
  protected $role_map;

  /**
   * Initializes context.
   * Every scenario gets it's own context object.
   *
   * @param   array   $parameters     context parameters (set them up through behat.yml)
   */
  public function __construct(array $params) {
    $this->base_url = $params['base_url'];
    $this->role_map = $params['role_map'];
    $this->email_url = $params['email_url'];
  }
  
  /**
   * Given a list of usernames (user_login field), checks for every username
   * if they exist. Returns a list of the users that do not exist.
   *
   * @param array $users
   * @return array
   * @author Maarten Jacobs
   **/
  protected function check_users_exist(array $users) {
    $session = $this->getSession();

    // Check if the users exist, saving the inexistent users
    $inexistent_users = array();
    $this->_visit( 'wp-admin/users.php' );
    $current_page = $session->getPage();
    foreach ($users as $username) {
      if (!$current_page->hasContent($username)) {
        $inexistent_users[] = $username;
      }
    }

    return $inexistent_users;
  }

  /**
   * Creates a user for every username given (user_login field).
   * The inner values can also maps of the following type:
   *  array(
   *    'username' => 
   *    'password' => (default: pass)
   *    'email' => (default: username@test.dev)
   *    'role' => (default: checks rolemap, or 'subscriber')
   *  )
   *
   * @param array $users
   * @author Maarten Jacobs
   **/
  protected function create_users(array $users) {
    $session = $this->getSession();

    foreach ($users as $username) {
      if (is_array($username)) {
        $name = $username['username'];
        $password = array_key_exists('password', $username) ? $username['password'] : 'pass';
        $email = array_key_exists('email', $username) ? $username['email'] : str_replace(' ', '_', $name) . '@test.dev';
      } else {
        $name = $username;
        $password = 'pass';
        $email = str_replace(' ', '_', $name) . '@test.dev';
      }

      $this->visit( 'wp-admin/user-new.php' );
      $current_page = $session->getPage();

      // Fill in the form
      $current_page->findField('user_login')->setValue($name);
      $current_page->findField('email')->setValue($email);
      $current_page->findField('pass1')->setValue($password);
      $current_page->findField('pass2')->setValue($password);

      // Set role
      $role = ucfirst( strtolower( $this->role_map[$name] ) );
      $current_page->findField('role')->selectOption($role);

      // Submit form
      $current_page->findButton('Add New User')->click();
    }
  }

  /**
   * Fills in the form of a generic post.
   * Given the status, will either publish or save as draft.
   *
   * @param string $post_type
   * @param string $post_title
   * @param string $status Either 'draft' or anything else for 'publish'
   * @author Maarten Jacobs
   **/
  protected function fill_in_post($post_type, $post_title, $status = 'publish') {
    // The post type, if not post, will be appended.
    // Rather than a separate page per type, this is how WP works with forms for separate post types.
    $uri_suffix = $post_type !== 'post' ? '?post_type=' . $post_type : '';
    $this->_visit( 'wp-admin/post-new.php' . $uri_suffix );
    $session = $this->session = $this->getSession();
    $current_page = $session->getPage();

    // Fill in the title
    $current_page->findField('post_title')->setValue( $post_title );
    // Fill in some nonsencical data for the body
    // $current_page->findField('content')->setValue( 'Testing all the things. All the time.' );

    // Click the appropriate button depending on the given status
    $state_button = 'Publish'; 
    switch ($status) {
      case 'draft':
        // We're good.
        break;
      
      case 'publish':
      default:
        $state_button = 'Publish';
        break;
    }
    $current_page->findButton($state_button)->click();

    // Check if the post exists now
    $this->_visit( 'wp-admin/edit.php' . $uri_suffix );
    assertTrue( $this->getSession()->getPage()->hasContent( $post_title ) );
  }

  /**
   * Makes sure the current user is logged out, and then logs in with
   * the given username and password.
   * 
   * @param string $username
   * @param string $password 
   * @author Maarten Jacobs
   **/
  protected function login($username, $password = 'pass') {
    $session = $this->session = $this->getSession();
    $current_page = $session->getPage();

    $this->visit( 'wp-login.php?action=logout' );
    if ($session->getPage()->hasLink('log out')) {
      $current_page->find('css', 'a')->click();
      $current_page = $session->getPage();
    }

    // If the user is not logged in, then the logout action will just show the login form
    // $this->_visit( 'wp-admin' );

    $current_page->fillField('user_login', $username);
    $current_page->fillField('user_pass', $password);
    $current_page->findButton('wp-submit')->click();

    // Assert that we are on the dashboard
    assertTrue( $session->getPage()->hasContent('Dashboard') );
  }

  protected function _visit($path) {
    $this->getSession()->visit( $this->base_url . $path );
  }

}