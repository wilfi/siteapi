<?php

namespace Drupal\sitesettings_api\Plugin\rest\resource;

use Drupal\node\NodeInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Annotation for get method.
 *
 * @RestResource(
 *   id = "custom_node",
 *   label = @Translation("Custom node"),
 *   uri_paths = {
 *     "canonical" = "/page_json/{site_api}/{nid}"
 *   }
 * )
 */
class BasicPageResource extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ConfigFactoryInterface Manager.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entitytype_manager
   *   The entitytype_manager factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entitytype_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entitytype_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('sitesettings_api'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a node object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($site_api, $nid) {
    if ($site_api && $nid) {
      $system_site = $this->configFactory->get('system.site');
      $config_api = $system_site->get('siteapikey');

      if ($config_api !== $site_api || empty($config_api)) {
        return new ResourceResponse('access denied.', 403);
      }

      // Load node based on nid.
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node instanceof NodeInterface && $node->getType() == 'page') {
        // Sets node object for response.
        $response_result[$node->id()] = $node;
        $response = new ResourceResponse($response_result);
        // Configure caching for results.
        if ($response instanceof CacheableResponseInterface) {
          $response->addCacheableDependency($response_result);
        }
        return $response;
      }
      return new ResourceResponse('Invalid node.', 400);

    }
    return new ResourceResponse('Node id required.', 400);
  }

}
