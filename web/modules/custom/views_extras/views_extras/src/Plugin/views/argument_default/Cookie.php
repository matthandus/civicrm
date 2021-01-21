<?php

namespace Drupal\views_extras\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
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
 *   id = "cookie",
 *   title = @Translation("Cookie variable from cookie")
 * )
 */
class Cookie extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

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
    $options['cookie_key'] = array('default' => '');
    $options['fallback_value'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['cookie_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cookie variable key'),
      '#default_value' => $this->options['cookie_key'],
      '#description' => $this->t('Key of Cookie variable, e.g. for $_COOKIE["key"], the key would be "key".'),
    );
    $form['fallback_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('If cookie variable is not set, what should be the fallback value.'),
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
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (!empty($key = $this->options['cookie_key']) && !empty($_COOKIE['Drupal_visitor_' . $key])) {
      return $_COOKIE['Drupal_visitor_' . $key];
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
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cookie = $this->options['cookie_key'];
    $cookie = 'Drupal_visitor_' . $cookie;
    return ['cookies:' . $cookie];
  }

}
