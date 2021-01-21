<?php

namespace Drupal\views_argument_token\Plugin\views\argument_default;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\Core\Url;

/**
 * The Token argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "token",
 *   title = @Translation("Token")
 * )
 */
class TokenArgument extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['argument'] = array('default' => '');
    $options['process'] = array('default' => 0);
    $options['and_or'] = array('default' => '+');
    $options['all_option'] = array('default' => TRUE);
    $options['debug'] = array('default' => 0);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['argument'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#default_value' => $this->options['argument'],
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token'] = \Drupal::service('views_argument_token.token')
        ->tokenBrowser();
    }

    $form['process'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Get fields raw values'),
      '#description' => $this->t('Get raw values of fields (only fields are supported).<br/>For example, get ID instead of title for entity reference fields.'),
      '#default_value' => $this->options['process'],
    );
    // @todo : allow to choose and / or for any multiple value.
    $form['and_or'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Multiple values handling condition'),
      '#options' => array('+' => $this->t('Or'), ',' => $this->t('And')),
      '#default_value' => $this->options['and_or'],
      '#states' => array(
        'invisible' => array(
          ':input[name="options[argument_default][token][process]"]' => array('checked' => FALSE),
        ),
      ),
      '#description' => $this->t('You should authorize multiple values at the bottom of this form.'),
    );

    $form['all_option'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send "all" if no value'),
      '#default_value' => $this->options['all_option'],
      '#description' => $this->t('You should enable the "all" argument below.'),
    );

    $form['debug'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show debug'),
      '#default_value' => $this->options['debug'],
      '#description' => $this->t('Show as a message the argument value for debugging purposes.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $token_service = \Drupal::token();
    $argument = $this->options['argument'];
    $process = $this->options['process'];
    $debug = $this->options['debug'];
    $all_option = $this->options['all_option'];

    $tokens = $this->tokenScan($argument);

    // Presence of a token concerning current user.
    if (isset($tokens['current-user'])) {
      // Case of processing with raw values.
      if ($process) {
        // Get current user value.
        // @todo : dependency injection.
        $current_user = \Drupal::currentUser();
        $account = user_load($current_user->id());

        // Process raw value (even multiple values).
        $argument = $this->processToken($account, 'current-user', $argument);
      }

      // If there is still a current user token.
      if (strpos($argument, 'current-user')) {
        if (!isset($current_user)) {
          // @todo : dependency injection.
          $current_user = \Drupal::currentUser();
        }

        // If the token cannot be translated, it will be removed from
        // the final text.
        $argument = $token_service->replace($argument, ['current-user' => $current_user], ['clear' => TRUE]);
      }
    }

    // Get type current entity.
    $current_path = \Drupal::service('path.current')->getPath();
    $params = Url::fromUri("internal:" . $current_path)->getRouteParameters();
    $entity_type = key($params);

    // If existing token, try to get current entity and replace tokens with
    // the right data.
    // @todo : dependency injection.
    if (isset($tokens[$entity_type]) && $entity = \Drupal::request()->attributes->get($entity_type)) {
      // Process with raw values for fields.
      if ($process) {
        $argument = $this->processToken($entity, $entity_type, $argument);
      }

      // If still a token, try to replace with token.
      if (strpos($argument, $entity_type)) {
        // If the token cannot be translated, it will be removed from
        // the final text.
        $argument = $token_service->replace($argument, [$entity_type => $entity], ['clear' => TRUE]);
      }
    }

    // Decode final argument HTML entities, as token returns encoded values.
    $argument = PlainTextOutput::renderFromHtml($argument);

    // Show debug for checking the value in the current context.
    if ($debug) {
      drupal_set_message($argument);
    }

    if (!$argument) {
      if (!$all_option) {
        return '';
      }

      $argument = 'all';
    }

    // Clean value (if + or , at the begining or at the end).
    // @todo : remove.
    $argument = $this->cleanArgumentValue($argument);

    return $argument;
  }

  /**
   * Process tokens as raw values.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *    Entity to process.
   * @param string $entity_type
   *    Entity type.
   * @param string $argument
   *    Argument to process.
   *
   * @return string
   *    Processed token.
   */
  public function processToken(FieldableEntityInterface $entity, $entity_type, $argument) {
    // @todo: $entity_type could potentially be retrieved by checking on $entity.
    $and_or = $this->options['and_or'];
    $field_names = $this->tokenScan($argument);

    foreach ($field_names[$entity_type] as $field_name => $token) {
      $replace_values = [];
      if ($entity->hasField($field_name)) {
        $field_values = $entity->get($field_name)->getValue();
        foreach ($field_values as $field_value) {
          $replace_values[] = array_values($field_value)[0];
        }

        // Replace and implode with , or + for multiple value management.
        $replace = implode($and_or, $replace_values);
        $argument = str_replace($token, $replace, $argument);
      }
    }

    return $argument;
  }

  /**
   * Scans a string to detect potential tokens.
   *
   * @param string $text
   *    The string to scan.
   *
   * @return string[]
   *    An array containing potential tokens ready for processing.
   */
  public function tokenScan($text) {
    // Matches tokens with the following pattern: [$type:$name]
    // $type and $name may not contain  [ ] characters.
    // $type may not contain : or whitespace characters, but $name may.
    preg_match_all('/
    \[             # [ - pattern start
    ([^\s\[\]:]*)  # match $type not containing whitespace : [ or ]
    :              # : - separator
    ([^\[\]]*)     # match $name not containing [ or ]
    \]             # ] - pattern end
    /x', $text, $matches);

    $types = $matches[1];
    $tokens = $matches[2];

    // Iterate through the matches, building an associative array containing
    // $tokens grouped by $types, pointing to the version of the token found in
    // the source text. For example, $results['node']['title'] = '[node:title]'.
    $results = array();
    for ($i = 0; $i < count($tokens); $i++) {
      $results[$types[$i]][$tokens[$i]] = $matches[0][$i];
    }

    return $results;
  }

  /**
   * Removes '+' or ',' at the beginning and at the end of an argument string.
   *
   * @param string $argument
   *    Argument string to clean.
   *
   * @todo: evaluate if still needed.
   *
   * @return string
   *    Cleaned argument.
   */
  public function cleanArgumentValue($argument) {
    // Remove '+' or ',' at the beginning.
    if ($argument[0] == '+' || $argument[0] == ',') {
      $argument = substr($argument, 1);
    }
    // Remove '+' or ',' at the the end.
    if ($argument[strlen($argument) - 1] == '+' || $argument[strlen($argument) - 1] == ',') {
      $argument = substr($argument, 0, -1);
    }

    return $argument;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   *
   * @todo : define cachecontexts.
   */
  public function getCacheContexts() {
    return [];
  }

}
