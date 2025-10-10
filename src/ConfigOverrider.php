<?php

declare(strict_types=1);

namespace Drupal\localgov_moderngov;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Alters site configuration.
 *
 * Carries out ModernGov template related configuration alterations.
 */
class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * Overrides asset aggregation config.
   *
   * Deactivates asset aggregation when the `noaggregation` HTTP query parameter
   * is present on the ModernGov template page.
   */
  public function loadOverrides($names) {

    $overrides = [];

    $is_moderngov_tpl = $this->requestStack->getCurrentRequest()->get('_route') === 'localgov_moderngov.modern_gov';
    $has_noaggregation_requirement = !is_null($this->requestStack->getCurrentRequest()->get('noaggregation'));

    if ($is_moderngov_tpl && $has_noaggregation_requirement && in_array('system.performance', $names)) {
      $overrides['system.performance']['css']['preprocess'] = FALSE;
      $overrides['system.performance']['js']['preprocess']  = FALSE;
    }

    return $overrides;
  }

  /**
   * As it says on the tin.
   */
  public function getCacheSuffix() {

    return 'localgov_moderngov';
  }

  /**
   * Placeholder.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {

    return NULL;
  }

  /**
   * Placeholder.
   */
  public function getCacheableMetadata($name) {

    return new CacheableMetadata();
  }

  /**
   * Keeps track of dependencies.
   */
  public function __construct(protected RequestStack $requestStack) {}

}
