<?php

namespace Drupal\sitenow_core\Validate;

use Drupal\Core\Form\FormStateInterface;
use Sitenow\Blt\Plugin\Commands\MultisiteCommands;

/**
 * Request a site webform URL field callable.
 */
class RequestASiteUriConstraint {

  /**
   * Validates given element.
   *
   * @param array $element
   *   The form element to process.
   * @param Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    $webformKey = $element['#webform_key'];
    $value = $formState->getValue($webformKey);

    $url = parse_url($value);

    foreach (['port', 'user', 'pass', 'path', 'query', 'fragment'] as $invalid) {
      if (isset($url[$invalid])) {
        $formState->setError(
          $element,
          t('URL @value must not contain a @invalid.', [
            '@value' => $value,
            '@invalid' => $invalid,
          ])
        );
      }
    }

    // Validate the URL pattern if this is a new site.
    if ($formState->getValue('request_type') == 'New') {
      if ($url['scheme'] == 'http') {
        $formState->setError(
          $element,
          t('URL @value must begin with https:// scheme.', [
            '@value' => $value,
          ])
        );
      }

      $pattern = $formState->getValue('url_pattern');
      $pattern = explode('*.', $pattern)[1];

      // The host should contain exactly 4 parts,=. Subdomains of approved URL
      // patterns are not allowed, e.g. foo.bar.sites.uiowa.edu is invalid.
      $parts = explode('.', $url['host']);

      if (count($parts) != 4) {
        $formState->setError(
          $element,
          t('URL @value must match the URL pattern *.@pattern.', [
            '@value' => $value,
            '@pattern' => $pattern,
          ])
        );
      }

      // Assuming the host contains exactly 4 parts, the last three should match
      // the pattern (minus the '*.' placeholder) when combined.
      $match = implode('.', [
        $parts[1],
        $parts[2],
        $parts[3],
      ]);

      if ($match != $pattern) {
        $formState->setError(
          $element,
          t('URL @value must match the URL pattern *.@pattern.', [
            '@value' => $value,
            '@pattern' => $pattern,
          ])
        );
      }
    }

    // Assuming the URI checks out, set some hidden fields based on it.
    $formState->setValue('url_without_scheme', str_replace($url['scheme'], '', $value));

    $command = new MultisiteCommands();
    $machine_name = $command->generateMachineName($value);
    $formState->setValue('url_internal_production', "https://{$machine_name}.prod.drupal.uiowa.edu");
  }

}
