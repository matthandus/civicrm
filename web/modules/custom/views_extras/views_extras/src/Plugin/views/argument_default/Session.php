<?php

namespace Drupal\views_extras\Plugin\views\argument_default;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to use the raw value from the URL.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "session",
 *   title = @Translation("Session variable from session")
 * )
 */
class Session extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The current path.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Token Handler.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a Raw object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user, ModuleHandlerInterface $moduleHandler, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->current_user = $current_user;
    $this->moduleHandler = $moduleHandler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['session_key'] = array('default' => '');
    $options['fallback_value'] = array('default' => FALSE);
    $options['cache_time'] = array('default' => -1);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['session_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Session variable key'),
      '#default_value' => $this->options['session_key'],
      '#description' => $this->t('Keys of SESSION variable seperated by ::, e.g. for $_SESSION["key1"]["key2"], the key would be "key1::key2".'),
    );
    $form['fallback_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('If session variable is not set, what should be the fallback value.'),
      '#default_value' => $this->options['fallback_value'],
      '#description' => $this->t('You may use user tokens.'),
    );
    if ($this->moduleHandler->moduleExists("token")) {
      $form['token_help'] = array(
        '#type' => 'markup',
        '#token_types' => array('user'),
        '#theme' => 'token_tree_link',
      );
    }
    $form['cache_time'] = array(
      '#type' => 'number',
      '#title' => $this->t('Cache Maximum Age.'),
      '#default_value' => $this->options['cache_time'],
      '#description' => $this->t('If session variable changes in between session set it to 0.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (!empty($key = $this->options['session_key'])) {
      $keys = explode('::', $key);
      if ($value = $this->findArrayValue($_SESSION, $keys)) {
        return $value;
      }
    }

    if (!empty($value = $this->options['fallback_value'])) {
      return $this->token->replace($value, ['user' => $this->current_user]);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return (int) $this->options['cache_time'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['session'];
  }

  /**
   * A helper function to return a value from a multidimensional array.
   *
   * @param array $array
   *   The array in which key has to be found.
   * @param array $keys
   *   The array of keys to be find in $array.
   */
  protected function findArrayValue(array $array, array $keys) {
    if (array_key_exists($keys[0], $array)) {
      if (is_array($array[$keys[0]])) {
        $next_key = array_shift($keys);
        if (!empty($keys)) {
          return $this->findArrayValue($array[$next_key], $keys);
        }
      }
      else {
        return $array[$keys[0]];
      }
    }
    return FALSE;
  }

}
