<?php

namespace Drupal\views_extras\Plugin\views\argument_default;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Default argument plugin to use the raw value from the URL.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "tempstore",
 *   title = @Translation("TempStore varibale")
 * )
 */
class TempStore extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * Token Handler.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

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
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempStore Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser, ModuleHandlerInterface $moduleHandler, Token $token, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
    $this->moduleHandler = $moduleHandler;
    $this->token = $token;
    $this->tempStoreFactory = $temp_store_factory;
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
      $container->get('token'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['tempStore_unique_name'] = ['default' => ''];
    $options['tempStore_key'] = ['default' => ''];
    $options['fallback_value'] = ['default' => FALSE];
    $options['cache_time'] = ['default' => -1];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['tempStore_unique_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique Name'),
      '#default_value' => $this->options['tempStore_unique_name'],
      '#description' => $this->t('Unique namespace, used while setting the tempStore'),
    ];
    $form['tempStore_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TempStore key'),
      '#default_value' => $this->options['tempStore_key'],
      '#description' => $this->t('Keys of tempStore variable'),
    ];
    $form['fallback_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('If tempStore is not set, what should be the fallback value.'),
      '#default_value' => $this->options['fallback_value'],
      '#description' => $this->t('You may use user tokens.'),
    ];
    if ($this->moduleHandler->moduleExists("token")) {
      $form['token_help'] = [
        '#type' => 'markup',
        '#token_types' => ['user'],
        '#theme' => 'token_tree_link',
      ];
    }
    $form['cache_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Maximum Age.'),
      '#default_value' => $this->options['cache_time'],
      '#description' => $this->t('If tempStore value changes set it to 0.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (!empty($key = $this->options['tempStore_key'])) {
      return $this->tempStoreFactory->get($this->options['tempStore_unique_name'])->get($this->options['tempStore_key']);
    }
    elseif (!empty($value = $this->options['fallback_value'])) {
      return $this->token->replace($value, ['user' => $this->currentUser]);
    }
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

}
