<?php

namespace Drupal\identity\Plugin\IdentityDataType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\BundleFieldDefinition;
use Drupal\identity\Entity\IdentityData;
use Drupal\identity\IdentityMatch;

/**
 * Class Address
 *
 * @IdentityDataType(
 *   id = "address",
 *   label = @Translation("Address"),
 * );
 *
 * @package Drupal\identity\Plugin\IdentityDataType
 */
class Address extends IdentityDataTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['address'] = BundleFieldDefinition::create('address')
      ->setLabel(new TranslatableMarkup('Address'))
      ->setCardinality(1)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('available_countries', ['US' => 'US'])
      ->setSetting('field_overrides', [
        'givenName' => ['override' => 'hidden'],
        'familyName' => ['override' => 'hidden'],
        'organization' => ['override' => 'hidden']
      ])
      ->setDisplayOptions('form', [
        'type' => 'address',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address_type'] = BundleFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setSetting('allowed_values', [
        'mailing' => new TranslatableMarkup('Mailing'),
        'physical' => new TranslatableMarkup('Physical')
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

    $enough_data = FALSE;
    if ($data->address->country_code) {
      $query->condition('address__country_code', $data->address->country_code);
    }
    if ($data->address->administrative_area) {
      $query->condition('address__administrative_area', $data->address->administrative_area);
    }
    if ($data->address->locality) {
      $query->condition('address__locality', $data->address->locality);
    }
    if ($data->address_line1) {
      $enough_data = TRUE;
      $query->condition('address__address_line1', $data->address->address_line1);
    }

    if (!$enough_data) {
      return [];
    }

    $matches = [];
    foreach ($this->identityDataStorage->loadMultiple($query->execute()) as $match_data) {
      /** @var IdentityData $match_data */
      $matches[$match_data->getIdentity()->id()] = new IdentityMatch(10, $match_data, $data);
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
      if (
        $data->address->country_code == $identity_data->address->country_code &&
        $data->address->administrative_area == $identity_data->address->administrative_area &&
        $data->address->locality == $identity_data->address->locality &&
        $data->address->address_line1 == $identity_data->address->address_line1
      ) {
        $match->supportMatch($identity_data, 10);
      }
    }
  }
}
