<?php

namespace Drupal\sitenow_pages\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Drupal\pathauto\PathautoGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure SiteNow Pages settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'sitenow_pages.settings';

  /**
   * The alias cleaner.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * The alias checker.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected $aliasRepository;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The PathautoGenerator service.
   *
   * @var \Drupal\pathauto\PathautoGenerator
   */
  protected $pathAutoGenerator;

  /**
   * The Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\pathauto\AliasCleanerInterface $pathauto_alias_cleaner
   *   The alias cleaner.
   * @param \Drupal\path_alias\AliasRepositoryInterface $aliasRepository
   *   The alias checker.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The EntityTypeManager service.
   * @param \Drupal\pathauto\PathautoGenerator $pathAutoGenerator
   *   The PathautoGenerator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasCleanerInterface $pathauto_alias_cleaner, AliasRepositoryInterface $aliasRepository, EntityTypeManager $entityTypeManager, PathautoGenerator $pathAutoGenerator) {
    parent::__construct($config_factory);
    $this->aliasCleaner = $pathauto_alias_cleaner;
    $this->aliasRepository = $aliasRepository;
    $this->entityTypeManager = $entityTypeManager;
    $this->pathAutoGenerator = $pathAutoGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('pathauto.alias_cleaner'),
      $container->get('path_alias.repository'),
      $container->get('entity_type.manager'),
      $container->get('pathauto.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sitenow_pages_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
      'pathauto.pattern.article',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $form = parent::buildForm($form, $form_state);

    $featured_image_display_default = $config->get('featured_image_display_default');
    $level = $config->get('level');
    $depth = $config->get('depth');

    $form['global']['featured_image'] = [
      '#type' => 'fieldset',
      '#title' => 'Featured image',
      '#collapsible' => FALSE,
    ];

    $form['global']['featured_image']['featured_image_display_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Display featured image'),
      '#description' => $this->t('Set the default behavior for how to display a featured image.'),
      '#options' => [
        'do_not_display' => $this
          ->t('Do not display'),
        'small' => $this
          ->t('Small'),
        'medium' => $this
          ->t('Medium'),
        'large' => $this
          ->t('Large'),
      ],
      '#default_value' => $featured_image_display_default ?: 'large',
    ];

    $form['global']['menu_levels'] = [
      '#type' => 'fieldset',
      '#title' => 'Menu settings',
      '#collapsible' => FALSE,
    ];

    $options = range(1, 4);

    $form['global']['menu_levels']['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial visibility level'),
      '#default_value' => $level ?: 1,
      '#options' => $options,
      '#description' => $this->t('The menu is only visible if the menu link for the current page is at this level or below it. Use level 1 to always display this menu.'),
      '#required' => TRUE,
    ];

    $form['global']['menu_levels']['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of levels to display'),
      '#default_value' => $depth ?: 3,
      '#options' => $options,
      '#description' => $this->t('This maximum number includes the initial level.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $featured_image_display_default = $form_state->getValue('featured_image_display_default');
    $level = $form_state->getValue('level');
    $depth = $form_state->getValue('depth');

    $this->configFactory->getEditable(static::SETTINGS)
      // Save the featured image display default.
      ->set('featured_image_display_default', $featured_image_display_default)
      ->set('level', $level)
      ->set('depth', $depth)
      ->save();

    parent::submitForm($form, $form_state);

    drupal_flush_all_caches();
  }

}
