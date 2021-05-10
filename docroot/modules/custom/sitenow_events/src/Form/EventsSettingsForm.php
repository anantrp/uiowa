<?php

namespace Drupal\sitenow_events\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure events settings for this site.
 */
class EventsSettingsForm extends ConfigFormBase {
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
  /**
   * The PathautoGenerator service.
   *
   * @var \Drupal\pathauto\PathautoGenerator
   */

  /**
   * The Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\pathauto\AliasCleanerInterface $pathauto_alias_cleaner
   *   The alias cleaner.
   * @param \Drupal\path_alias\AliasRepositoryInterface $aliasRepository
   *   The alias checker.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasCleanerInterface $pathauto_alias_cleaner, AliasRepositoryInterface $aliasRepository) {
    parent::__construct($config_factory);
    $this->aliasCleaner = $pathauto_alias_cleaner;
    $this->aliasRepository = $aliasRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('pathauto.alias_cleaner'),
      $container->get('path_alias.repository'),
      $container->get('pathauto.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sitenow_events_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sitenow_events.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('sitenow_events.settings');
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>These settings let you configure the SiteNow Events module.</p>'),
    ];
    $form['global'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site-wide settings'),
      '#description' => $this->t('These settings affect all event lists and single instances.'),
    ];
    $form['global']['sitenow_events_event_link'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Option'),
      '#default_value' => $config->get('event_link'),
      '#description' => $this->t('Choose to have events link to events.uiowa.edu or an event page on this site.'),
      '#options' => [
        'event-link-external' => $this->t('Link to events.uiowa.edu'),
        'event-link-internal' => $this->t('Link to page on this site'),
      ],
    ];
    $form['global']['sitenow_events_single_event_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Single event path'),
      '#description' => $this->t('The base path component for a single event. Defaults to <em>event</em>.'),
      '#default_value' => $config->get('single_event_path'),
      '#required' => TRUE,
    ];
    $form['view_page'] = [
      '#type' => 'fieldset',
      '#title' => 'View Page Settings',
      '#collapsible' => FALSE,
    ];
    $form['view_page']['sitenow_events_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Event page'),
      '#default_value' => $config->get('event_status'),
      '#description' => $this->t('If checked, an event page will display at the configurable path below.'),
      '#size' => 60,
    ];
    $form['view_page']['sitenow_events_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event title'),
      '#description' => $this->t('The title for the event listing. Defaults to <em>Event</em>.'),
      '#default_value' => $config->get('event_title'),
      '#required' => TRUE,
    ];
    $form['view_page']['sitenow_events_header_content'] = [
      '#type' => 'text_format',
      '#format' => 'filtered_html',
      '#title' => $this->t('Header Content'),
      '#description' => $this->t('Enter any content that is displayed above the Event listing.'),
      '#default_value' => $config->get('event_header'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if path already exists.
    $path = $form_state->getValue('sitenow_events_single_event_path');
    // Clean up path first.
    $path = $this->aliasCleaner->cleanString($path);
    $path_exists = $this->aliasRepository->lookupByAlias('/' . $path, 'en');
    if ($path_exists) {
      $form_state->setErrorByName('path', $this->t('This path is already in-use.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('sitenow_events_single_event_path');
    // Clean path.
    $path = $this->aliasCleaner->cleanString($path);
    $this->config('sitenow_events.settings')
      ->set('event_status', $form_state->getValue('sitenow_events_status'))
      ->set('event_title', $form_state->getValue('sitenow_events_title'))
      ->set('event_link', $form_state->getValue('sitenow_events_event_link'))
      ->set('single_event_path', $path)
      ->set('event_header', $form_state->getValue('sitenow_events_header_content'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
