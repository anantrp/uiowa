<?php

namespace Drupal\sitenow_p2lb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Primary paragraphs2layoutbuilder class.
 */
class P2LbSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sitenow_p2lb_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>These settings let you configure and use SiteNow paragraphs2layoutbuilder on this site.</p>'),
    ];

    $nids_w_paragraphs = sitenow_p2lb_paragraph_nodes();
    $nids_w_paragraphs = array_combine($nids_w_paragraphs, $nids_w_paragraphs);
    $form['nodes_w_paragraphs'] = [
      '#type' => 'checkboxes',
      '#title' => t('Nodes with paragraph items.'),
      '#options' => $nids_w_paragraphs,
    ];

    $form['delete'] = [
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#name' => 'delete',
      '#submit' => array([$this, 'deleteButton']),
    ];

    $form['update'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
      '#name' => 'update',
      '#submit' => array([$this, 'updateButton']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * Delete connected paragraphs from the selected nodes.
   */
  public function deleteButton(array &$form, FormStateInterface $form_state) {
    $nids = array_filter(array_values($form_state->getValue('nodes_w_paragraphs')));
    foreach ($nids as $nid) {
      sitenow_p2lb_remove_attached_paragraphs($nid);
    }
    return $form_state;
  }

  /**
   * Update paragraphs to lb blocks from the selected nodes.
   */
  public function updateButton(array &$form, FormStateInterface $form_state) {
    $nids = array_filter(array_values($form_state->getValue('nodes_w_paragraphs')));
    foreach ($nids as $nid) {
      $section_ids = sitenow_p2lb_fetch_section_ids($nid);
      foreach($section_ids as $section_id) {
        sitenow_p2lb_process_section($section_id);
      }
    }
    return $form_state;
  }
}