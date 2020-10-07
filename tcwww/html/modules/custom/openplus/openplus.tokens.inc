<?php

/**
 * @file
 * Builds custom placeholder replacement tokens for this site.
 */

use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\tac_lite\Form\SchemeForm;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function openplus_token_info() {
  $types = [];
  $tokens = [];

  // Webform submission.
  $webform_submission = [];
  $webform_submission['drone_emails'] = [
    'name' => t('Drone incident email addresses'),
    'description' => t('Returns the email addresses per province'),
  ];
  $tokens['webform_submission'] = $webform_submission;

  // tac_lite
  $tokens['current-user']['tac_term_ids'] = [
    'name' => t('TAC term ids from user'),
    'description' =>  t('A token to return tac lite tids of a user + delimited for views.'),
  ];


  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function openplus_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  // current-user tokens
  if ($type == 'current-user') {
    foreach ($tokens as $name => $original) {
      // Find the desired token by name.
      switch ($name) {
        case 'tac_term_ids':
          // this is in Data
          //$user = $data['user'];
          $config = SchemeForm::tacLiteConfig(1);
          //$user = \Drupal::currentUser();
          $user = $data['current-user'];
          $tids = array_filter(_tac_lite_user_tids($user, 1, $config));
          $replacements[$original] = implode('+', $tids);
          break;
      }
    }
  }

  // webform-submission tokens
  if ($type == 'webform_submission' && !empty($data['webform_submission'])) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];
    $webform = $webform_submission->getWebform();

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'drone_emails':
          // get the value of the province from the address element
          $submission_data = $webform_submission->getData();
          $province = $submission_data['address']['state_province'];

          // look up value in taxonomy for province
          $query = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', 'provinces')
            ->condition('field_province_abbr', $province);
          $tids = $query->execute();

          // get drone email fields
          $emails = [];
          if (!empty($tids)) {
            $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load(array_pop($tids));
            $contacts = $term->get('field_drone_contact')->getValue();
            if (!empty($contacts)) {
              foreach ($contacts as $contact) {
                $emails[] = $contact['value'];
              }
            }
          }
          else {
            $config = \Drupal::config('openplus.settings');
            $emails = $config->get('drone_default_contacts');
          }

          $replacements[$original] = is_array($emails) ? implode(',', $emails) : $emails;
          break;
      }
    }
  }

  return $replacements;
}

