<?php

namespace Drupal\identity\Plugin\IdentityDataType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\BundleFieldDefinition;
use Drupal\identity\Entity\IdentityData;
use Drupal\identity\IdentityMatch;

/**
 * Class Email
 *
 * @IdentityDataType(
 *   id = "email",
 *   label = @Translation("Email"),
 * );
 *
 * @package Drupal\identity\Plugin\IdentityDataType
 */
class Email extends IdentityDataTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['address'] = BundleFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Address'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['email_type'] = BundleFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setSetting('allowed_values', [
        'unknown' => new TranslatableMarkup('Unknown'),
        'personal' => new TranslatableMarkup('Personal'),
        'work' => new TranslatableMarkup('Work'),
        'other' => new TranslatableMarkup('Other')
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function findMatches(IdentityData $data) {
    $query = $this->identityDataStorage->getQuery();
    $query->condition('type', $this->pluginId);

    if ($data->email_address->value) {
      $query->condition('email_address', $data->email_address->value);
    }
    else {
      return [];
    }

    $matches = [];
    foreach ($this->identityDataStorage->loadMultiple($query->execute()) as $match_data) {
      /** @var IdentityData $match_data */
      $matches[$match_data->getIdentity()->id()] = new IdentityMatch(1000, $match_data, $data);
    }

    return $matches;
  }

  /**
   * Work out whether the data supports or opposes
   *
   * @param \Drupal\identity\Entity\IdentityData $data
   * @param \Drupal\identity\IdentityMatch $match
   */
  public function supportOrOppose(IdentityData $data, IdentityMatch $match) {
    $identity = $match->getIdentity();

    foreach ($identity->getData($this->pluginId) as $identity_data) {
      if ($data->address->value == $identity_data->address->value) {
        $match->supportMatch($identity_data, 1000);
      }
    }
  }
}
