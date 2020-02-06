<?php

namespace Drupal\sitesettings_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\system\Form\SiteInformationForm;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;

/**
 * Extend site settings form.
 */
class SiteSettingsFormExtended extends SiteInformationForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $invalidator;

  /**
   * Constructs SiteSettingsFormExtended .
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $invalidator
   *   The cache factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context, MessengerInterface $messenger, CacheTagsInvalidator $invalidator) {
    parent::__construct($config_factory, $alias_manager, $path_validator, $request_context);
    $this->messenger = $messenger;
    $this->invalidator = $invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('messenger'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $form = parent::buildForm($form, $form_state);
    // New siteapikey field.
    $form['site_information']['siteapikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site API Key'),
      '#default_value' => $site_config->get('siteapikey') ?: "No API Key yet",
      '#description' => $this->t("Site API Key"),
    ];

    // Update submit label.
    $form['actions']['submit']['#value'] = $this->t('Update Configuration');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $messenger = $this->messenger;

    // Default flag to check if site_api is saved.
    $siteapiflag = 0;
    // Show status message only if user value is diff from previous.
    if (empty($form_state->getValue('siteapikey')) || $form_state->getValue('siteapikey') === "No API Key yet") {
      // Clear existing siteapikey and invalidate custom_node cache tag.
      $this->config('system.site')->clear('siteapikey')->save();
      $this->invalidator->invalidateTags(['config:rest.resource.custom_node']);
      $messenger->addMessage($this->t('Site API Key is not set.'), $messenger::TYPE_WARNING);
    }
    elseif ($site_config->get('siteapikey') !== $form_state->getValue('siteapikey')) {
      $siteapiflag = 1;
    }

    // Save config only if existing config is diff from user input.
    if ($siteapiflag == 1) {
      $site_config->set('siteapikey', $form_state->getValue('siteapikey'))->save();
      // Invalidate custom_node cache tag.
      $this->invalidator->invalidateTags(['config:rest.resource.custom_node']);
    }

    // Display message if flag is set and siteapikey config exists.
    if ($site_config->get('siteapikey') && $siteapiflag == 1) {
      $messenger->addMessage($this->t('Site API Key updated.'), $messenger::TYPE_STATUS);
    }

    parent::submitForm($form, $form_state);
  }

}
