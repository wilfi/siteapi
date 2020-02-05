<?php

namespace Drupal\sitesettings_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\system\Form\SiteInformationForm;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SiteSettingsFormExtended extends SiteInformationForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   */
  public function __construct(ConfigFactoryInterface $config_factory, $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context, MessengerInterface $messenger)
  {
    parent::__construct($config_factory, $alias_manager, $path_validator, $request_context);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('messenger')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $form =  parent::buildForm($form, $form_state);
    // New siteapikey field.
    $form['site_information']['siteapikey'] = [
      '#type' => 'textfield',
      '#title' => t('Site API Key'),
      '#default_value' => $site_config->get('siteapikey') ?: "No API Key yet",
      '#description' => t("Site API Key"),
    ];

    // Update submit label.
    $form['actions']['submit']['#value'] = t('Update Configuration');

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $messenger = $this->messenger;

    // Show status message only if user value is diff from previous.
    if(empty($form_state->getValue('siteapikey')) || $form_state->getValue('siteapikey') === "No API Key yet") {
      $messenger->addMessage($this->t('Site API Key is not set.'), $messenger::TYPE_STATUS);
    } else if($site_config->get('siteapikey') !== $form_state->getValue('siteapikey')) {
      $messenger->addMessage($this->t('Site API Key updated.'), $messenger::TYPE_STATUS);
    }

    $site_config->set('siteapikey', $form_state->getValue('siteapikey'))->save();
    parent::submitForm($form, $form_state);
  }
}
