CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Module Details
 * Recommended modules
 * Configuration
 * Maintainers


INTRODUCTION
------------
Views extra modules provide extra arguments for contextual filters based on
session, cookie or token. This extends filter functionality to pass cookie,
session variable or token as argument to views.


MODULE DETAILS
--------------
Views Extras allows to create views that accept arguments from session, cache
or token. As of now, module provides following type of arguments:

* Session variable from session
    Views will accept session variable from the current session to filter out
    results. Also, in case the session variable is not set, views can use the
    fallback value and best part is it supports token.
* Cookie variable from cookie
    Works in the similar manner, but instead of session variable cookie variable
    would be used in this case and this also supports token as fallback value.


RECOMMENDED MODULES
-------------------

* TOKEN (https://www.drupal.org/project/token)
  When enabled user tokens would be available in configuration settings for
  arguments.


CONFIGURATION
-------------
* Session variable from session:

    * Session variable key: Key of SESSION variable, e.g. for $_SESSION["key"],
    the key would be "key".

    * Fallback value: If session variable is not set, what should be the fallback
    value. User tokens are supported.

    * Cache Maximum Age: As drupal 8 cache api as of now does not support
    session:name cache context. So, we can additionally provide maximum cache age
     in case the session variable is updated/changed in between a session.


* Cookie variable from cookie
    * Cookie variable key: Key of COOKIE variable, e.g. for $_COOKIE["Drupal_visitor_key"],
    the key would be "key".
    Why we are appending prefix "Drupal_visitor"?
    Reference: https://api.drupal.org/api/drupal/core%21modules%21user%21user.module/function/user_cookie_save/8.5.x

    * Fallback value: If cookie variable is not set, what should be the fallback
    value. User tokens are supported.


MAINTAINERS
-----------
Current maintainers:

 * Purushotam Rai (https://drupal.org/user/3193859)


This project has been sponsored by:
 * QED42
  QED42 is a web development agency focussed on helping organisations and
  individuals reach their potential, most of our work is in the space of
  publishing, e-commerce, social and enterprise.
